<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\LoanCalculator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Characterisation tests (Stage 4 Phase 0.12): they pin what the engine computes TODAY for the four
 * audit loans, including figures that are wrong by the confirmed rules (docs/audit/modules/B-loan-engine.md).
 * Fixtures are the live-mirror rows for loans 529, 486, 494 and 461 (loans, loan_cycles, repayments,
 * loan_types), copied on 20 Oct 2026.
 *
 * "BUG" marks a pinned figure that Phase 2 is meant to change. When Phase 2 lands, update the pinned
 * value to the rule's value in the same commit, so every change to money output is deliberate.
 */
class CharacterisationTest extends TestCase
{
    use RefreshDatabase;

    private LoanCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = app(LoanCalculator::class);
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------- loan 529 (rules 5 and 6)

    public function test_529_owed_the_day_after_due(): void
    {
        $this->loan529();
        Carbon::setTestNow('2026-10-17 10:00');

        $b = $this->balance(529, 2);
        $this->assertMoney(17280.00, $b['full_balance']);
        $this->assertMoney(2500.00, $b['repayments_before_due']);
        $this->assertMoney(14780.00, $b['outstanding_at_due']);
        $this->assertSame(1, (int) $b['days_subject_to_penalty']);
        $this->assertMoney(1478.00, $b['penalty']);
        $this->assertMoney(16258.00, $b['final_outstanding']); // matches the rule on day 1
    }

    public function test_529_late_payment_only_reduces_the_penalty(): void
    {
        $this->loan529();
        $this->repay(529, 606, 16258.00, '2026-10-17', 'CHAR-529-LATE');
        Carbon::setTestNow('2026-10-25 10:00');

        $b = $this->balance(529, 2);
        $this->assertSame(9, (int) $b['days_overdue']);   // BUG (rule 5): penalty runs to today, not to the payment date
        $this->assertMoney(13302.00, $b['penalty']);
        $this->assertMoney(16258.00, $b['repayments_after_due']);
        $this->assertMoney(0.00, $b['outstanding_after_due']);
        $this->assertMoney(14780.00, $b['final_outstanding']); // BUG (rule 5): rule says 0
        $this->assertFalse($b['is_fully_repaid']);
    }

    public function test_529_extension_preview_differs_from_what_is_booked(): void
    {
        $this->loan529();
        Carbon::setTestNow('2026-10-17 10:00');

        $preview = $this->calc->getRolloverPreview(Loan::findOrFail(529));
        $this->assertMoney(16258.00, $preview['final_outstanding']);
        $this->assertSame(3, (int) $preview['new_cycle']);

        $this->calc->executeRollover(Loan::findOrFail(529));
        $c3 = LoanCycle::where('loan_id', 529)->where('cycle_number', 3)->firstOrFail();
        $this->assertMoney(17280.00, $c3->previous_balance); // BUG (rule 6): rule says 16,258 (repayment and penalty ignored)
        $this->assertMoney(3456.00, $c3->interest_capitalized);
        $this->assertMoney(20736.00, $c3->new_balance);      // BUG (rule 6): rule says 19,509.60, over by 1,226.40
        $this->assertSame('2026-10-16', Carbon::parse($c3->start_date)->toDateString()); // BUG: rule says 10-17
        $this->assertSame('2026-10-26', Carbon::parse($c3->due_date)->toDateString());
        $this->assertSame('completed', LoanCycle::findOrFail(606)->status);
        $this->assertMoney(20736.00, Loan::findOrFail(529)->amount); // rule 7: amount becomes P + I
    }

    // ---------------------------------------------------------------- loan 494 (rule 7: cycle 1 from the current amount)

    public function test_494_cycle_one_is_recomputed_from_the_current_amount(): void
    {
        $this->loan494();
        Carbon::setTestNow('2026-10-20 10:00');

        $c1 = $this->balance(494, 1);
        $this->assertMoney(69120.00, $c1['principal']);   // BUG (rule 7 / S-B2): cycle 1 lent 40,000
        $this->assertMoney(13824.00, $c1['interest']);    // BUG: stored interest_capitalized is 8,000
        $this->assertMoney(48000.00, $c1['database_new_balance']);

        $c3 = $this->balance(494, 3);
        $this->assertMoney(57600.00, $c3['principal']);
        $this->assertMoney(69120.00, $c3['full_balance']);
        $this->assertMoney(69120.00, $c3['repayments_before_due']); // includes CREDIT DISCOUNT and INTEREST FEES book entries
        $this->assertMoney(0.00, $c3['final_outstanding']);
        $this->assertTrue($c3['is_fully_repaid']);
    }

    // ---------------------------------------------------------------- loan 486 (rule 5, payment plan)

    public function test_486_payment_plan_and_late_payments_on_cycle_one(): void
    {
        $this->loan486();
        Carbon::setTestNow('2026-10-20 10:00');

        $c1 = $this->balance(486, 1);
        $this->assertMoney(29651.43, $c1['principal']);     // BUG (rule 7): cycle 1 lent 26,376.19 (31,651.43 − 5,275.24)
        $this->assertMoney(35581.72, $c1['full_balance']);
        $this->assertMoney(0.00, $c1['repayments_before_due']);
        $this->assertMoney(3000.00, $c1['repayments_after_due']);
        $this->assertSame(55, (int) $c1['days_overdue']);   // BUG (rule 5): still running on a cycle closed by the plan
        $this->assertMoney(195699.44, $c1['penalty']);
        $this->assertMoney(228281.15, $c1['final_outstanding']);

        $c2 = $this->balance(486, 2);
        $this->assertMoney(29651.43, $c2['principal']);
        $this->assertMoney(0.00, $c2['interest']);
        $this->assertMoney(0.00, $c2['penalty']);           // plan cycle: penalty waived
        $this->assertMoney(2000.00, $c2['repayments_before_due']);
        $this->assertMoney(27651.43, $c2['final_outstanding']);
    }

    // ---------------------------------------------------------------- loan 461 (P1 #10: "repaid" from all repayments ≥ amount)

    public function test_461_balances(): void
    {
        $this->loan461();
        Carbon::setTestNow('2026-10-20 10:00');

        $c1 = $this->balance(461, 1);
        $this->assertMoney(44352.00, $c1['principal']);     // BUG (rule 7): cycle 1 lent 85,600
        $this->assertMoney(100000.00, $c1['repayments_before_due']);
        $this->assertMoney(0.00, $c1['final_outstanding']);

        $c2 = $this->balance(461, 2);
        $this->assertMoney(36960.00, $c2['principal']);     // older rollover formula: 136,960 − 100,000
        $this->assertMoney(44352.00, $c2['full_balance']);
        $this->assertMoney(44352.00, $c2['final_outstanding']);
        $this->assertFalse($c2['is_overdue']);
    }

    public function test_461_a_small_collection_marks_the_loan_repaid(): void
    {
        $this->loan461();
        Carbon::setTestNow('2026-10-20 10:00');
        $this->seed(RoleSeeder::class);
        $teller = User::factory()->create();
        $teller->assignRole('teller');

        $this->actingAs($teller)->postJson('/repayments', [
            'loan_id' => 461, 'loan_cycle_id' => 608, 'amount' => 5000, 'repayment_date' => '2026-10-20', 'transaction' => 'CHAR-461', 'mode' => 'cash',
        ])->assertOk();

        // BUG (P1 #10): Σ all repayments 105,000 ≥ loans.amount 44,352 → repaid, while cycle 2 still owes 39,352
        $this->assertSame('repaid', Loan::findOrFail(461)->status);
        $this->assertSame('active', LoanCycle::findOrFail(608)->status);
        $this->assertMoney(39352.00, $this->balance(461, 2)['final_outstanding']);
    }

    // ---------------------------------------------------------------- fixtures (live mirror, 20 Oct 2026)

    private function loanTypes(): void
    {
        DB::table('loan_types')->insert([
            ['id' => 1, 'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
            ['id' => 2, 'name' => 'Quick Loans 1 month 20%', 'period' => 1, 'unit' => 'months', 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
            ['id' => 3, 'name' => 'Quick Loans 14', 'period' => 2, 'unit' => 'weeks', 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
            ['id' => 13, 'name' => 'Quick Loans 3 months 60%', 'period' => 3, 'unit' => 'months', 'interest_rate' => 60, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
        ]);
    }

    private function loan529(): void
    {
        $this->loanRow(529, 1, 17280.00, 12000.00, '2026-09-26', '2026-10-16', 'overdue', 2, 0);
        $this->cycleRow(593, 529, 1, 0, 2400.00, 14400.00, 20, '2026-09-26', '2026-10-06', 'completed', 'Initial loan cycle - Emergency 10 Days');
        $this->cycleRow(606, 529, 2, 14400.00, 2880.00, 17280.00, 20, '2026-10-06', '2026-10-16', 'active', 'Loan rollover - Cycle 2');
        $this->repay(529, 606, 2500.00, '2026-10-07', 'UJ7CQ9KQCR', 800);
    }

    private function loan494(): void
    {
        $this->loanRow(494, 3, 69120.00, 40000.00, '2026-08-07', '2026-09-23', 'repaid', 3, 42);
        $this->cycleRow(523, 494, 1, 0, 8000.00, 48000.00, 20, '2026-08-07', '2026-08-21', 'completed', 'Initial loan cycle - Quick Loans 14');
        $this->cycleRow(540, 494, 2, 48000.00, 9600.00, 57600.00, 20, '2026-08-21', '2026-09-04', 'completed', 'Loan rollover - Cycle 2 (Quick Loans 14)');
        $this->cycleRow(555, 494, 3, 57600.00, 11520.00, 69120.00, 20, '2026-09-04', '2026-09-23', 'completed', 'Loan rollover - Cycle 3 (Quick Loans 14)');
        $this->repay(494, 555, 43600.00, '2026-09-23', '051771616664', 780, 'bank_transfer');
        $this->repay(494, 555, 14000.00, '2026-09-23', 'CREDIT DISCOUNT', 781, 'cash');
        $this->repay(494, 555, 11520.00, '2026-09-23', 'INTEREST FEES', 782, 'other');
    }

    private function loan486(): void
    {
        $this->loanRow(486, 2, 29651.43, null, '2026-07-26', '2026-08-26', 'overdue', 2, 90);
        $this->cycleRow(462, 486, 1, 0, 5275.24, 31651.43, 20, '2026-07-26', '2026-08-26', 'completed', 'Initial cycle (auto-created)');
        $this->cycleRow(560, 486, 2, 29651.43, 0, 29651.43, 0, '2026-09-03', '2026-10-08', 'active', 'PAYMENT PLAN: Interest 0%, Period 35 days, Penalties WAIVED.');
        $this->repay(486, 462, 1000.00, '2026-09-01', 'UI16B4PGWM', 746);
        $this->repay(486, 462, 1000.00, '2026-09-01', 'UI16B4SQM4', 747);
        $this->repay(486, 462, 1000.00, '2026-09-03', 'UI36B4ZMHT', 753);
        $this->repay(486, 560, 1000.00, '2026-09-03', 'UI36B50VCE', 756);
        $this->repay(486, 560, 1000.00, '2026-09-12', 'UIC6B645BU', 767);
    }

    private function loan461(): void
    {
        $this->loanRow(461, 13, 44352.00, null, '2026-06-20', '2026-12-20', 'disbursed', 2, 150);
        $this->cycleRow(437, 461, 1, 0, 51360.00, 136960.00, 60, '2026-06-20', '2026-09-20', 'completed', 'Initial cycle (auto-created)');
        $this->cycleRow(608, 461, 2, 36960.00, 7392.00, 44352.00, 20, '2026-09-20', '2026-12-20', 'active', 'Loan rollover - Cycle 2');
        $this->repay(461, 437, 100000.00, '2026-09-10', 'UIAGC5Z6RF', 764);
    }

    private function loanRow(int $id, int $typeId, float $amount, ?float $original, string $borrowDate, string $dueDate, string $status, int $cycle, int $threshold): void
    {
        DB::table('loans')->insert([
            'id' => $id, 'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId,
            'amount' => $amount, 'original_amount' => $original, 'borrow_date' => $borrowDate, 'due_date' => $dueDate,
            'status' => $status, 'cycle' => $cycle, 'npl_trigger_threshold' => $threshold, 'broker_status' => 0,
            'reason' => 'Characterisation fixture', 'consent' => 1, 'created_at' => $borrowDate, 'updated_at' => $borrowDate,
        ]);
    }

    private function cycleRow(int $id, int $loanId, int $number, float $previous, float $interest, float $new, float $rate, string $start, string $due, string $status, string $notes): void
    {
        DB::table('loan_cycles')->insert([
            'id' => $id, 'loan_id' => $loanId, 'cycle_number' => $number, 'previous_balance' => $previous,
            'interest_capitalized' => $interest, 'new_balance' => $new, 'interest_rate' => $rate,
            'start_date' => $start, 'due_date' => $due, 'status' => $status, 'notes' => $notes,
            'created_at' => $start, 'updated_at' => $start,
        ]);
    }

    private function repay(int $loanId, int $cycleId, float $amount, string $date, string $transaction, ?int $id = null, string $mode = 'mpesa'): void
    {
        DB::table('repayments')->insert(array_filter([
            'id' => $id, 'loan_id' => $loanId, 'loan_cycle_id' => $cycleId, 'amount' => $amount,
            'repayment_date' => $date, 'transaction' => $transaction, 'mode' => $mode,
            'created_at' => $date, 'updated_at' => $date,
        ], fn ($v) => $v !== null));
    }

    private function balance(int $loanId, int $cycleNumber): array
    {
        $loan = Loan::findOrFail($loanId);

        return $this->calc->calculateCycleBalance($loan, $loan->cycles()->where('cycle_number', $cycleNumber)->firstOrFail());
    }

    private function assertMoney(float $expected, $actual): void
    {
        $this->assertEqualsWithDelta($expected, (float) $actual, 0.01);
    }
}
