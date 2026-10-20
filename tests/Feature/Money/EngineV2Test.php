<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Services\LoanCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The four audit loans on the v2 engine (Stage 4 Phase 2). Each test names the v1 figure it replaces
 * (CharacterisationTest pins v1).
 */
class EngineV2Test extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private LoanCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        $this->calc = app(LoanCalculator::class);
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_529_owed_the_day_after_due_is_unchanged(): void
    {
        $this->loan529();
        Carbon::setTestNow('2026-10-17 10:00');
        $b = $this->balance(529, 2);
        $this->assertMoney(14780.00, $b['outstanding_at_due']);
        $this->assertMoney(1478.00, $b['penalty']);
        $this->assertMoney(16258.00, $b['final_outstanding']);
    }

    public function test_529_paying_on_day_one_closes_the_cycle(): void
    {
        $this->loan529();
        $this->repay(529, 606, 16258.00, '2026-10-17', 'CHAR-529-LATE');
        Carbon::setTestNow('2026-10-25 10:00');

        $b = $this->balance(529, 2);
        $this->assertMoney(1478.00, $b['penalty']);            // v1: 13,302 (nine days)
        $this->assertMoney(0.00, $b['final_outstanding']);     // v1: 14,780
        $this->assertTrue($b['is_fully_repaid']);
        $this->assertSame(0, $b['days_overdue']);
    }

    public function test_494_cycle_one_lends_what_it_lent(): void
    {
        $this->loan494();
        Carbon::setTestNow('2026-10-20 10:00');

        $c1 = $this->balance(494, 1);
        $this->assertMoney(40000.00, $c1['principal']);        // v1: 69,120 (current loans.amount)
        $this->assertMoney(8000.00, $c1['interest']);          // v1: 13,824
        $this->assertMoney(48000.00, $c1['carried_forward']);
        $this->assertMoney(0.00, $c1['carry_difference']);

        $c3 = $this->balance(494, 3);
        $this->assertMoney(69120.00, $c3['full_balance']);
        $this->assertMoney(0.00, $c3['final_outstanding']);
        $this->assertTrue($c3['is_fully_repaid']);
    }

    public function test_486_cycle_one_stops_on_the_day_the_plan_took_it_over(): void
    {
        $this->loan486();
        Carbon::setTestNow('2026-10-20 10:00');

        $c1 = $this->balance(486, 1);
        $this->assertMoney(26376.19, $c1['principal']);        // v1: 29,651.43
        $this->assertMoney(31651.43, $c1['full_balance']);
        $this->assertSame(8, $c1['days_subject_to_penalty']);  // 08-27 .. 09-03, then the plan; v1: 55 days to today
        $this->assertMoney(25321.12, $c1['penalty']);          // v1: 195,699.44
        $this->assertMoney(53972.55, $c1['owed_on_close']);
        $this->assertMoney(29651.43, $c1['carried_forward']);
        $this->assertMoney(24321.12, $c1['carry_difference']); // penalty the plan did not carry: reported, not charged
        $this->assertMoney(0.00, $c1['final_outstanding']);

        $c2 = $this->balance(486, 2);
        $this->assertMoney(29651.43, $c2['principal']);
        $this->assertMoney(0.00, $c2['interest']);
        $this->assertMoney(0.00, $c2['penalty']);              // plan cycle: waived, this cycle only
        $this->assertMoney(27651.43, $c2['final_outstanding']);
        $this->assertTrue($c2['is_overdue'], 'still late, just not charged');
    }

    public function test_461_cycle_one_lent_85600_and_cycle_two_is_on_time(): void
    {
        $this->loan461();
        Carbon::setTestNow('2026-10-20 10:00');

        $c1 = $this->balance(461, 1);
        $this->assertMoney(85600.00, $c1['principal']);        // v1: 44,352
        $this->assertMoney(36960.00, $c1['owed_on_close']);
        $this->assertMoney(0.00, $c1['carry_difference']);

        $c2 = $this->balance(461, 2);
        $this->assertMoney(44352.00, $c2['final_outstanding']);
        $this->assertFalse($c2['is_overdue']);
    }

    public function test_loan_metrics_read_the_latest_cycle(): void
    {
        $this->loan529();
        Carbon::setTestNow('2026-10-17 10:00');
        $m = $this->calc->calculateLoanMetrics(Loan::findOrFail(529));
        $this->assertMoney(16258.00, $m['final_outstanding']);
        $this->assertMoney(16258.00, $m['outstanding_balance']);
        $this->assertMoney(1478.00, $m['penalty_amount']);
        $this->assertSame('v2', $m['engine']);
    }

    private function balance(int $loanId, int $cycleNumber): array
    {
        $loan = Loan::findOrFail($loanId);

        return $this->calc->calculateCycleBalance($loan, $loan->cycles()->where('cycle_number', $cycleNumber)->firstOrFail());
    }
}
