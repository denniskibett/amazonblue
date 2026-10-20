<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\Repayment;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Engine v2 + ledger (Stage 4 Phase 2): the ledger books each cycle's charges, so a loan's receivable is what
 * the borrower owes.
 */
class LedgerV2Test extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private Ledger $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.ledger.enabled' => true, 'lending.penalty.cap' => 'in_duplum']);
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        $this->ledger = app(Ledger::class);
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_receivable_follows_what_is_owed_through_lateness_payment_and_extension(): void
    {
        Carbon::setTestNow('2026-10-01 09:00');
        $this->loanRow(900, 1, 5000, 5000, '2026-10-01', '2026-10-11', 'disbursed', 1, 30);
        $cycle = LoanCycle::create(['loan_id' => 900, 'cycle_number' => 1, 'previous_balance' => 0, 'principal' => 5000,
            'interest_capitalized' => 1000, 'new_balance' => 6000, 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_days' => 0,
            'default_threshold_days' => 30, 'start_date' => '2026-10-01', 'due_date' => '2026-10-11', 'status' => 'active']);
        DB::transaction(fn () => Disbursement::create(['loan_id' => 900, 'loan_cycle_id' => $cycle->id, 'amount' => 5000, 'net_amount' => 5000,
            'transaction' => 'QD900', 'mode' => 'mpesa', 'disburse_date' => '2026-10-01', 'payment_date' => '2026-10-01']));
        $receivable = fn () => $this->ledger->balance($this->ledger->account(Loan::findOrFail(900), 'gl-receivable'));

        $this->assertEqualsWithDelta(6000, $receivable(), 0.001);            // 5,000 sent + 1,000 interest

        Carbon::setTestNow('2026-10-13 01:15');                               // two days late
        $this->artisan('ledger:accrue')->assertSuccessful();
        $this->assertEqualsWithDelta(7200, $receivable(), 0.001);            // + 2 x 600 penalty
        $this->artisan('ledger:accrue')->assertSuccessful();
        $this->assertEqualsWithDelta(7200, $receivable(), 0.001, 'running twice books nothing more');

        DB::transaction(fn () => Repayment::create(['loan_id' => 900, 'loan_cycle_id' => $cycle->id, 'amount' => 3000, 'repayment_date' => '2026-10-13', 'transaction' => 'QK900']));
        $this->assertEqualsWithDelta(4200, $receivable(), 0.001);

        Carbon::setTestNow('2026-10-14 10:00');
        DB::transaction(fn () => app(\App\Services\LoanCalculator::class)->executeRollover(Loan::findOrFail(900)));
        // cycle 1 closed owing 4,200 + 420 (10-14) = 4,620; cycle 2 lends 4,620 at 20% = 924 interest
        $this->assertEqualsWithDelta(4620 + 924, $receivable(), 0.001);
        $owed = \App\Services\Loans\CycleBalance::at(LoanCycle::where('loan_id', 900)->where('cycle_number', 2)->first())['final_outstanding'];
        $this->assertEqualsWithDelta($owed, $receivable(), 0.001);
        $this->assertSame([], $this->ledger->checkReceivables()['issues']);
        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
    }

    public function test_a_carry_that_differs_from_what_was_owed_is_booked_as_an_adjustment(): void
    {
        // 486: the plan carried 29,651.43 while cycle 1 owed 53,972.55 on 09-03 (penalty dropped)
        $this->loan486();
        Carbon::setTestNow('2026-10-20 10:00');
        $this->ledger->syncLoanCycle(LoanCycle::findOrFail(462));
        $this->assertEqualsWithDelta(24321.12, $this->ledger->balance($this->ledger->houseAccount('gl-rollover-adjustments')), 0.001); // forgiven
        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
    }

    public function test_engine_v1_books_no_charges(): void
    {
        config(['lending.engine' => 'v1']);
        $this->loan529();
        $this->assertSame([], $this->ledger->planLoanCycle(LoanCycle::findOrFail(606))['legs']);
    }
}
