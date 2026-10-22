<?php

namespace Tests\Feature\Money;

use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\LoanBalancer;
use App\Services\Loans\LoanRepair;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Owner, 22 Oct 2026: balance every loan; no "add the extra"; no penalty days the cash did not pay; closed loans end at 0. */
class LoanBalancerTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        Carbon::setTestNow('2026-10-21 18:00');
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cash(int $loanId, ?int $cycleId, float $amount, string $date, ?int $id = null): void
    {
        DB::table('disbursements')->insert(array_filter(['id' => $id, 'loan_id' => $loanId, 'loan_cycle_id' => $cycleId, 'amount' => $amount, 'net_amount' => $amount,
            'transaction' => 'CASH' . $amount . $date, 'mode' => 'mpesa', 'kind' => 'cash', 'disburse_date' => $date, 'payment_date' => $date,
            'created_at' => $date, 'updated_at' => $date], fn ($v) => $v !== null));
    }

    private function owed(int $loanId): float
    {
        return app(LoanRepair::class)->owedFor($loanId);
    }

    public function test_cash_above_principal_and_interest_on_a_late_loan_becomes_penalty_days_and_the_loan_ends_at_exactly_0(): void
    {
        // 10,000 at 20% due 11 Jan, paid 12,700 on 20 Jan (9 days late): the 700 is penalty for the days it covers
        $this->loanRow(800, 1, 10000, 10000, '2026-01-01', '2026-01-11', 'repaid', 1, 0);
        $this->cycleRow(8001, 800, 1, 0, 2000, 12000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->cash(800, 8001, 10000, '2026-01-01');
        $this->repay(800, 8001, 12700, '2026-01-20', 'CASH', 8101);

        $r = app(LoanBalancer::class)->apply(800);

        $this->assertSame('balance', $r['action']);
        $this->assertMoney(0, $this->owed(800));
        $this->assertGreaterThan(0, (int) LoanCycle::findOrFail(8001)->grace_days, 'only the days nobody paid for became grace');
        $this->assertEqualsWithDelta(700, (float) CycleBalance::at(LoanCycle::findOrFail(8001))['penalty'], 1.0);
    }

    public function test_a_closed_loan_short_of_its_debt_gets_a_lower_rate_then_a_write_off_and_ends_at_0(): void
    {
        $this->loanRow(801, 1, 10000, 10000, '2026-01-01', '2026-01-11', 'repaid', 1, 0);
        $this->cycleRow(8011, 801, 1, 0, 2000, 12000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->cash(801, 8011, 10000, '2026-01-01');
        $this->repay(801, 8011, 9000, '2026-01-30', 'CASH', 8111);

        app(LoanBalancer::class)->apply(801);

        $this->assertMoney(0, $this->owed(801));
        $this->assertMoney(0, (float) LoanCycle::findOrFail(8011)->interest_rate, 'the cash did not cover even the principal');
        $this->assertMoney(1000, DB::table('repayments')->where('loan_id', 801)->where('kind', 'bad_debt')->value('amount'));
        $this->assertSame(0.0, (float) CycleBalance::at(LoanCycle::findOrFail(8011))['penalty']);
    }

    public function test_a_top_up_belongs_to_the_cycle_running_that_day_and_each_cycle_carries_what_the_one_before_owed(): void
    {
        // cycle 1 lent 30,000 (paid 3,000); cycle 2 booked 135,000 because 100,000 more was sent during it
        $this->loanRow(802, 1, 135000, 30000, '2026-01-01', '2026-01-21', 'disbursed', 2, 0);
        $this->cycleRow(8021, 802, 1, 0, 6000, 36000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->cycleRow(8022, 802, 2, 135000, 27000, 162000, 20, '2026-01-11', '2026-10-30', 'active', 'Rollover');
        $this->cash(802, 8021, 30000, '2026-01-01');
        $this->cash(802, 8021, 100000, '2026-01-15');
        $this->repay(802, 8021, 3000, '2026-01-11', 'CASH', 8121);

        app(LoanBalancer::class)->apply(802);

        $this->assertMoney(30000, LoanCycle::findOrFail(8021)->lentPrincipal(), 'cycle 1 stays as lent');
        $this->assertSame(8022, (int) DB::table('disbursements')->where('amount', 100000)->value('loan_cycle_id'), 'sent on 15 Jan: cycle 2');
        $this->assertMoney(133000, LoanCycle::findOrFail(8022)->lentPrincipal(), '36,000 - 3,000 still owed, + the 100,000 sent');
        $this->assertSame(0.0, (float) CycleBalance::at(LoanCycle::findOrFail(8021))['penalty']);
    }

    public function test_a_loan_lent_more_than_was_recorded_going_out_gets_the_missing_disbursement(): void
    {
        $this->loanRow(803, 1, 100000, 100000, '2025-10-24', '2025-11-03', 'repaid', 1, 0);
        $this->cycleRow(8031, 803, 1, 0, 20000, 120000, 20, '2025-10-24', '2025-11-03', 'completed', 'Initial');
        $this->cash(803, 8031, 10000, '2025-10-24');
        $this->repay(803, 8031, 120000, '2025-10-31', 'CASH', 8131);

        $r = app(LoanBalancer::class)->apply(803);

        $this->assertStringContainsString('recorded the missing 90,000.00', $r['steps']);
        $this->assertMoney(100000, DB::table('disbursements')->where('loan_id', 803)->whereNull('deleted_at')->sum('amount'));
        $this->assertMoney(0, $this->owed(803));
    }

    public function test_a_write_off_bigger_than_the_debt_is_cut_before_any_penalty_and_agreed_loans_are_left_alone(): void
    {
        $this->loanRow(804, 1, 50000, 50000, '2026-02-28', '2026-03-10', 'repaid', 1, 0);
        $this->cycleRow(8041, 804, 1, 0, 10000, 60000, 20, '2026-02-28', '2026-03-10', 'completed', 'Initial');
        $this->cash(804, 8041, 50000, '2026-02-28');
        $this->repay(804, 8041, 60000, '2026-03-10', 'CASH', 8141);
        DB::table('repayments')->insert(['id' => 8142, 'loan_id' => 804, 'loan_cycle_id' => 8041, 'amount' => 578000, 'repayment_date' => '2026-03-10',
            'transaction' => 'BAD DEBT', 'kind' => 'bad_debt', 'mode' => 'book', 'created_at' => now(), 'updated_at' => now()]);

        app(LoanBalancer::class)->apply(804);
        $this->assertNotNull(DB::table('repayments')->where('id', 8142)->value('deleted_at'), 'the whole write-off was credit');
        $this->assertMoney(0, $this->owed(804));

        $this->loanRow(805, 1, 10000, 10000, '2026-01-01', '2026-01-11', 'forbearance', 1, 0);
        $this->cycleRow(8051, 805, 1, 0, 2000, 12000, 20, '2026-01-01', '2026-01-11', 'active', 'Initial');
        DB::table('loan_cycles')->where('id', 8051)->update(['penalty_waived_until' => '2026-12-05']);
        $this->assertContains(app(LoanBalancer::class)->preview(805)['action'], ['skip', 'rebook']);
    }

    public function test_an_overpayment_moves_to_the_borrowers_other_loan_by_splitting_the_cash_row(): void
    {
        $this->loanRow(806, 1, 10000, 10000, '2026-01-01', '2026-01-11', 'repaid', 1, 0);
        $this->cycleRow(8061, 806, 1, 0, 2000, 12000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->repay(806, 8061, 15000, '2026-01-11', 'CASH', 8161);
        $this->loanRow(807, 1, 20000, 20000, '2026-01-05', '2026-10-30', 'disbursed', 1, 0);
        $this->cycleRow(8071, 807, 1, 0, 4000, 24000, 20, '2026-01-05', '2026-10-30', 'active', 'Initial');
        DB::table('loans')->where('id', 807)->update(['user_id' => DB::table('loans')->where('id', 806)->value('user_id')]);

        app(LoanRepair::class)->moveOverpayment(806, 807, 3000);

        $this->assertMoney(12000, DB::table('repayments')->where('id', 8161)->value('amount'));
        $this->assertMoney(3000, DB::table('repayments')->where('loan_id', 807)->sum('amount'));
        $this->assertMoney(0, $this->owed(806));
        $this->assertMoney(15000, DB::table('repayments')->whereIn('loan_id', [806, 807])->sum('amount'), 'the cash in total does not change');
    }
}
