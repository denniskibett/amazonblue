<?php

namespace Tests\Feature\Money;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The four audit loans (529, 494, 486, 461) as copied from the live mirror on 20 Oct 2026, shared by the v1
 * characterisation tests and the v2 engine tests.
 */
trait AuditLoanFixtures
{
    // ---------------------------------------------------------------- fixtures (live mirror, 20 Oct 2026)

    protected function loanTypes(): void
    {
        DB::table('loan_types')->insert([
            ['id' => 1, 'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
            ['id' => 2, 'name' => 'Quick Loans 1 month 20%', 'period' => 1, 'unit' => 'months', 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
            ['id' => 3, 'name' => 'Quick Loans 14', 'period' => 2, 'unit' => 'weeks', 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
            ['id' => 13, 'name' => 'Quick Loans 3 months 60%', 'period' => 3, 'unit' => 'months', 'interest_rate' => 60, 'penalty_rate' => 10, 'grace_period_days' => 0, 'default_threshold_days' => 30],
        ]);
    }

    protected function loan529(): void
    {
        $this->loanRow(529, 1, 17280.00, 12000.00, '2026-09-26', '2026-10-16', 'overdue', 2, 0);
        $this->cycleRow(593, 529, 1, 0, 2400.00, 14400.00, 20, '2026-09-26', '2026-10-06', 'completed', 'Initial loan cycle - Emergency 10 Days');
        $this->cycleRow(606, 529, 2, 14400.00, 2880.00, 17280.00, 20, '2026-10-06', '2026-10-16', 'active', 'Loan rollover - Cycle 2');
        $this->repay(529, 606, 2500.00, '2026-10-07', 'UJ7CQ9KQCR', 800);
    }

    protected function loan494(): void
    {
        $this->loanRow(494, 3, 69120.00, 40000.00, '2026-08-07', '2026-09-23', 'repaid', 3, 42);
        $this->cycleRow(523, 494, 1, 0, 8000.00, 48000.00, 20, '2026-08-07', '2026-08-21', 'completed', 'Initial loan cycle - Quick Loans 14');
        $this->cycleRow(540, 494, 2, 48000.00, 9600.00, 57600.00, 20, '2026-08-21', '2026-09-04', 'completed', 'Loan rollover - Cycle 2 (Quick Loans 14)');
        $this->cycleRow(555, 494, 3, 57600.00, 11520.00, 69120.00, 20, '2026-09-04', '2026-09-23', 'completed', 'Loan rollover - Cycle 3 (Quick Loans 14)');
        $this->repay(494, 555, 43600.00, '2026-09-23', '051771616664', 780, 'bank_transfer');
        $this->repay(494, 555, 14000.00, '2026-09-23', 'CREDIT DISCOUNT', 781, 'cash');
        $this->repay(494, 555, 11520.00, '2026-09-23', 'INTEREST FEES', 782, 'other');
    }

    protected function loan486(): void
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

    protected function loan461(): void
    {
        $this->loanRow(461, 13, 44352.00, null, '2026-06-20', '2026-12-20', 'disbursed', 2, 150);
        $this->cycleRow(437, 461, 1, 0, 51360.00, 136960.00, 60, '2026-06-20', '2026-09-20', 'completed', 'Initial cycle (auto-created)');
        $this->cycleRow(608, 461, 2, 36960.00, 7392.00, 44352.00, 20, '2026-09-20', '2026-12-20', 'active', 'Loan rollover - Cycle 2');
        $this->repay(461, 437, 100000.00, '2026-09-10', 'UIAGC5Z6RF', 764);
    }

    protected function loanRow(int $id, int $typeId, float $amount, ?float $original, string $borrowDate, string $dueDate, string $status, int $cycle, int $threshold): void
    {
        DB::table('loans')->insert([
            'id' => $id, 'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId,
            'amount' => $amount, 'original_amount' => $original, 'borrow_date' => $borrowDate, 'due_date' => $dueDate,
            'status' => $status, 'cycle' => $cycle, 'npl_trigger_threshold' => $threshold, 'broker_status' => 0,
            'reason' => 'Characterisation fixture', 'consent' => 1, 'created_at' => $borrowDate, 'updated_at' => $borrowDate,
        ]);
    }

    protected function cycleRow(int $id, int $loanId, int $number, float $previous, float $interest, float $new, float $rate, string $start, string $due, string $status, string $notes): void
    {
        DB::table('loan_cycles')->insert([
            'id' => $id, 'loan_id' => $loanId, 'cycle_number' => $number, 'previous_balance' => $previous,
            'interest_capitalized' => $interest, 'new_balance' => $new, 'interest_rate' => $rate,
            'start_date' => $start, 'due_date' => $due, 'status' => $status, 'notes' => $notes,
            'created_at' => $start, 'updated_at' => $start,
        ]);
    }

    protected function repay(int $loanId, int $cycleId, float $amount, string $date, string $transaction, ?int $id = null, string $mode = 'mpesa'): void
    {
        DB::table('repayments')->insert(array_filter([
            'id' => $id, 'loan_id' => $loanId, 'loan_cycle_id' => $cycleId, 'amount' => $amount,
            'repayment_date' => $date, 'transaction' => $transaction, 'mode' => $mode,
            'created_at' => $date, 'updated_at' => $date,
        ], fn ($v) => $v !== null));
    }

    protected function assertMoney(float $expected, $actual): void
    {
        $this->assertEqualsWithDelta($expected, (float) $actual, 0.01);
    }
}
