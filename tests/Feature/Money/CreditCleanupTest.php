<?php

namespace Tests\Feature\Money;

use App\Models\LoanCycle;
use App\Services\Loans\CreditCleanup;
use App\Services\Loans\CycleBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreditCleanupTest extends TestCase
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

    private function credit(int $loanId, int $cycleId, float $amount, string $date, int $id): void
    {
        DB::table('repayments')->insert(['id' => $id, 'loan_id' => $loanId, 'loan_cycle_id' => $cycleId, 'amount' => $amount, 'repayment_date' => $date,
            'transaction' => 'CREDIT DISCOUNT', 'kind' => 'discount', 'mode' => 'other', 'created_at' => $date, 'updated_at' => $date]);
    }

    public function test_cash_short_of_principal_plus_interest_gets_a_lower_rate_and_closes(): void
    {
        // 10,000 lent at 20%; borrower paid 11,000 two days late; 1,000 + penalty was "CREDIT DISCOUNT"
        $this->loanRow(29, 1, 10000, 10000, '2025-04-24', '2025-05-04', 'repaid', 1, 0);
        $this->cycleRow(29, 29, 1, 0, 2000, 12000, 20, '2025-04-24', '2025-05-04', 'completed', 'Initial');
        $this->repay(29, 29, 11000, '2025-05-06', 'CASH', 291, 'mpesa');
        $this->credit(29, 29, 3400, '2025-05-06', 292);

        $p = app(CreditCleanup::class)->proposals()->firstWhere('loan_id', 29);
        $this->assertSame('forgive', $p['action']);
        $this->assertSame(2, $p['grace_after']);
        $this->assertEquals(10.0, $p['rate_after']);
        $this->assertMoney(0, $p['owed_after']);

        app(CreditCleanup::class)->apply($p);
        $this->assertNotNull(DB::table('repayments')->where('id', 292)->value('deleted_at'));
        $b = CycleBalance::at(LoanCycle::findOrFail(29));
        $this->assertMoney(0, $b['final_outstanding']);
        $this->assertMoney(1000, $b['interest']);
        $this->assertSame('repaid', DB::table('loans')->where('id', 29)->value('status'));
    }

    public function test_cash_short_of_the_principal_writes_off_exactly_what_is_unpaid(): void
    {
        $this->loanRow(30, 1, 10000, 10000, '2025-04-24', '2025-05-04', 'repaid', 1, 0);
        $this->cycleRow(30, 30, 1, 0, 2000, 12000, 20, '2025-04-24', '2025-05-04', 'completed', 'Initial');
        $this->repay(30, 30, 8000, '2025-05-04', 'CASH', 301, 'mpesa');
        $this->credit(30, 30, 4000, '2025-05-04', 302);

        $p = app(CreditCleanup::class)->proposals()->firstWhere('loan_id', 30);
        $this->assertEquals(0.0, $p['rate_after']);
        $this->assertMoney(2000, $p['write_off']);

        app(CreditCleanup::class)->apply($p);
        $this->assertMoney(2000, DB::table('repayments')->where('loan_id', 30)->where('kind', 'bad_debt')->value('amount'));
        $this->assertMoney(0, CycleBalance::at(LoanCycle::findOrFail(30))['final_outstanding']);
        $this->assertSame('repaid', DB::table('loans')->where('id', 30)->value('status'));
    }

    public function test_a_credit_discount_pair_becomes_a_roll_over(): void
    {
        $this->loanRow(38, 1, 50000, 50000, '2025-05-03', '2025-05-13', 'repaid', 1, 0);
        $user = DB::table('loans')->where('id', 38)->value('user_id');
        $this->cycleRow(38, 38, 1, 0, 10000, 60000, 20, '2025-05-03', '2025-05-13', 'completed', 'Initial');
        $this->credit(38, 38, 60000, '2025-05-15', 381);
        $this->loanRow(61, 1, 60000, 60000, '2025-05-15', '2025-05-25', 'repaid', 1, 0);
        DB::table('loans')->where('id', 61)->update(['user_id' => $user]);
        $this->cycleRow(61, 61, 1, 0, 12000, 72000, 20, '2025-05-15', '2025-05-25', 'completed', 'Initial');
        DB::table('disbursements')->insert(['id' => 64, 'loan_id' => 61, 'amount' => 60000, 'transaction' => 'CREDIT DISCOUNT', 'mode' => 'other', 'kind' => 'discount',
            'disburse_date' => '2025-05-15', 'payment_date' => '2025-05-15', 'created_at' => now(), 'updated_at' => now()]);

        $p = app(CreditCleanup::class)->proposals()->firstWhere('loan_id', 38);
        $this->assertSame('rollover', $p['action']);
        $this->assertSame(61, $p['to_loan']);
        app(CreditCleanup::class)->apply($p);
        $this->assertSame('rollover', DB::table('repayments')->where('id', 381)->value('kind'));
        $this->assertSame('rollover', DB::table('disbursements')->where('id', 64)->value('kind'));
    }
}
