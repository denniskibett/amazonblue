<?php

namespace Tests\Unit;

use App\Services\Loans\CycleBalance;
use PHPUnit\Framework\TestCase;

/**
 * The v2 cycle balance (Stage 4 Phase 2), against the audit's worked examples (docs/audit/modules/B-loan-engine.md)
 * and the owner's decisions of 20 Oct 2026 (10%/day simple penalty, in-duplum cap).
 */
class CycleBalanceTest extends TestCase
{
    // loan 529 cycle 2: principal 14,400 at 20%, 10%/day, due 2026-10-16, 2,500 paid on 10-07
    private function t529(array $over = []): array
    {
        return $over + ['principal' => 14400, 'interest_rate' => 20, 'start' => '2026-10-06', 'due' => '2026-10-16',
            'penalty_rate' => 10, 'grace_days' => 0, 'threshold_days' => 30, 'waived_until' => null, 'cap' => 'none'];
    }

    public function test_529_owed_the_day_after_due(): void
    {
        $b = CycleBalance::compute($this->t529(), [['date' => '2026-10-07', 'amount' => 2500]], '2026-10-17');
        $this->assertSame(17280.0, $b['full_balance']);
        $this->assertSame(14780.0, $b['outstanding_at_due']);
        $this->assertSame(1478.0, $b['penalty']);
        $this->assertSame(16258.0, $b['final_outstanding']);
        $this->assertSame(1, $b['days_overdue']);
    }

    public function test_529_paying_the_balance_shown_closes_it_and_penalty_stops(): void
    {
        $b = CycleBalance::compute($this->t529(), [
            ['date' => '2026-10-07', 'amount' => 2500], ['date' => '2026-10-17', 'amount' => 16258],
        ], '2026-10-25');
        $this->assertSame(0.0, $b['final_outstanding']);         // v1 says 14,780
        $this->assertSame(1478.0, $b['penalty']);                 // one day, not nine
        $this->assertSame('2026-10-17', $b['settled_on']);
        $this->assertSame('2026-10-17', $b['last_penalty_day']);
        $this->assertTrue($b['is_fully_repaid']);
        $this->assertFalse($b['is_overdue']);
        $this->assertSame([1478.0, 2880.0, 14400.0], [$b['penalty_paid'], $b['interest_paid'], $b['principal_paid']]);
    }

    public function test_a_late_payment_clears_penalty_then_interest_then_principal_and_penalty_runs_on_what_is_left(): void
    {
        // 5,000 at 20% due 10-10; nothing paid; 10-11 penalty 600; pays 2,000 on 10-11: penalty 600, interest 1,000, principal 400
        $t = ['principal' => 5000, 'interest_rate' => 20, 'start' => '2026-10-01', 'due' => '2026-10-10', 'penalty_rate' => 10,
            'grace_days' => 0, 'threshold_days' => 30, 'waived_until' => null, 'cap' => 'none'];
        $b = CycleBalance::compute($t, [['date' => '2026-10-11', 'amount' => 2000]], '2026-10-12');
        $this->assertSame(600.0 + 460.0, $b['penalty']);         // day 2 on 4,600 unpaid principal (not on penalty)
        $this->assertSame(4600.0 + 460.0, $b['final_outstanding']);
        $this->assertSame([600.0, 1000.0, 400.0], [$b['penalty_paid'], $b['interest_paid'], $b['principal_paid']]);
    }

    public function test_in_duplum_stops_penalty_at_the_principal_unpaid_on_the_due_date(): void
    {
        // the owner's question: 5,000 at 20%, never paid, 180 days late
        $t = ['principal' => 5000, 'interest_rate' => 20, 'start' => '2026-01-01', 'due' => '2026-01-11', 'penalty_rate' => 10,
            'grace_days' => 0, 'threshold_days' => 30, 'waived_until' => null];
        $none = CycleBalance::compute($t + ['cap' => 'none'], [], '2026-07-10');
        $capped = CycleBalance::compute($t + ['cap' => 'in_duplum'], [], '2026-07-10');

        $this->assertSame(180, $none['days_overdue']);
        $this->assertSame(114000.0, $none['final_outstanding']); // 6,000 + 180 x 600
        $this->assertSame(11000.0, $capped['final_outstanding']); // 6,000 + 5,000
        $this->assertSame(5000.0, $capped['penalty']);
        $this->assertTrue($capped['penalty_capped']);
        $this->assertTrue($capped['is_defaulted']);
        $this->assertSame('2026-01-20', CycleBalance::compute($t + ['cap' => 'in_duplum'], [], '2026-02-01')['last_penalty_day']); // 01-12..01-19: 8 x 600, then 200 on 01-20
    }

    public function test_grace_days_and_a_waiver_hold_the_penalty(): void
    {
        $t = $this->t529(['grace_days' => 2]);
        $this->assertSame(0.0, CycleBalance::compute($t, [], '2026-10-18')['penalty']);
        $this->assertSame(1728.0, CycleBalance::compute($t, [], '2026-10-19')['penalty']);

        $w = $this->t529(['waived_until' => '2026-10-20']);
        $this->assertSame(0.0, CycleBalance::compute($w, [], '2026-10-20')['penalty']);
        $this->assertSame(1728.0, CycleBalance::compute($w, [], '2026-10-21')['penalty']);
        $this->assertTrue(CycleBalance::compute($w, [], '2026-10-20')['is_overdue'], 'waived is still unpaid and late');
    }

    public function test_a_reversal_on_the_same_day_nets_out(): void
    {
        $b = CycleBalance::compute($this->t529(), [
            ['date' => '2026-10-07', 'amount' => 2500], ['date' => '2026-10-07', 'amount' => -2500],
        ], '2026-10-16');
        $this->assertSame(17280.0, $b['final_outstanding']);
        $this->assertSame(0.0, $b['total_repayments']);
    }

    public function test_paid_in_full_on_time_and_overpaid(): void
    {
        $b = CycleBalance::compute($this->t529(), [['date' => '2026-10-10', 'amount' => 17500]], '2026-11-30');
        $this->assertSame(0.0, $b['final_outstanding']);
        $this->assertSame(0.0, $b['penalty']);
        $this->assertSame(220.0, $b['overpaid']);
        $this->assertSame('2026-10-10', $b['settled_on']);
        $this->assertSame(0, $b['days_overdue']);
    }

    public function test_payments_after_the_as_of_day_are_ignored(): void
    {
        $b = CycleBalance::compute($this->t529(), [['date' => '2026-10-20', 'amount' => 20000]], '2026-10-17');
        $this->assertSame(17280.0 + 1728.0, $b['final_outstanding']);
    }
}
