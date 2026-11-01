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
use App\Services\Loans\LoanFundings;
use App\Services\Loans\RepaymentSplit;
use App\Services\Partners\PartnerLines;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Part funding (owner, 1 Nov 2026): one loan funded from several sources. Each funder's slice of a repayment's
 * interest and principal follows its share of the amount sent; the penalty share stays whole; the house takes any
 * rounding cent. Past loans are frozen at their single funder and split exactly as before.
 */
class PartFundingTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $admin;

    private Partner $isiro;

    private Partner $imagine;

    private Partner $house;

    private Investment $houseLine;

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
        $this->houseLine = Investment::create(['name' => 'General Lending Book', 'type' => 'other', 'facility_type' => 'long_term', 'country' => 'Kenya',
            'initial_amount' => 0, 'purchase_date' => '2026-01-01', 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** An approved 70,000 loan at 20% (10 days): 14,000 interest. */
    private function approved(int $id, float $amount = 70000): Loan
    {
        $this->loanRow($id, 1, $amount, $amount, '2026-10-23', '2026-11-02', 'approved', 1, 30);
        $this->cycleRow($id, $id, 1, 0, $amount * 0.2, $amount * 1.2, 20, '2026-10-23', '2026-11-02', 'active', 'Initial');

        return Loan::findOrFail($id);
    }

    private function disburse(Loan $loan, array $fundings, int $broker, float $amount = 70000)
    {
        return $this->actingAs($this->admin)->postJson(route('disbursements.store'), ['loan_id' => $loan->id, 'amount' => $amount,
            'disburse_date' => '2026-10-23', 'transaction' => 'QD' . $loan->id, 'mode' => 'mpesa', 'broker_partner_id' => $broker,
            'funding_mode' => 'split', 'fundings' => array_map(fn ($f) => ['investment_id' => $f[0], 'amount' => $f[1]], $fundings)]);
    }

    private function pay(int $loanId, float $amount, string $ref, string $date = '2026-10-30'): Repayment
    {
        if (now()->lt($date . ' 12:00')) {
            Carbon::setTestNow($date . ' 12:00');
        }

        return app(\App\Services\RepaymentService::class)->createRepayment(Loan::findOrFail($loanId),
            ['amount' => $amount, 'repayment_date' => $date, 'transaction' => $ref, 'mode' => 'mpesa'], LoanCycle::findOrFail($loanId));
    }

    private function earned(int $partnerId, int $loanId, ?string $type = null): float
    {
        return round((float) PartnerTransaction::where('partner_id', $partnerId)->where('loan_id', $loanId)
            ->when($type, fn ($q) => $q->where('type', $type))->sum('amount'), 2);
    }

    private function isiroLine(float $amount = 35000): Investment
    {
        return app(PartnerLines::class)->open($this->isiro, $amount, 'short_term', '2026-11-30', 'ISIRO' . (int) $amount, '2026-10-22', $this->admin);
    }

    public function test_house_and_the_broker_fund_half_each_and_split_pro_rata(): void
    {
        $line = $this->isiroLine();
        $this->disburse($this->approved(901), [[$this->houseLine->id, 35000], [$line->id, 35000]], $this->isiro->id)->assertCreated();

        $this->assertSame(2, DB::table('loan_fundings')->where('loan_id', 901)->count());
        $this->assertSame(1, \App\Models\Disbursement::where('loan_id', 901)->count(), 'one disbursement, one cycle-1 principal');
        $this->assertSame(35000.0, (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->isiro->id)->value('amount_disbursed'));
        $this->assertSame(70000.0, (float) LoanCycle::findOrFail(901)->lentPrincipal());

        // the owner's worked example: 14,000 interest, 1,000 penalty
        $shares = app(RepaymentSplit::class)->shares(Loan::findOrFail(901), ['penalty' => 1000, 'interest' => 14000, 'principal' => 70000]);
        $sum = fn ($p, $t) => round(array_sum(array_map(fn ($s) => $s[3], array_filter($shares, fn ($s) => $s[0] === $p && $s[1] === $t))), 2);
        $this->assertSame(7000.0, $sum($this->isiro->id, 'broker_fee_earned'));      // 2,800 (40% of house half) + 4,200 (60% of own half)
        $this->assertSame(500.0, $sum($this->isiro->id, 'penalty_share'));           // 50% of the whole penalty
        $this->assertSame(7000.0, $sum($this->house->id, 'profit_distribution'));
        $this->assertSame(500.0, $sum($this->house->id, 'penalty_share'));

        // the Receive payment preview shows each funder's slice
        $pv = app(RepaymentSplit::class)->preview(LoanCycle::findOrFail(901), 84000, '2026-10-23');
        $this->assertSame([[35000.0, 50.0, 7000.0, 35000.0], [35000.0, 50.0, 7000.0, 35000.0]],
            array_map(fn ($f) => [$f['amount'], $f['pct'], $f['interest'], $f['principal']], $pv['funders']));

        // repaid in full on time: shares posted, Isiro's line gets its half of the principal back
        $this->pay(901, 84000, 'QK901');
        $this->assertSame(7000.0, $this->earned($this->isiro->id, 901, 'broker_fee_earned'));
        $this->assertSame(7000.0, $this->earned($this->house->id, 901, 'profit_distribution'));
        $this->assertSame(35000.0, (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->isiro->id)->value('amount_returned'));
        $this->assertEqualsWithDelta(7000, (float) $this->isiro->getWallet('broker_fee')->refresh()->balanceFloat, 0.001);

        // the partner portal lists the loan under Isiro's line
        $this->assertTrue(app(\App\Services\PartnerAccount::class)->fundedLoans($this->isiro, $line->id)->whereKey(901)->exists());
        $this->assertContains(901, app(PartnerLines::class)->loanIds($line)->map(fn ($id) => (int) $id)->all());
    }

    public function test_a_partial_payment_splits_to_the_cent_and_a_reversal_moves_it_back_exactly(): void
    {
        $line = $this->isiroLine();
        $this->disburse($this->approved(902), [[$this->houseLine->id, 35000], [$line->id, 35000]], $this->isiro->id)->assertCreated();

        $r = $this->pay(902, 3333.33, 'QK902A');                 // all interest
        $this->assertSame(1666.66, $this->earned($this->isiro->id, 902));   // 40% of 1,666.66 + 60% of 1,666.67
        $this->assertSame(1666.67, $this->earned($this->house->id, 902));
        $this->assertSame(3333.33, round($this->earned($this->isiro->id, 902) + $this->earned($this->house->id, 902), 2));

        $r2 = $this->pay(902, 20000, 'QK902B');                  // 10,666.67 interest, 9,333.33 principal
        $this->assertSame(round(9333.33 / 2, 2), (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->isiro->id)->value('amount_returned'));
        // all 14,000 interest now paid: each payment rounds its slices, the cent goes to the house, the total is exact
        $this->assertEqualsWithDelta(7000, $this->earned($this->isiro->id, 902), 0.011);
        $this->assertSame(14000.0, round($this->earned($this->isiro->id, 902) + $this->earned($this->house->id, 902), 2));

        // reverse (delete) the second payment: shares and principal go back to what the first one gave
        DB::transaction(fn () => $r2->delete());
        $this->assertSame(1666.66, $this->earned($this->isiro->id, 902));
        $this->assertSame(1666.67, $this->earned($this->house->id, 902));
        $this->assertSame(0.0, (float) InvestmentFunding::where('investment_id', $line->id)->where('partner_id', $this->isiro->id)->value('amount_returned'));

        // and editing the first one moves it by exactly the change
        DB::transaction(fn () => $r->update(['amount' => 4000]));
        $this->assertSame(2000.0, $this->earned($this->isiro->id, 902));
        $this->assertSame(2000.0, $this->earned($this->house->id, 902));
    }

    public function test_a_non_broker_partner_and_the_house_split_by_what_each_put_in(): void
    {
        $line = app(PartnerLines::class)->open($this->imagine, 30000, 'short_term', '2026-11-30', 'IMAG30000', '2026-10-22', $this->admin);
        $this->disburse($this->approved(903), [[$line->id, 30000], [$this->houseLine->id, 40000]], 0)->assertCreated();

        $this->pay(903, 84000, 'QK903');
        $this->assertSame(2400.0, $this->earned($this->imagine->id, 903, 'profit_distribution'));   // 40% of 3/7 of 14,000
        $this->assertSame(11600.0, $this->earned($this->house->id, 903, 'profit_distribution'));
        $this->assertSame(30000.0, (float) InvestmentFunding::where('investment_id', $line->id)->value('amount_returned'));
        $this->assertSame('matured', $line->fresh()->status, 'the line closes with its loan');
    }

    public function test_the_lines_must_add_up_and_each_line_is_capped(): void
    {
        $line = $this->isiroLine(20000);
        $this->disburse($this->approved(904), [[$this->houseLine->id, 35000], [$line->id, 30000]], 0)
            ->assertStatus(422)->assertJsonValidationErrors('fundings');
        $this->disburse($this->approved(905), [[$this->houseLine->id, 35000], [$line->id, 35000]], 0)
            ->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->assertSame(0, \App\Models\Disbursement::whereIn('loan_id', [904, 905])->count());
        $this->assertSame(0, DB::table('loan_fundings')->count());
    }

    /** A loan as it stands in the books today: cash from one line (or none), not part funded. */
    private function pastLoan(int $id, bool $brokered, ?int $lineId): void
    {
        $this->loanRow($id, 1, 10000, 10000, '2026-09-30', '2026-10-10', 'disbursed', 1, 30);
        DB::table('loans')->where('id', $id)->update(['broker_status' => (int) $brokered, 'partner_id' => $brokered ? $this->isiro->id : null,
            'broker_rate' => 40, 'broker_penalty_rate' => $brokered ? 50 : null]);
        $this->cycleRow($id, $id, 1, 0, 2000, 12000, 20, '2026-09-30', '2026-10-10', 'active', 'Initial');
        DB::table('disbursements')->insert(['loan_id' => $id, 'loan_cycle_id' => $id, 'amount' => 10000, 'net_amount' => 10000, 'transaction' => 'QD' . $id,
            'kind' => 'cash', 'mode' => 'mpesa', 'disburse_date' => '2026-09-30', 'payment_date' => '2026-09-30', 'investment_id' => $lineId]);
    }

    private function line(Partner $p, string $returnType): Investment
    {
        $line = Investment::create(['name' => $p->name . ' ' . $returnType, 'type' => 'other', 'facility_type' => 'long_term', 'return_type' => $returnType,
            'country' => 'Kenya', 'initial_amount' => 0, 'purchase_date' => '2026-09-01', 'status' => 'active', 'maturity_date' => '2027-06-30']);
        InvestmentFunding::create(['investment_id' => $line->id, 'partner_id' => $p->id, 'amount_committed' => 50000,
            'amount_disbursed' => 10000, 'amount_returned' => 0, 'status' => 'active']);

        return $line;
    }

    private function journal(): array
    {
        return PartnerTransaction::selectRaw('partner_id, loan_id, type, wallet_slug, round(sum(amount), 2) as total')
            ->groupBy('partner_id', 'loan_id', 'type', 'wallet_slug')->orderBy('loan_id')->orderBy('partner_id')->orderBy('type')->orderBy('wallet_slug')
            ->get()->map(fn ($r) => "{$r->partner_id}|{$r->loan_id}|{$r->type}|{$r->wallet_slug}|" . number_format((float) $r->total, 2, '.', ''))->all();
    }

    public function test_past_single_funder_loans_are_frozen_by_the_backfill_and_split_exactly_as_before(): void
    {
        Carbon::setTestNow('2026-10-21 10:00');
        $share = $this->line($this->imagine, 'loan_share');
        $fixed = $this->line($this->imagine, 'fixed_rate');
        $own = $this->line($this->isiro, 'loan_share');
        $this->pastLoan(911, true, $this->houseLine->id);   // house funded, brokered by Isiro
        $this->pastLoan(912, false, $share->id);            // partner loan_share
        $this->pastLoan(913, false, $fixed->id);            // partner fixed_rate
        $this->pastLoan(914, true, $own->id);               // brokered, broker's own line
        $this->pastLoan(915, false, null);                  // no line recorded (house)
        $pay = fn ($id, $amount, $date, $ref) => DB::transaction(fn () => Repayment::create(['loan_id' => $id, 'loan_cycle_id' => $id,
            'amount' => $amount, 'repayment_date' => $date, 'transaction' => $ref]));
        $edit = [];
        foreach ([911, 912, 913, 914, 915] as $id) {
            $edit[$id] = $pay($id, 7000, '2026-10-11', 'QA' . $id);    // late: penalty, interest, some principal
            $pay($id, 3333.33, '2026-10-15', 'QB' . $id);
        }
        $split = app(RepaymentSplit::class);
        $plans = fn () => Repayment::whereIn('loan_id', [911, 912, 913, 914, 915])->orderBy('id')->get()->map(fn ($r) => $split->plan($r))->all();
        $returns = fn () => InvestmentFunding::orderBy('id')->pluck('amount_returned', 'id')->map(fn ($v) => (float) $v)->all();
        [$journalBefore, $plansBefore, $returnsBefore, $rows] = [$this->journal(), $plans(), $returns(), PartnerTransaction::count()];

        $this->artisan('loans:backfill-fundings')->assertSuccessful();
        $this->assertSame(0, DB::table('loan_fundings')->count(), 'the dry run writes nothing');
        $this->artisan('loans:backfill-fundings', ['--apply' => true])->assertSuccessful();
        $this->assertSame(5, DB::table('loan_fundings')->where('source', LoanFundings::BACKFILL)->count());
        $this->artisan('loans:backfill-fundings', ['--apply' => true])->assertSuccessful();
        $this->assertSame(5, DB::table('loan_fundings')->count(), 're-running adds nothing');
        $this->assertSame(1.0, (float) DB::table('loan_fundings')->where('loan_id', 912)->value('share'));
        $this->assertSame($this->imagine->id, (int) DB::table('loan_fundings')->where('loan_id', 912)->value('partner_id'));

        // the same shares to the cent, and re-syncing posts nothing
        $this->assertSame($plansBefore, $plans());
        foreach ([911, 912, 913, 914, 915] as $id) {
            $split->syncCycle($id);
        }
        $this->assertSame($rows, PartnerTransaction::count());
        $this->assertSame($journalBefore, $this->journal());
        $this->assertSame($returnsBefore, $returns());

        // editing a repayment on a frozen loan moves the shares exactly as it would have without the rows
        foreach ($edit as $r) {
            DB::transaction(fn () => $r->update(['amount' => 9000]));
        }
        $journalAfterEdit = [$this->journal(), $returns()];
        DB::table('loan_fundings')->delete();
        $count = PartnerTransaction::count();
        foreach ([911, 912, 913, 914, 915] as $id) {
            $split->syncCycle($id);
        }
        $this->assertSame($count, PartnerTransaction::count(), 'without the frozen rows the split wants the same journal');
        $this->assertSame($journalAfterEdit, [$this->journal(), $returns()]);
        $this->assertNotSame($journalBefore, $journalAfterEdit[0], 'the edit did move shares');
    }

    public function test_a_single_funder_loan_disbursed_today_is_unchanged(): void
    {
        $line = app(PartnerLines::class)->open($this->imagine, 10000, 'short_term', '2026-11-30', 'IMAG10000', '2026-10-22', $this->admin);
        $loan = $this->approved(921, 10000);
        // one funding line = today's single-line disbursement, no loan_fundings rows
        $this->disburse($loan, [[$line->id, 10000]], 0, 10000)->assertCreated();
        $this->assertSame(0, DB::table('loan_fundings')->count());
        $this->assertSame($line->id, (int) \App\Models\Disbursement::where('loan_id', 921)->value('investment_id'));
        $this->pay(921, 12000, 'QK921');
        $this->assertSame(800.0, $this->earned($this->imagine->id, 921, 'profit_distribution'));
        $this->assertSame(1200.0, $this->earned($this->house->id, 921, 'profit_distribution'));
    }
}
