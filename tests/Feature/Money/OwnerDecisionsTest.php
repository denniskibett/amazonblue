<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\GraceFix;
use App\Services\Loans\OwnerDecisions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OwnerDecisionsTest extends TestCase
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

    public function test_forbearance_waives_the_cycle_and_sets_the_pay_by_day_even_when_defaulted(): void
    {
        $this->loanRow(212, 1, 100000, 100000, '2026-09-23', '2026-10-03', 'defaulted', 1, 0);
        DB::table('loans')->where('id', 212)->update(['default_triggered' => 1]);
        $this->cycleRow(212, 212, 1, 0, 20000, 120000, 20, '2026-09-23', '2026-10-03', 'active', 'Initial');
        $this->assertGreaterThan(120000, CycleBalance::at(LoanCycle::findOrFail(212))['final_outstanding']);

        app(OwnerDecisions::class)->apply(212);

        $loan = Loan::findOrFail(212);
        $cycle = LoanCycle::findOrFail(212);
        $this->assertSame('forbearance', $loan->status);
        $this->assertFalse((bool) $loan->default_triggered);
        $this->assertSame(GraceFix::OPEN_ENDED, $cycle->penalty_waived_until->toDateString());
        $this->assertSame('2026-10-03', $cycle->due_date->toDateString());
        $this->assertMoney(120000, CycleBalance::at($cycle)['final_outstanding']);
    }

    public function test_a_plan_sets_the_agreed_amount_at_zero_percent_in_forbearance(): void
    {
        $this->loanRow(458, 1, 150000, 150000, '2026-09-08', '2026-09-18', 'defaulted', 1, 0);
        $this->cycleRow(458, 458, 1, 0, 30000, 180000, 20, '2026-09-08', '2026-09-18', 'active', 'Initial');
        $owed = CycleBalance::at(LoanCycle::findOrFail(458))['final_outstanding'];
        $this->assertGreaterThan(200000, $owed);

        // the decision list names 458 with 1,000,000; here the owed amount is smaller, so plan for less
        $svc = new class extends OwnerDecisions {
            public const DECISIONS = [458 => ['action' => 'plan', 'amount' => 200000.00, 'pay_by' => '2026-10-22', 'who' => 'test']];

            public function apply(int $loanId): void
            {
                $this->applyDecision($loanId, self::DECISIONS[$loanId]);
            }
        };
        $svc->apply(458);

        $c = LoanCycle::where('loan_id', 458)->orderByDesc('cycle_number')->first();
        $this->assertSame(2, (int) $c->cycle_number);
        $this->assertMoney(200000, $c->lentPrincipal());
        $this->assertSame('2026-10-22', $c->due_date->toDateString());
        $this->assertMoney(200000, CycleBalance::at($c)['final_outstanding']);
        $this->assertSame('forbearance', Loan::findOrFail(458)->status);
    }
}
