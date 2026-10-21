<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Ledger;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\RolloverCleanup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolloverCleanupTest extends TestCase
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

    private function disburse(int $loanId, float $amount, string $date, string $transaction, ?int $id = null): void
    {
        DB::table('disbursements')->insert(array_filter(['id' => $id, 'loan_id' => $loanId, 'amount' => $amount, 'transaction' => $transaction,
            'mode' => 'mpesa', 'disburse_date' => $date, 'payment_date' => $date, 'created_at' => $date, 'updated_at' => $date], fn ($v) => $v !== null));
    }

    /** Like 282 -> 330: P lends 10,000, rolls 12,000 into C on P's due date; C is paid off. */
    private function pair(int $userOffsetDays = 0): void
    {
        $this->loanRow(500, 1, 10000, 10000, '2026-01-01', '2026-01-11', 'repaid', 1, 0);
        $user = DB::table('loans')->where('id', 500)->value('user_id');
        $this->cycleRow(500, 500, 1, 0, 2000, 12000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->disburse(500, 10000, '2026-01-01', 'CASH1', 900);
        $this->repay(500, 500, 12000, '2026-01-11', 'ROLL OVER', 900, 'other');

        $start = Carbon::parse('2026-01-11')->addDays($userOffsetDays)->toDateString();
        $this->loanRow(501, 1, 12000, 12000, $start, Carbon::parse($start)->addDays(10)->toDateString(), 'repaid', 1, 0);
        DB::table('loans')->where('id', 501)->update(['user_id' => $user]);
        $this->cycleRow(501, 501, 1, 0, 2400, 14400, 20, $start, Carbon::parse($start)->addDays(10)->toDateString(), 'completed', 'Initial');
        $this->disburse(501, 12000, $start, 'ROLL OVER', 901);
        $this->repay(501, 501, 14400, Carbon::parse($start)->addDays(10)->toDateString(), 'CASH2', 901, 'mpesa');
    }

    public function test_a_rollover_on_the_due_date_becomes_cycle_2_and_its_roll_over_rows_go(): void
    {
        $this->pair();
        $found = app(RolloverCleanup::class)->chains();
        $this->assertCount(1, $found['chains']);
        $this->assertSame([500, 501], $found['chains'][0]['loans']);
        $this->assertSame(0, $found['chains'][0]['links'][0]['days_late']);
        $this->assertMoney(0, $found['chains'][0]['carry_difference']);

        $done = app(RolloverCleanup::class)->applyChain(500);

        $cycles = LoanCycle::where('loan_id', 500)->orderBy('cycle_number')->get();
        $this->assertSame([1, 2], $cycles->pluck('cycle_number')->all());
        $this->assertSame(['2026-01-01', '2026-01-11'], $cycles->map(fn ($c) => $c->start_date->toDateString())->all(), 'cycles in date order');
        $this->assertSame(501, (int) DB::table('loans')->where('id', 501)->value('id'));
        $this->assertSame(500, (int) DB::table('loans')->where('id', 501)->value('merged_into_loan_id'));
        $this->assertNotNull(DB::table('repayments')->where('id', 900)->value('deleted_at'), 'ROLL OVER repayment removed');
        $this->assertNotNull(DB::table('disbursements')->where('id', 901)->value('deleted_at'), 'ROLL OVER disbursement removed');
        $this->assertNull(DB::table('repayments')->where('id', 901)->value('deleted_at'), 'cash stays');
        $this->assertNull(DB::table('disbursements')->where('id', 900)->value('deleted_at'), 'cash stays');
        $this->assertSame(2, $done['rows_removed']);
        $this->assertMoney(0, CycleBalance::at($cycles->last())['final_outstanding']);
        $this->assertSame('repaid', Loan::findOrFail(500)->status);
        $this->assertCount(0, app(RolloverCleanup::class)->strayRows());
    }

    public function test_two_days_late_still_links_but_a_parallel_loan_does_not(): void
    {
        $this->pair(2);
        $this->assertCount(1, app(RolloverCleanup::class)->chains()['chains']);

        DB::table('disbursements')->delete();
        DB::table('repayments')->delete();
        DB::table('loan_cycles')->delete();
        DB::table('loans')->delete();
        $this->pair(9);   // starts 9 days after the due date, for a different amount: a separate loan
        DB::table('loan_cycles')->where('id', 501)->update(['interest_capitalized' => 2600, 'new_balance' => 15600]);
        $found = app(RolloverCleanup::class)->chains();
        $this->assertCount(0, $found['chains']);
        $this->assertSame(501, $found['unmatched'][0]['loan']);
    }

    public function test_an_exact_amount_links_even_when_the_dates_were_entered_days_apart(): void
    {
        $this->pair(9);   // like 138 -> 456: the ROLL OVER repayment is exactly what the new loan lent
        $found = app(RolloverCleanup::class)->chains();
        $this->assertCount(1, $found['chains']);
        $this->assertSame('amount', $found['chains'][0]['links'][0]['matched_on']);
    }

    public function test_a_top_up_loan_is_not_merged(): void
    {
        $this->pair();
        $this->disburse(501, 5000, '2026-01-11', 'NEW CASH');   // like #80: carried balance plus new cash
        $found = app(RolloverCleanup::class)->chains();
        $this->assertCount(0, $found['chains']);
        $this->assertStringContainsString('top-up', $found['unmatched']->firstWhere('loan', 501)['why']);
    }

    public function test_a_loan_already_paid_off_is_not_a_rollover_source(): void
    {
        $this->pair();
        $this->repay(500, 500, 12000, '2026-01-10', 'CASH-EARLY', 902, 'mpesa');   // paid in cash the day before
        $this->assertCount(0, app(RolloverCleanup::class)->chains()['chains']);
    }

    public function test_a_roll_over_row_on_the_latest_cycle_is_kept(): void
    {
        $this->loanRow(600, 1, 10000, 10000, '2026-01-01', '2026-01-21', 'repaid', 2, 0);
        $this->cycleRow(600, 600, 1, 0, 2000, 12000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->cycleRow(601, 600, 2, 12000, 2400, 14400, 20, '2026-01-11', '2026-01-21', 'completed', 'Rollover');
        $this->repay(600, 600, 12000, '2026-01-11', 'ROLL OVER', 950, 'other');
        $this->repay(600, 601, 14400, '2026-01-21', 'ROLL OVER', 951, 'other');

        $ids = app(RolloverCleanup::class)->strayRows()->pluck('id')->all();
        $this->assertSame([950], $ids, 'the carry on cycle 1 goes; the one on the latest cycle may still carry somewhere');
    }

    public function test_a_rollover_into_an_existing_loan_becomes_a_transfer(): void
    {
        // like 158 -> 162: the old loan's ROLL OVER closes it; the other loan has no disbursement row, just the carried amount
        $this->loanRow(700, 1, 1600, 1600, '2026-01-01', '2026-01-11', 'repaid', 1, 0);
        $user = DB::table('loans')->where('id', 700)->value('user_id');
        $this->cycleRow(700, 700, 1, 0, 320, 1920, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->disburse(700, 1600, '2026-01-01', 'CASH');
        $this->repay(700, 700, 1920, '2026-01-11', 'ROLL OVER', 970, 'other');
        $this->loanRow(701, 1, 1920, 1920, '2026-01-12', '2026-01-22', 'repaid', 1, 0);
        DB::table('loans')->where('id', 701)->update(['user_id' => $user]);
        $this->cycleRow(701, 701, 1, 0, 384, 2304, 20, '2026-01-12', '2026-01-22', 'completed', 'Initial');

        $t = app(RolloverCleanup::class)->transfers();
        $this->assertCount(1, $t);
        $this->assertSame([700, 701, 'no disbursement row'], [$t[0]['from'], $t[0]['to'], $t[0]['how']]);
        $this->assertMoney(1920, $t[0]['amount']);
    }

    public function test_a_loan_never_sent_money_charges_nothing(): void
    {
        $this->loanRow(132, 1, 50000, 50000, '2025-07-29', '2025-08-08', 'rejected', 1, 0);
        $this->cycleRow(132, 132, 1, 0, 10000, 60000, 20, '2025-07-29', '2025-08-08', 'active', 'Initial');

        $this->assertTrue(Ledger::neverSent(Loan::findOrFail(132)));
        $this->assertSame([], app(Ledger::class)->planLoanCycle(LoanCycle::findOrFail(132))['legs']);
        $this->assertSame(132, app(RolloverCleanup::class)->neverSent()[0]['loan_id']);

        $this->disburse(132, 50000, '2025-07-29', 'CASH');
        $this->assertFalse(Ledger::neverSent(Loan::findOrFail(132)));
    }
}
