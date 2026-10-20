<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\LegacyChains;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Legacy ROLL OVER chains become one loan with cycles (owner, 21 Oct 2026).
 */
class LegacyChainsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');

        // A: 5,000 sent 01-01, due 01-11; rolled on 01-11: ROLL OVER 6,000 closes A, B opens with ROLL OVER 6,000
        $this->loanRow(1001, 1, 5000, 5000, '2026-01-01', '2026-01-11', 'repaid', 1, 30);
        $this->cycleRow(2001, 1001, 1, 0, 1000, 6000, 20, '2026-01-01', '2026-01-11', 'active', 'Initial');
        DB::table('disbursements')->insert(['id' => 3001, 'loan_id' => 1001, 'loan_cycle_id' => 2001, 'amount' => 5000, 'net_amount' => 5000, 'transaction' => 'QA1', 'kind' => 'cash', 'mode' => 'mpesa', 'disburse_date' => '2026-01-01', 'payment_date' => '2026-01-01']);
        DB::table('repayments')->insert(['id' => 4001, 'loan_id' => 1001, 'loan_cycle_id' => 2001, 'amount' => 6000, 'transaction' => 'ROLL OVER', 'kind' => 'rollover', 'repayment_date' => '2026-01-11']);

        $uid = DB::table('loans')->where('id', 1001)->value('user_id');
        $this->loanRow(1002, 1, 6000, 6000, '2026-01-11', '2026-01-21', 'repaid', 1, 30);
        DB::table('loans')->where('id', 1002)->update(['user_id' => $uid]);
        $this->cycleRow(2002, 1002, 1, 0, 1200, 7200, 20, '2026-01-11', '2026-01-21', 'active', 'Initial');
        DB::table('disbursements')->insert(['id' => 3002, 'loan_id' => 1002, 'loan_cycle_id' => 2002, 'amount' => 6000, 'net_amount' => 6000, 'transaction' => 'ROLL OVER', 'kind' => 'rollover', 'mode' => 'none', 'disburse_date' => '2026-01-11', 'payment_date' => '2026-01-11']);
        DB::table('repayments')->insert(['id' => 4002, 'loan_id' => 1002, 'loan_cycle_id' => 2002, 'amount' => 7200, 'transaction' => 'QB1', 'kind' => 'cash', 'repayment_date' => '2026-01-20']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_chain_is_found_and_merged_into_its_first_loan(): void
    {
        $found = app(LegacyChains::class)->detect();
        $this->assertSame([1001, 1002], $found['chains'][0]['loans']);

        $this->artisan('loans:merge-chains --dry-run')->assertSuccessful();
        $this->assertNotNull(Loan::find(1002), 'dry run changes nothing');
        $sha = hash('sha256', Storage::disk('local')->get('reports/merge-chains-2026-10-21.csv'));
        $this->artisan('loans:merge-chains --apply --confirm=wrong')->assertFailed();
        $this->artisan('loans:merge-chains --apply --confirm=' . $sha)->assertSuccessful();

        $this->assertNull(Loan::find(1002), 'the continuation is hidden');
        $this->assertSame(1001, (int) Loan::withTrashed()->find(1002)->merged_into_loan_id);
        $this->assertSame([1, 2], LoanCycle::where('loan_id', 1001)->orderBy('cycle_number')->pluck('cycle_number')->all());
        $this->assertSame(1001, (int) DB::table('repayments')->where('id', 4002)->value('loan_id'));
        $this->assertSame(1001, (int) DB::table('disbursements')->where('id', 3002)->value('loan_id'));

        // cycle 1 was carried (its ROLL OVER row is the carry, not a payment), cycle 2 paid in full
        $c1 = CycleBalance::at(LoanCycle::findOrFail(2001));
        $this->assertEqualsWithDelta(6000, $c1['owed_on_close'], 0.01);
        $this->assertEqualsWithDelta(0, $c1['carry_difference'], 0.01);
        $this->assertEqualsWithDelta(0, CycleBalance::at(LoanCycle::findOrFail(2002))['final_outstanding'], 0.01);

        $loan = Loan::findOrFail(1001);
        $this->assertSame('repaid', $loan->status);
        $this->assertEqualsWithDelta(5000, (float) $loan->original_amount, 0.01);
        $this->assertEqualsWithDelta(6000, (float) $loan->amount, 0.01);
    }

    public function test_a_continuation_with_no_closed_loan_nearby_is_left_alone_and_listed(): void
    {
        DB::table('repayments')->where('id', 4001)->update(['repayment_date' => '2025-12-01']);
        $found = app(LegacyChains::class)->detect();
        $this->assertSame([], $found['chains']);
        $this->assertSame(1002, $found['unmatched'][0]['loan']);
    }
}
