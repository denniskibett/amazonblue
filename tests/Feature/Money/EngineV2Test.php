<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
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

    public function test_529_rollover_books_what_the_preview_shows(): void
    {
        $this->loan529();
        Carbon::setTestNow('2026-10-17 10:00');

        $preview = $this->calc->getRolloverPreview(Loan::findOrFail(529));
        $this->assertMoney(16258.00, $preview['new_principal']);
        $this->assertMoney(19509.60, $preview['new_balance']);
        $this->assertSame('2026-10-27', $preview['new_due_date_raw']);

        $this->calc->executeRollover(Loan::findOrFail(529));
        $c3 = LoanCycle::where('loan_id', 529)->where('cycle_number', 3)->firstOrFail();
        $this->assertMoney(16258.00, $c3->previous_balance);   // v1: 17,280
        $this->assertMoney(3251.60, $c3->interest_capitalized);
        $this->assertMoney(19509.60, $c3->new_balance);        // v1: 20,736 (over by 1,226.40)
        $this->assertSame('2026-10-17', $c3->start_date->toDateString()); // v1: 10-16
        $this->assertSame('2026-10-27', $c3->due_date->toDateString());
        $this->assertSame('10.000', (string) $c3->penalty_rate);

        $c2 = LoanCycle::findOrFail(606);
        $this->assertSame('completed', $c2->status);
        $this->assertSame('2026-10-17', $c2->closed_on->toDateString());
        $this->assertMoney(16258.00, $c2->closing_balance);

        $loan = Loan::findOrFail(529);
        $this->assertMoney(16258.00, $loan->amount);           // current principal (v1: 20,736 = P + I)
        $this->assertMoney(12000.00, $loan->original_amount);
        $this->assertSame(3, (int) $loan->cycle);
        $this->assertSame('disbursed', $loan->status);
    }

    public function test_a_closed_cycle_is_never_rolled_again_and_the_rate_rule_holds(): void
    {
        $this->loan529();
        Carbon::setTestNow('2026-10-17 10:00');
        $roll = app(\App\Services\Loans\Rollover::class);

        try {
            $roll->preview(Loan::findOrFail(529), ['interest_rate' => 30]);
            $this->fail('cycle 3 must keep the previous rate');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('keep the previous rate', $e->getMessage());
        }

        LoanCycle::whereKey(606)->update(['status' => 'completed']);
        $this->expectException(\DomainException::class);
        $roll->preview(Loan::findOrFail(529));
    }

    public function test_from_the_fifth_cycle_staff_must_set_a_rate(): void
    {
        $this->loan529();
        LoanCycle::whereKey(606)->update(['cycle_number' => 4]);
        Carbon::setTestNow('2026-10-17 10:00');
        $roll = app(\App\Services\Loans\Rollover::class);

        try {
            $roll->preview(Loan::findOrFail(529));
            $this->fail('cycle 5 needs a rate');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('set a new rate', $e->getMessage());
        }
        $this->assertMoney(16258 * 1.15, $roll->preview(Loan::findOrFail(529), ['interest_rate' => 15])['new_balance']);
    }

    public function test_preview_equals_booking_for_generated_cases(): void
    {
        mt_srand(20261020);
        $roll = app(\App\Services\Loans\Rollover::class);
        for ($i = 0; $i < 200; $i++) {
            $id = 10000 + $i;
            $start = Carbon::parse('2026-01-01')->addDays(mt_rand(0, 200));
            $due = $start->copy()->addDays(mt_rand(5, 40));
            $principal = mt_rand(10, 2000) * 50;
            $rate = [10, 20, 30][mt_rand(0, 2)];
            $this->loanRow($id, 1, $principal, $principal, $start->toDateString(), $due->toDateString(), 'disbursed', 1, 30);
            $cycleId = 50000 + $i * 10; // room for the cycle each rollover creates
            $this->cycleRow($cycleId, $id, 1, 0, $principal * $rate / 100, $principal * (1 + $rate / 100), $rate, $start->toDateString(), $due->toDateString(), 'active', 'Initial');
            for ($k = mt_rand(0, 3); $k > 0; $k--) {
                $this->repay($id, $cycleId, mt_rand(1, 40) * 100, $start->copy()->addDays(mt_rand(0, 60))->toDateString(), 'GEN' . $id . $k);
            }
            $when = $due->copy()->addDays(mt_rand(-3, 25));
            Carbon::setTestNow($when->copy()->setTime(9, 0));
            try {
                $p = $roll->preview(Loan::findOrFail($id));
            } catch (\DomainException $e) {
                continue; // nothing owed
            }
            $this->calc->executeRollover(Loan::findOrFail($id));
            $c2 = LoanCycle::where('loan_id', $id)->where('cycle_number', 2)->firstOrFail();
            $this->assertEqualsWithDelta($p['principal'], (float) $c2->previous_balance, 0.001, "case {$i}");
            $this->assertEqualsWithDelta($p['new_balance'], (float) $c2->new_balance, 0.001, "case {$i}");
            $this->assertSame($p['due_date'], $c2->due_date->toDateString(), "case {$i}");
            $this->assertMoney($p['principal'], Loan::findOrFail($id)->amount);
        }
        Carbon::setTestNow();
    }

    public function test_a_yearly_product_is_due_a_year_later(): void
    {
        $this->assertSame('2027-01-31', \App\Services\Loans\Period::add(Carbon::parse('2026-01-31'), 1, 'year')->toDateString());
        $this->assertSame('2026-02-28', \App\Services\Loans\Period::add(Carbon::parse('2026-01-31'), 1, 'months')->toDateString());
        $this->assertSame('2026-02-14', \App\Services\Loans\Period::add(Carbon::parse('2026-01-31'), 2, 'weeks')->toDateString());
    }

    private function balance(int $loanId, int $cycleNumber): array
    {
        $loan = Loan::findOrFail($loanId);

        return $this->calc->calculateCycleBalance($loan, $loan->cycles()->where('cycle_number', $cycleNumber)->firstOrFail());
    }
}
