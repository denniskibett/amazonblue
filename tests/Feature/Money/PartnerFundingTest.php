<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Models\User;
use App\Services\Partners\PartnerLines;
use App\Services\Partners\PartnerLinks;
use App\Support\MpesaSms;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Partner money (owner, 23 Oct 2026): money from an M-Pesa SMS becomes a short- or long-term line, a loan is
 * disbursed from it, repayments pay the partner their share and close the line with a payout on Requests;
 * partners get their broker share back on their own loans; past loans can be re-linked with a dry run.
 */
class PartnerFundingTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $admin;

    private Partner $isiro;

    private Partner $imagine;

    private Partner $house;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-23 10:00');
        config(['lending.engine' => 'v2', 'lending.split.enabled' => true, 'lending.ledger.enabled' => false, 'lending.penalty.cap' => 'none',
            'lending.approvals.enabled' => false]);
        $this->seed(RoleSeeder::class);
        $this->loanTypes();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(['create disbursements', 'edit partners']);
        $isiroUser = User::factory()->create();
        $this->isiro = Partner::create(['name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test', 'type' => 'business', 'account_type' => 'principal',
            'user_id' => $isiroUser->id, 'broker_rate' => 40, 'profit_share_rate' => 8]);
        DB::table('brokers')->insert(['user_id' => $isiroUser->id, 'interest_client' => 20, 'interest_broker' => 40, 'penalty_client' => 0, 'penalty_broker' => 50, 'cert_no' => 'BRK-T']);
        $this->imagine = Partner::create(['name' => 'IMAGINE-NATION', 'email' => 'in@example.test', 'type' => 'corporate', 'account_type' => 'principal', 'profit_share_rate' => 40]);
        $this->house = Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** An approved 10,000 loan at 20% (10 days), with its first cycle. */
    private function approved(int $id, ?int $userId = null): Loan
    {
        $this->loanRow($id, 1, 10000, 10000, '2026-10-23', '2026-11-02', 'approved', 1, 30);
        if ($userId) {
            DB::table('loans')->where('id', $id)->update(['user_id' => $userId]);
        }
        $this->cycleRow($id, $id, 1, 0, 2000, 12000, 20, '2026-10-23', '2026-11-02', 'active', 'Initial');

        return Loan::findOrFail($id);
    }

    private function disburse(Loan $loan, array $extra)
    {
        return $this->actingAs($this->admin)->postJson(route('disbursements.store'), $extra + ['loan_id' => $loan->id, 'amount' => 10000,
            'disburse_date' => '2026-10-23', 'transaction' => 'QD' . $loan->id, 'mode' => 'mpesa']);
    }

    private function pay(int $loanId, float $amount, string $ref): Repayment
    {
        if (now()->lt('2026-10-30 12:00')) {
            Carbon::setTestNow('2026-10-30 12:00');      // a payment can't be dated after today
        }
        // the way staff and M-Pesa record a payment: the loan's status follows it
        return app(\App\Services\RepaymentService::class)->createRepayment(Loan::findOrFail($loanId),
            ['amount' => $amount, 'repayment_date' => '2026-10-30', 'transaction' => $ref, 'mode' => 'mpesa'], \App\Models\LoanCycle::findOrFail($loanId));
    }

    public function test_the_sms_reader_finds_code_amount_sender_and_date(): void
    {
        $s = MpesaSms::parse('TJN4H7K2LM Confirmed. You have received Ksh50,000.00 from JOHN DOE 0712345678 on 23/10/26 at 2:15 PM New M-PESA balance is Ksh60,100.00.');
        $this->assertSame(['TJN4H7K2LM', 50000.0, 'JOHN DOE', '0712345678', '2026-10-23 14:15'], [$s['code'], $s['amount'], $s['party'], $s['phone'], $s['at']]);
        $this->assertSame(1250.5, MpesaSms::parse('TJN4H7K2LM Confirmed. Ksh 1,250.50 paid to MAMA MBOGA. on 3/1/2026 at 11:40 AM.')['amount']);
        $this->assertNull(MpesaSms::parse('hello there'));
        $this->actingAs($this->admin)->postJson(route('staff.mpesa.read-sms'), ['sms' => 'TJN4H7K2LM Confirmed. Ksh5.00 received from X on 1/1/26 at 1:00 PM'])
            ->assertOk()->assertJsonPath('sms.code', 'TJN4H7K2LM')->assertJsonPath('used', false);
    }

    public function test_new_short_term_money_funds_the_loan_pays_its_share_and_closes_with_a_payout(): void
    {
        $loan = $this->approved(801);
        $this->disburse($loan, ['funding_mode' => 'new', 'new_partner_id' => $this->imagine->id, 'new_amount' => 10000, 'new_code' => 'tjn4h7k2lm',
            'new_term' => 'short_term', 'new_maturity' => '2026-11-02', 'broker_partner_id' => 0])->assertCreated();

        $line = Investment::where('facility_type', 'short_term')->where('return_type', 'loan_share')->firstOrFail();
        $this->assertSame($line->id, (int) Disbursement::where('loan_id', 801)->value('investment_id'));
        $this->assertSame(10000.0, (float) InvestmentFunding::where('investment_id', $line->id)->value('amount_disbursed'));
        $this->assertSame(10000.0, (float) PartnerTransaction::where('reference', 'MPESA-TJN4H7K2LM')->value('amount'));
        $this->assertSame(0, (int) $loan->fresh()->broker_status);

        // repaid on time: 2,000 interest, IMAGINE-NATION takes 40%, the line gets its 10,000 back and closes
        $this->pay(801, 12000, 'QK801');
        $this->assertEqualsWithDelta(800, (float) PartnerTransaction::where('partner_id', $this->imagine->id)->where('type', 'profit_distribution')->sum('amount'), 0.001);
        $this->assertSame('matured', $line->fresh()->status);
        $payouts = DB::table('partner_requests')->where('partner_id', $this->imagine->id)->where('type', 'withdrawal')->pluck('amount', 'wallet_slug');
        $this->assertEqualsWithDelta(10000, (float) $payouts['principal'], 0.001);
        $this->assertEqualsWithDelta(800, (float) $payouts['interest'], 0.001);
        // closing twice queues nothing more
        app(PartnerLines::class)->closeIfDone($line->fresh());
        $this->assertSame(2, DB::table('partner_requests')->where('partner_id', $this->imagine->id)->count());

        // the same code can't be recorded twice
        $loan2 = $this->approved(802);
        $this->disburse($loan2, ['funding_mode' => 'new', 'new_partner_id' => $this->imagine->id, 'new_amount' => 10000, 'new_code' => 'TJN4H7K2LM',
            'new_term' => 'short_term'])->assertStatus(422)->assertJsonValidationErrors('new_code');
        $this->assertNull(Disbursement::where('loan_id', 802)->first(), 'nothing saved when the line could not open');
    }

    public function test_a_long_term_line_lends_its_money_again_and_stays_open_until_it_matures(): void
    {
        $line = app(PartnerLines::class)->open($this->imagine, 10000, 'long_term', '2027-04-30', 'LT0000001', '2026-10-01', $this->admin);
        $loan = $this->approved(811);
        $this->disburse($loan, ['funding_mode' => 'line', 'investment_id' => $line->id])->assertCreated();
        $this->assertSame(0.0, $line->fresh()->remainingCommitted());

        $this->pay(811, 12000, 'QK811');
        $this->assertSame('active', $line->fresh()->status, 'not matured yet');
        $this->assertSame(10000.0, $line->fresh()->remainingCommitted(), 'the principal paid back can be lent again');

        $loan2 = $this->approved(812);
        $this->disburse($loan2, ['funding_mode' => 'line', 'investment_id' => $line->id])->assertCreated();
        $this->pay(812, 12000, 'QK812');

        Carbon::setTestNow('2027-05-01 03:00');
        $this->artisan('partners:lines')->assertSuccessful();
        $this->assertSame('matured', $line->fresh()->status);
        $this->assertEqualsWithDelta(1600, (float) DB::table('partner_requests')->where('reference', 'LINE-' . $line->id . '-INTEREST')->value('amount'), 0.001);
        $this->disburse($this->approved(813), ['funding_mode' => 'line', 'investment_id' => $line->id])->assertStatus(422);
    }

    public function test_a_written_off_loan_shows_the_shortfall_on_the_payout(): void
    {
        $line = app(PartnerLines::class)->open($this->imagine, 10000, 'short_term', null, 'SHORT00001', null, $this->admin);
        $this->disburse($this->approved(821), ['funding_mode' => 'line', 'investment_id' => $line->id])->assertCreated();
        $this->pay(821, 6000, 'QK821');                     // 2,000 interest, 4,000 principal
        Loan::findOrFail(821)->forceFill(['status' => 'written_off'])->save();

        $this->assertSame('matured', $line->fresh()->status);
        $r = DB::table('partner_requests')->where('reference', 'LINE-' . $line->id . '-PRINCIPAL')->first();
        $this->assertEqualsWithDelta(4000, (float) $r->amount, 0.001);
        $this->assertStringContainsString('6,000.00 not paid back', $r->note);
    }

    public function test_a_broker_and_a_partners_own_loan_get_their_share_back(): void
    {
        $this->disburse($this->approved(831), ['broker_partner_id' => $this->isiro->id])->assertCreated();
        $loan = Loan::findOrFail(831);
        $this->assertSame([1, $this->isiro->id, 40.0], [(int) $loan->broker_status, (int) $loan->partner_id, (float) $loan->broker_rate]);

        // ISIRO borrowing for itself: no broker chosen, so it is its own broker and gets 40% of the interest back
        $this->disburse($this->approved(832, $this->isiro->user_id), [])->assertCreated();
        $this->assertSame($this->isiro->id, (int) Loan::findOrFail(832)->partner_id);
        $this->pay(832, 12000, 'QK832');
        $this->assertEqualsWithDelta(800, (float) PartnerTransaction::where('partner_id', $this->isiro->id)->where('loan_id', 832)->where('type', 'broker_fee_earned')->sum('amount'), 0.001);
    }

    public function test_past_loans_are_relinked_with_a_dry_run_first(): void
    {
        // an old ISIRO own loan, not brokered, already repaid: AC kept all 2,000 of the interest
        $loan = $this->approved(841, $this->isiro->user_id);
        $this->disburse($loan, ['broker_partner_id' => 0])->assertCreated();
        $this->pay(841, 12000, 'QK841');
        $this->assertEqualsWithDelta(2000, (float) PartnerTransaction::where('partner_id', $this->house->id)->where('loan_id', 841)->sum('amount'), 0.001);
        $count = PartnerTransaction::count();

        $this->artisan('partners:own-loans')->assertSuccessful();
        $this->assertSame($count, PartnerTransaction::count(), 'the dry run posts nothing');
        $this->actingAs($this->admin)->get(route('staff.loans.partners', ['loan' => 841, 'preview' => 1, 'broker' => $this->isiro->id, 'mode' => 'keep']))
            ->assertOk()->assertSee('ISIRO AGENCIES')->assertSee('+800.00');
        $this->assertSame(0, (int) Loan::findOrFail(841)->broker_status);
        $this->assertSame($count, PartnerTransaction::count(), 'the preview posts nothing');

        $this->actingAs($this->admin)->post(route('staff.loans.partners.apply', 841), ['broker' => (string) $this->isiro->id, 'mode' => 'keep'])->assertSessionHasErrors('confirm');
        $this->actingAs($this->admin)->post(route('staff.loans.partners.apply', 841), ['broker' => (string) $this->isiro->id, 'mode' => 'keep', 'confirm' => '1'])->assertRedirect();
        $this->assertEqualsWithDelta(800, (float) PartnerTransaction::where('partner_id', $this->isiro->id)->where('loan_id', 841)->sum('amount'), 0.001);
        $this->assertEqualsWithDelta(1200, (float) PartnerTransaction::where('partner_id', $this->house->id)->where('loan_id', 841)->sum('amount'), 0.001);

        // moving its cash onto a partner line records the money and re-splits the same way
        app(PartnerLinks::class)->apply(Loan::findOrFail(841), 0, null);
        $line = app(PartnerLines::class)->open($this->imagine, 10000, 'short_term', null, 'OLD0000841', '2026-10-20');
        app(PartnerLinks::class)->apply(Loan::findOrFail(841), null, $line->id);
        $this->assertEqualsWithDelta(800, (float) PartnerTransaction::where('partner_id', $this->imagine->id)->where('loan_id', 841)->sum('amount'), 0.001);
        $this->assertSame(10000.0, (float) InvestmentFunding::where('investment_id', $line->id)->value('amount_returned'));
        $this->assertSame('matured', $line->fresh()->status);
    }
}
