<?php

namespace Tests\Feature\Money;

use App\Console\Commands\LoansWriteOffRepaid;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\GraceFix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GraceFixTest extends TestCase
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

    /** Loan 23 as on live: 6,000 due 8 Mar 2025, paid in full on 11 Mar, then written off 5,000 on 21 Oct 2026. */
    private function loan23(float $paid = 6000.00, bool $writeOff = true, string $status = 'repaid'): void
    {
        $this->loanRow(23, 1, 5000.00, 5000.00, '2025-02-26', '2025-03-08', $status, 1, 0);
        $this->cycleRow(23, 23, 1, 0, 1000.00, 6000.00, 20, '2025-02-26', '2025-03-08', 'completed', 'Initial cycle (auto-created)');
        $this->repay(23, 23, $paid, '2025-03-11', 'EQAC4978BCD7F1F', 25, 'cash');
        if ($writeOff) {
            DB::table('repayments')->insert(['id' => 810, 'loan_id' => 23, 'loan_cycle_id' => 23, 'amount' => 5000.00,
                'repayment_date' => '2026-10-21', 'transaction' => LoansWriteOffRepaid::REFERENCE, 'kind' => 'bad_debt', 'mode' => 'book',
                'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_loan_23_gets_three_grace_days_and_loses_its_write_off(): void
    {
        $this->loan23();
        $p = app(GraceFix::class)->proposalFor(Loan::findOrFail(23));

        $this->assertSame('grace', $p['action']);
        $this->assertSame('2025-03-11', $p['paid_in_full_on']);
        $this->assertSame(3, $p['days_late']);
        $this->assertSame(3, $p['grace_after']);
        $this->assertMoney(5000.00, $p['owed_now']);        // what v2 says without the write-off
        $this->assertMoney(0, $p['owed_after']);
        $this->assertMoney(0, $p['penalty_after']);

        app(GraceFix::class)->apply(Loan::findOrFail(23));

        $cycle = LoanCycle::findOrFail(23);
        $this->assertSame(3, (int) $cycle->grace_days);
        $this->assertSame('2025-03-08', $cycle->due_date->toDateString(), 'the due date is kept');
        $this->assertNotNull(DB::table('repayments')->where('id', 810)->value('deleted_at'), 'write-off soft-deleted');
        $this->assertNull(DB::table('repayments')->where('id', 25)->value('deleted_at'), 'the cash stays');
        $b = CycleBalance::at($cycle);
        $this->assertMoney(0, $b['final_outstanding']);
        $this->assertMoney(0, $b['penalty']);
        $this->assertMoney(5000, $b['principal_paid']);
        $this->assertMoney(1000, $b['interest_paid']);
        $this->assertSame('repaid', Loan::findOrFail(23)->status);
        $this->assertNull(app(GraceFix::class)->proposalFor(Loan::findOrFail(23)), 'nothing left to fix');
    }

    public function test_cash_above_the_contract_pays_penalty_days_and_only_the_rest_become_grace(): void
    {
        // like loan 165: 25,920 due, paid 28,512 two days late; the extra 2,592 is one day of penalty
        $this->loanRow(165, 1, 21600.00, 21600.00, '2025-09-13', '2025-09-23', 'repaid', 1, 0);
        $this->cycleRow(165, 165, 1, 0, 4320.00, 25920.00, 20, '2025-09-13', '2025-09-23', 'completed', 'Initial');
        $this->repay(165, 165, 28512.00, '2025-09-25', 'LATE2', 165, 'cash');

        $p = app(GraceFix::class)->proposalFor(Loan::findOrFail(165));
        $this->assertSame('grace', $p['action']);
        $this->assertSame(2, $p['days_late']);
        $this->assertSame(1, $p['grace_after']);
        $this->assertMoney(2592.00, $p['penalty_after']);
        $this->assertMoney(0, $p['overpaid_after']);

        app(GraceFix::class)->apply(Loan::findOrFail(165));
        $b = CycleBalance::at(LoanCycle::findOrFail(165));
        $this->assertMoney(0, $b['final_outstanding']);
        $this->assertMoney(2592.00, $b['penalty_paid']);
        $this->assertSame('repaid', Loan::findOrFail(165)->status);
    }

    public function test_open_ended_forbearance_saves_on_the_timestamp_column(): void
    {
        $this->loanRow(430, 1, 100000.00, 100000.00, '2026-10-12', '2026-10-22', 'disbursed', 1, 0);
        $this->cycleRow(430, 430, 1, 0, 20000.00, 120000.00, 20, '2026-10-12', '2026-10-22', 'active', 'Initial');
        $this->loanRow(492, 1, 100000.00, 100000.00, '2026-08-01', '2026-08-11', 'forbearance', 1, 0);
        DB::table('loans')->where('id', 492)->update(['merged_into_loan_id' => 430, 'deleted_at' => now()]);
        $this->assertSame([430], app(GraceFix::class)->forbearanceRoots());
        app(GraceFix::class)->apply(Loan::findOrFail(430));

        $this->assertSame('forbearance', Loan::findOrFail(430)->status);
        $this->assertSame(GraceFix::OPEN_ENDED, LoanCycle::findOrFail(430)->penalty_waived_until->toDateString());
    }

    public function test_a_genuinely_short_payment_is_listed_for_the_owner_and_never_changed(): void
    {
        $this->loan23(4000.00);
        $p = app(GraceFix::class)->proposalFor(Loan::findOrFail(23));
        $this->assertSame('short_paid', $p['action']);

        $this->expectException(\RuntimeException::class);
        try {
            app(GraceFix::class)->apply(Loan::findOrFail(23));
        } finally {
            $this->assertNull(DB::table('repayments')->where('id', 810)->value('deleted_at'));
            $this->assertSame(0, (int) LoanCycle::findOrFail(23)->grace_days);
        }
    }

    public function test_a_late_full_payer_v2_moved_off_repaid_goes_back_to_repaid(): void
    {
        $this->loan23(6000.00, false, 'pending');
        $p = app(GraceFix::class)->proposalFor(Loan::findOrFail(23));
        $this->assertSame('grace', $p['action']);

        app(GraceFix::class)->apply(Loan::findOrFail(23));
        $this->assertSame('repaid', Loan::findOrFail(23)->status);
    }

    public function test_an_on_time_loan_and_a_loan_after_the_switch_are_left_alone(): void
    {
        $this->loanRow(30, 1, 5000.00, 5000.00, '2025-02-26', '2025-03-08', 'repaid', 1, 0);
        $this->cycleRow(30, 30, 1, 0, 1000.00, 6000.00, 20, '2025-02-26', '2025-03-08', 'completed', 'Initial');
        $this->repay(30, 30, 6000.00, '2025-03-08', 'ONTIME', 30, 'cash');
        $this->assertNull(app(GraceFix::class)->proposalFor(Loan::findOrFail(30)));

        Carbon::setTestNow('2026-11-30 10:00');
        $this->loanRow(31, 1, 5000.00, 5000.00, '2026-10-25', '2026-11-04', 'overdue', 1, 0);
        $this->cycleRow(31, 31, 1, 0, 1000.00, 6000.00, 20, '2026-10-25', '2026-11-04', 'active', 'Initial');
        $this->repay(31, 31, 6000.00, '2026-11-07', 'LATE-NEW', 31, 'cash');
        $this->assertNull(app(GraceFix::class)->proposalFor(Loan::findOrFail(31)), 'v2 penalties after the switch stand');
    }

    public function test_the_command_dry_runs_then_applies_with_the_signed_sha(): void
    {
        Storage::fake('local');
        $this->loan23();

        $this->artisan('loans:grace-fix --dry-run')->assertSuccessful()->expectsOutputToContain('1 loan(s)');
        $sha = hash('sha256', Storage::disk('local')->get('reports/grace-fix-2026-10-21.csv'));
        $this->artisan('loans:grace-fix --apply --confirm=bad')->assertFailed();
        $this->artisan('loans:grace-fix --apply --confirm=' . $sha)->assertSuccessful();

        $this->assertSame('repaid', Loan::findOrFail(23)->status);
        $this->assertNotNull(DB::table('repayments')->where('id', 810)->value('deleted_at'));
    }
}
