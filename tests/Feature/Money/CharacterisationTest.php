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
    use RefreshDatabase, AuditLoanFixtures;

    private LoanCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v1']); // these pin the old engine while it can still be switched back to
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

    private function balance(int $loanId, int $cycleNumber): array
    {
        $loan = Loan::findOrFail($loanId);

        return $this->calc->calculateCycleBalance($loan, $loan->cycles()->where('cycle_number', $cycleNumber)->firstOrFail());
    }

}
