<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\GraceFix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanDecisionTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        Carbon::setTestNow('2026-10-21 18:00');
        $this->loanTypes();
        DB::table('loan_types')->insert(['id' => 19, 'name' => 'Trade Finance per year', 'period' => 1, 'unit' => 'year', 'interest_rate' => 50, 'penalty_rate' => 2, 'grace_period_days' => 0, 'default_threshold_days' => 45]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_defaulted_loan_goes_to_open_ended_forbearance_and_the_nightly_run_leaves_it(): void
    {
        $this->loanRow(467, 1, 30000, 30000, '2026-07-04', '2026-08-03', 'defaulted', 1, 0);
        $this->cycleRow(467, 467, 1, 0, 6000, 36000, 20, '2026-07-04', '2026-08-03', 'active', 'Initial');
        DB::table('loans')->where('id', 467)->update(['default_triggered' => 1]);

        $this->artisan('loans:decide forbearance 467')->assertSuccessful()->expectsOutputToContain('(preview)');
        $this->assertSame('defaulted', Loan::findOrFail(467)->status, 'preview changes nothing');

        $this->artisan('loans:decide forbearance 467 --apply')->assertSuccessful();
        $loan = Loan::findOrFail(467);
        $this->assertSame('forbearance', $loan->status);
        $this->assertFalse((bool) $loan->default_triggered);
        $this->assertSame(GraceFix::OPEN_ENDED, LoanCycle::findOrFail(467)->penalty_waived_until->toDateString());

        Carbon::setTestNow('2026-12-21 02:00');
        $this->artisan('loans:update-statuses')->assertSuccessful();
        $this->assertSame('forbearance', Loan::findOrFail(467)->status, 'never defaulted while waived');
    }

    public function test_a_trade_finance_loan_on_time_is_set_active(): void
    {
        $this->loanRow(511, 19, 100000, 100000, '2026-05-29', '2027-05-29', 'defaulted', 1, 0);
        $this->cycleRow(511, 511, 1, 0, 50000, 150000, 50, '2026-05-29', '2027-05-29', 'active', 'Initial');
        DB::table('loans')->where('id', 511)->update(['default_triggered' => 1, 'is_non_performing' => 1]);

        $this->artisan('loans:decide active 511 --apply')->assertSuccessful();
        $loan = Loan::findOrFail(511);
        $this->assertSame('disbursed', $loan->status);
        $this->assertFalse((bool) $loan->is_non_performing);
    }

    public function test_active_is_refused_when_the_cycle_supports_the_default(): void
    {
        $this->loanRow(600, 1, 10000, 10000, '2026-07-01', '2026-07-11', 'defaulted', 1, 0);
        $this->cycleRow(600, 600, 1, 0, 2000, 12000, 20, '2026-07-01', '2026-07-11', 'active', 'Initial');
        $this->artisan('loans:decide active 600 --apply')->assertFailed();
        $this->assertSame('defaulted', Loan::findOrFail(600)->status);
    }
}
