<?php

namespace Tests\Feature;

use App\Console\Commands\LoansCheckIntegrity;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 0.11: loans:check-integrity lists status/money disagreements and changes nothing.
 */
class LoansCheckIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_check_finds_its_case_and_nothing_changes(): void
    {
        Storage::fake('local');

        // repaid, 6,000 cycle, 5,000 paid, cycle completed → repaid_with_balance
        $owing = $this->loan('repaid');
        $c1 = $this->cycle($owing, 1, 6000, '2026-09-01', 'completed');
        $this->repay($owing, $c1, 5000, '2026-09-05');

        // repaid, fully paid, but the cycle is still active → repaid_with_active_cycle
        $active = $this->loan('repaid');
        $c2 = $this->cycle($active, 1, 6000, '2026-09-01', 'active');
        $this->repay($active, $c2, 6000, '2026-09-05');

        // repayment dated before its cycle → repayment_before_cycle
        $early = $this->loan('disbursed');
        $c3 = $this->cycle($early, 1, 6000, '2026-09-10', 'active');
        $this->repay($early, $c3, 1000, '2026-09-01');

        // repayment on another loan's cycle → repayment_other_loan
        $misplaced = $this->loan('disbursed');
        $this->cycle($misplaced, 1, 6000, '2026-09-01', 'active');
        $this->repay($misplaced, $c3, 500, '2026-09-12');

        // a clean repaid loan → nowhere
        $clean = $this->loan('repaid');
        $this->repay($clean, $this->cycle($clean, 1, 6000, '2026-09-01', 'completed'), 6000, '2026-09-05');

        $before = DB::table('loans')->pluck('status', 'id')->all();
        $found = app(LoansCheckIntegrity::class)->findings();

        $this->assertSame([$owing->id], array_column($found['repaid_with_balance'], 'loan_id'));
        $this->assertSame(1000.0, (float) $found['repaid_with_balance'][0]['still_owed']);
        $this->assertSame([$active->id], array_column($found['repaid_with_active_cycle'], 'loan_id'));
        $this->assertSame([$early->id], array_column($found['repayment_before_cycle'], 'loan_id'));
        $this->assertSame([$misplaced->id], array_column($found['repayment_other_loan'], 'loan_id'));

        $this->artisan('loans:check-integrity', ['--csv' => true])->assertSuccessful();
        $csv = Storage::disk('local')->get('reports/loan-integrity-' . now()->format('Y-m-d') . '.csv');
        $this->assertStringContainsString('repaid_with_balance,' . $owing->id, $csv);
        $this->assertSame($before, DB::table('loans')->pluck('status', 'id')->all());
    }

    private function loan(string $status): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);

        return Loan::create([
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => '2026-09-01', 'status' => $status, 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
    }

    private function cycle(Loan $loan, int $number, float $balance, string $start, string $status): int
    {
        return DB::table('loan_cycles')->insertGetId([
            'loan_id' => $loan->id, 'cycle_number' => $number, 'new_balance' => $balance, 'interest_rate' => 20,
            'start_date' => $start, 'due_date' => date('Y-m-d', strtotime($start . ' +10 days')), 'status' => $status,
        ]);
    }

    private function repay(Loan $loan, int $cycleId, float $amount, string $date): void
    {
        DB::table('repayments')->insert([
            'loan_id' => $loan->id, 'loan_cycle_id' => $cycleId, 'amount' => $amount, 'repayment_date' => $date,
            'transaction' => 'T' . $loan->id . '-' . $cycleId . '-' . (int) $amount, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
