<?php

namespace Tests\Feature\Money;

use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Models\User;
use App\Services\Partners\PartnerLines;
use App\Services\Partners\PartnerSlices;
use App\Services\Requests;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Partner money recorded the way it happened (owner, 3 Nov 2026): a line's own share of the interest (60%) and the
 * penalty (50%), a slice of a house loan paid for by a line, the house settling a partner's slice before the borrower
 * repaid, and a payout recorded after it was made.
 */
class PartnerSlicesTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $admin;

    private Partner $buosti;

    private Partner $house;

    private Investment $houseLine;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 10:00');
        config(['lending.engine' => 'v2', 'lending.split.enabled' => true, 'lending.ledger.enabled' => false, 'lending.penalty.cap' => 'none',
            'lending.approvals.enabled' => false]);
        $this->seed(RoleSeeder::class);
        $this->loanTypes();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(['create disbursements', 'edit partners']);
        $this->buosti = Partner::create(['name' => 'BUOSTI AGENCY', 'email' => 'b@example.test', 'type' => 'business', 'account_type' => 'principal', 'profit_share_rate' => 0]);
        $this->house = Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        $this->houseLine = Investment::create(['name' => 'General Lending Book', 'type' => 'other', 'facility_type' => 'long_term', 'country' => 'Kenya',
            'initial_amount' => 0, 'purchase_date' => '2026-01-01', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A house loan of 100,000 at 20%, disbursed on 13 Oct. */
    private function houseLoan(int $id): Loan
    {
        $this->loanRow($id, 1, 100000, 100000, '2026-10-13', '2026-10-23', 'approved', 1, 30);
        $this->cycleRow($id, $id, 1, 0, 20000, 120000, 20, '2026-10-13', '2026-10-23', 'active', 'Initial');
        $this->actingAs($this->admin)->postJson(route('disbursements.store'), ['loan_id' => $id, 'amount' => 100000, 'disburse_date' => '2026-10-13',
            'transaction' => 'QD' . $id, 'mode' => 'mpesa', 'funding_mode' => 'split', 'fundings' => [['investment_id' => $this->houseLine->id, 'amount' => 100000]]])->assertCreated();

        return Loan::findOrFail($id);
    }

    private function pay(int $loanId, float $amount, string $ref, string $date): Repayment
    {
        Carbon::setTestNow($date . ' 12:00');

        return app(\App\Services\RepaymentService::class)->createRepayment(Loan::findOrFail($loanId),
            ['amount' => $amount, 'repayment_date' => $date, 'transaction' => $ref, 'mode' => 'mpesa'], LoanCycle::findOrFail($loanId));
    }

    private function line(): Investment
    {
        return app(PartnerLines::class)->open($this->buosti, 20000, 'short_term', null, 'UJDJAACF19', '2026-10-13', $this->admin);
    }

    private function earned(int $partnerId, int $loanId, ?string $type = null): float
    {
        return round((float) PartnerTransaction::where('partner_id', $partnerId)->where('loan_id', $loanId)->when($type, fn ($q) => $q->where('type', $type))->sum('amount'), 2);
    }

    public function test_a_line_defaults_to_60_percent_of_interest_and_50_of_penalty(): void
    {
        $line = $this->line();
        $this->assertSame(60.0, (float) $line->partner_share_rate);
        $this->assertSame(50.0, (float) $line->partner_penalty_rate);
        $this->assertSame('2026-10-13', substr((string) PartnerTransaction::where('partner_id', $this->buosti->id)->where('type', 'contribution')->value('transaction_date'), 0, 10));
    }

    public function test_a_line_paying_for_part_of_a_house_loan_earns_its_share_of_that_part(): void
    {
        $loan = $this->houseLoan(910);
        $line = $this->line();
        $d = DB::table('disbursements')->where('loan_id', 910)->value('id');

        // preview posts nothing
        $before = DB::table('loan_fundings')->where('loan_id', 910)->get()->toArray();
        $this->actingAs($this->admin)->post(route('staff.partners.slices.fund', $this->buosti), ['line' => $line->id, 'loan' => 910, 'disbursement' => $d, 'amount' => 20000, 'mode' => 'preview'])
            ->assertSessionHas('slice_preview');
        $this->assertEquals($before, DB::table('loan_fundings')->where('loan_id', 910)->get()->toArray());

        $this->actingAs($this->admin)->post(route('staff.partners.slices.fund', $this->buosti), ['line' => $line->id, 'loan' => 910, 'disbursement' => $d, 'amount' => 20000, 'mode' => 'save'])
            ->assertSessionHas('success');
        $this->assertSame([80000.0, 20000.0], DB::table('loan_fundings')->where('loan_id', 910)->orderBy('amount', 'desc')->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertSame(20000.0, (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->buosti->id)->value('amount_disbursed'));

        // repaid in full: 20,000 interest; Buosti's part is a fifth, 60% of it = 2,400
        $this->pay(910, 120000, 'QK910', '2026-10-23');
        $this->assertSame(2400.0, $this->earned($this->buosti->id, 910));
        $this->assertSame(17600.0, $this->earned($this->house->id, 910));
        $this->assertSame(20000.0, (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->buosti->id)->value('amount_returned'));
    }

    public function test_the_house_settles_a_slice_and_later_repayments_share_nothing_with_the_partner(): void
    {
        $loan = $this->houseLoan(911);
        $line = $this->line();
        $d = DB::table('disbursements')->where('loan_id', 911)->value('id');
        app(PartnerSlices::class)->slices($loan, [['line' => $this->houseLine->id, 'amount' => 80000, 'disbursement' => null], ['line' => $line->id, 'amount' => 20000, 'disbursement' => $d]], $this->admin);
        $row = DB::table('loan_fundings')->where('loan_id', 911)->where('investment_id', $line->id)->first();
        $this->assertSame(2400.0, app(PartnerSlices::class)->shareDue($row));

        Carbon::setTestNow('2026-10-20 12:00');
        app(PartnerSlices::class)->settleByHouse($row->id, '2026-10-20', null, $this->admin);
        $this->assertSame(2400.0, $this->earned($this->buosti->id, 911));
        $this->assertSame('2026-10-20', substr((string) PartnerTransaction::where('reference', 'SETTLE-LF' . $row->id)->value('transaction_date'), 0, 10));
        $this->assertSame(20000.0, (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->buosti->id)->value('amount_returned'));

        // settling twice is refused; the borrower pays later: all the interest is the house's
        $this->expectExceptionObject(new \DomainException('The house settled that slice on 20 Oct 2026.'));
        try {
            $this->pay(911, 120000, 'QK911', '2026-10-23');
            $this->assertSame(2400.0, $this->earned($this->buosti->id, 911));
            $this->assertSame(20000.0, $this->earned($this->house->id, 911));
        } finally {
            app(PartnerSlices::class)->settleByHouse($row->id, '2026-10-23', null, $this->admin);
        }
    }

    public function test_a_payout_already_made_is_recorded_on_the_day_it_was_paid(): void
    {
        Carbon::setTestNow('2026-11-03 10:00');
        $this->buosti->addContribution(20000, 'MPESA-UJK6OAKZ64', 'money back on the account', null, null, '2026-10-20');
        $id = app(Requests::class)->recordPaidPayout($this->buosti, 'principal', 20000, '2026-10-30', '019605362653', $this->admin);
        $r = DB::table('partner_requests')->find($id);
        $this->assertSame('2026-10-30', substr((string) $r->paid_on, 0, 10));
        $this->assertSame('019605362653', $r->bank_reference);

        // the same transfer can't be recorded twice for the same wallet
        $this->expectException(\DomainException::class);
        app(Requests::class)->recordPaidPayout($this->buosti, 'principal', 20000, '2026-10-30', '019605362653', $this->admin);
    }

    public function test_a_payout_already_made_posts_without_a_second_sign_off(): void
    {
        config(['lending.approvals.enabled' => true]);
        Carbon::setTestNow('2026-11-03 10:00');
        $this->buosti->addContribution(20000, 'MPESA-UJK6OAKZ64', 'money back on the account', null, null, '2026-10-20');
        $id = app(Requests::class)->recordPaidPayout($this->buosti, 'principal', 20000, '2026-10-30', '019605362653', $this->admin);

        $this->assertSame(0, DB::table('approvals')->where('subject_id', $id)->count());
        $this->assertSame('done', DB::table('partner_requests')->where('id', $id)->value('status'));
        $this->assertSame(0.0, round((float) $this->buosti->getWallet('principal')->refresh()->balanceFloat, 2));
        $this->assertSame('2026-10-30', substr((string) PartnerTransaction::where('partner_id', $this->buosti->id)->where('type', 'withdrawal')->value('transaction_date'), 0, 10));
    }

    public function test_partners_check_reconciles_a_clean_book(): void
    {
        $this->houseLoan(912);
        $this->pay(912, 120000, 'QK912', '2026-10-23');
        $this->artisan('partners:check')->expectsOutputToContain('Partner money reconciles.')->assertSuccessful();
    }
}
