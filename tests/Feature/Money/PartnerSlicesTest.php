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

    /** partners:fund-loan books a partner's money for one loan as a line slice: dry run by default, idempotent (owner, 17 Nov 2026). */
    public function test_partner_money_for_one_loan_is_booked_as_a_line_slice(): void
    {
        $this->houseLoan(915);
        $this->artisan('partners:fund-loan', ['partner' => $this->buosti->id, 'loan' => 915, '--amount' => 35000, '--code' => 'QISIRO915'])->assertSuccessful();
        $this->assertSame(0, Investment::where('name', 'like', '%QISIRO915%')->count(), 'a dry run saves nothing');

        $this->artisan('partners:fund-loan', ['partner' => $this->buosti->id, 'loan' => 915, '--amount' => 35000, '--code' => 'QISIRO915', '--apply' => true])->assertSuccessful();
        $line = Investment::where('name', 'like', '%QISIRO915%')->firstOrFail();
        $this->assertSame([35000.0, 65000.0], DB::table('loan_fundings')->where('loan_id', 915)->orderBy('amount')->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertSame(35000.0, (float) DB::table('loan_fundings')->where('loan_id', 915)->where('investment_id', $line->id)->value('amount'));
        $this->assertSame(1, app(PartnerLines::class)->status($line)['loans']);

        $this->artisan('partners:fund-loan', ['partner' => $this->buosti->id, 'loan' => 915, '--amount' => 35000, '--code' => 'QISIRO915', '--apply' => true])->assertSuccessful();
        $this->assertSame(1, Investment::where('name', 'like', '%QISIRO915%')->count(), 'run twice, booked once');
        $this->actingAs($this->admin)->get(route('partners.show', $this->buosti))->assertOk()->assertSee('#915')->assertSee('35,000.00');
    }

    /** The partner took their part straight from the borrower's repayment (ISIRO on loan 547, owner, 17 Nov 2026): the closed line's payouts are recorded as made. */
    public function test_a_partner_paid_from_the_repayment_has_nothing_left_waiting(): void
    {
        $this->houseLoan(916);
        $this->pay(916, 120000, 'QK916', '2026-10-23');
        $args = ['partner' => $this->buosti->id, 'loan' => 916, '--amount' => 35000, '--code' => 'QISIRO916', '--paid-on' => '2026-10-23', '--paid-ref' => 'QK916', '--by' => $this->admin->id];
        $this->artisan('partners:fund-loan', $args + ['--apply' => true])->assertSuccessful();

        $line = Investment::where('name', 'like', '%QISIRO916%')->firstOrFail();
        $this->assertSame('matured', $line->status);
        $reqs = DB::table('partner_requests')->where('partner_id', $this->buosti->id)->where('type', 'withdrawal')->get();
        $this->assertNotEmpty($reqs);
        $this->assertSame(['done'], $reqs->pluck('status')->unique()->values()->all(), 'nothing left waiting on Requests');
        $this->assertSame(-35000.0, (float) DB::table('partner_transactions')->where('partner_id', $this->buosti->id)->where('wallet_slug', 'principal')->where('type', 'withdrawal')->sum('amount'));
        $this->assertSame(0.0, round((float) DB::table('partner_transactions')->where('partner_id', $this->buosti->id)->where('wallet_slug', 'principal')->sum('amount'), 2), 'their capital went back out');

        $this->artisan('partners:fund-loan', $args + ['--apply' => true])->assertSuccessful();
        $this->assertSame($reqs->count(), DB::table('partner_requests')->where('partner_id', $this->buosti->id)->where('type', 'withdrawal')->count(), 'run twice, paid once');
    }

    /** The same from the partner page's "Their money in a loan" sheet: new money, already paid back from the repayment (owner, 17 Nov 2026). */
    public function test_the_money_in_a_loan_sheet_books_new_money_already_paid_back(): void
    {
        $this->houseLoan(917);
        $this->pay(917, 120000, 'QK917', '2026-10-23');
        $show = route('partners.show', $this->buosti);
        $form = ['_sheet' => 'part-of-loan', 'source' => 'new', 'loan' => 917, 'amount' => 35000, 'code' => 'qisiro917', 'paid' => 1, 'paid_on' => '2026-10-23', 'paid_ref' => 'QK917'];

        $this->actingAs($this->admin)->get($show)->assertOk()->assertSee('Money in a loan…')->assertSee('New money for this loan')->assertSee('They already got their part back');
        $this->actingAs($this->admin)->from($show)->followingRedirects()->post(route('staff.partners.slices.fund', $this->buosti), $form + ['mode' => 'preview'])
            ->assertOk()->assertSee('Preview: the journal rows this would post')->assertSee('MPESA-QISIRO917');
        $this->assertSame(0, Investment::where('name', 'like', '%QISIRO917%')->count(), 'preview saves nothing');

        $this->actingAs($this->admin)->from($show)->post(route('staff.partners.slices.fund', $this->buosti), $form + ['mode' => 'save'])->assertSessionHas('success');
        $this->assertSame(35000.0, (float) DB::table('loan_fundings')->where('loan_id', 917)->where('partner_id', $this->buosti->id)->value('amount'));
        $this->assertSame(0, DB::table('partner_requests')->where('partner_id', $this->buosti->id)->whereIn('status', ['pending', 'prepared'])->count(), 'nothing waits on Partner Payouts');

        $this->actingAs($this->admin)->from($show)->post(route('staff.partners.slices.fund', $this->buosti), $form + ['mode' => 'save'])->assertSessionHas('error');
        $this->assertSame(1, Investment::where('name', 'like', '%QISIRO917%')->count(), 'saved twice, booked once');
    }

    /** A partner's own share of a loan with no line (ISIRO's 44,000 on facility 458) shows on the Investments tab (owner, 17 Nov 2026). */
    public function test_money_in_a_loan_without_a_line_shows_on_the_investments_tab(): void
    {
        $this->houseLoan(914);
        $d = DB::table('disbursements')->where('loan_id', 914)->value('id');
        DB::table('loan_fundings')->insert(['loan_id' => 914, 'investment_id' => null, 'partner_id' => $this->buosti->id, 'amount' => 44000, 'share' => 0.18,
            'disbursement_id' => $d, 'source' => 'slices', 'notes' => 'their share, no line', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->get(route('partners.show', $this->buosti))->assertOk()
            ->assertSee('Money in a loan, not on a line')->assertSee('#914')->assertSee('44,000.00')->assertSee('18%');
    }

    /** A line that paid for a loan alone, in cash (no loan_fundings rows), lists that loan when opened (live, 17 Nov 2026). */
    public function test_a_line_that_paid_for_a_loan_alone_lists_it(): void
    {
        $line = $this->line();
        $this->loanRow(913, 1, 15000, 15000, '2026-10-13', '2026-10-23', 'approved', 1, 30);
        $this->cycleRow(913, 913, 1, 0, 3000, 18000, 20, '2026-10-13', '2026-10-23', 'active', 'Initial');
        $this->actingAs($this->admin)->postJson(route('disbursements.store'), ['loan_id' => 913, 'amount' => 15000, 'disburse_date' => '2026-10-13',
            'transaction' => 'QD913', 'mode' => 'mpesa', 'funding_mode' => 'line', 'investment_id' => $line->id])->assertCreated();
        $this->assertSame(0, DB::table('loan_fundings')->where('loan_id', 913)->count(), 'paid for by one line: no slice rows');
        $this->assertSame(1, app(PartnerLines::class)->status($line->refresh())['loans']);

        $this->actingAs($this->admin)->get(route('partners.show', $this->buosti))->assertOk()
            ->assertSee('#913')->assertSee('QD913')->assertSee('15,000.00')->assertSee('Out on the loan')
            ->assertDontSee('This line has not funded a loan yet.');
    }

    /** The partner page (owner, 16 Nov 2026): every form sits in a right-side sheet, and a line lists the loans it funded. */
    public function test_the_partner_page_keeps_forms_in_sheets_and_shows_what_each_line_funded(): void
    {
        $this->houseLoan(912);
        $line = $this->line();
        $d = DB::table('disbursements')->where('loan_id', 912)->value('id');
        $show = route('partners.show', $this->buosti);

        // Preview from the "part of a loan" sheet comes back to the same sheet, with the journal rows in it
        $this->actingAs($this->admin)->from($show)->followingRedirects()
            ->post(route('staff.partners.slices.fund', $this->buosti), ['_sheet' => 'part-of-loan', 'line' => $line->id, 'loan' => 912, 'disbursement' => $d, 'amount' => 20000, 'mode' => 'preview'])
            ->assertOk()->assertSee('Preview: the journal rows this would post');
        $this->actingAs($this->admin)->post(route('staff.partners.slices.fund', $this->buosti), ['line' => $line->id, 'loan' => 912, 'disbursement' => $d, 'amount' => 20000, 'mode' => 'save'])
            ->assertSessionHas('success');
        $borrower = DB::table('users')->where('id', Loan::findOrFail(912)->user_id)->value('name');
        $slice = DB::table('loan_fundings')->where('loan_id', 912)->where('investment_id', $line->id)->value('id');

        $this->actingAs($this->admin)->get($show)->assertOk()
            ->assertSee('data-sheet="record-money"', false)->assertSee('data-sheet="part-of-loan"', false)->assertSee('data-sheet="settle-' . $slice . '"', false)
            ->assertSee('Investments')->assertSee('Clients and own loans')
            ->assertSee('#912')->assertSee($borrower)->assertSee('Out on the loan')
            ->assertDontSee('partners:own-loans');

        // paid in full: the line says so, how much the borrower paid and when, and their share
        $this->pay(912, 120000, 'QK912', '2026-10-23');
        $this->actingAs($this->admin)->get($show)->assertOk()
            ->assertSee('Paid back')->assertSee('120,000.00')->assertSee('23 Oct 2026')->assertSee('2,400.00')
            ->assertDontSee('data-sheet="settle-' . $slice . '"', false);

        $this->actingAs($this->admin)->get(route('partners.index'))->assertOk()
            ->assertSee('Funding lines')->assertSee('Introducers')->assertSee('BUOSTI AGENCY');
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
