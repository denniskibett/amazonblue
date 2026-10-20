<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WriteOffRepaidTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    public function test_a_loan_kept_repaid_gets_its_balance_written_off_and_reads_closed(): void
    {
        Storage::fake('local');
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        $this->loanTypes();
        $this->loan529();
        DB::table('loans')->where('id', 529)->update(['status' => 'repaid']);
        Carbon::setTestNow('2026-10-21 10:00');
        $owed = CycleBalance::at(LoanCycle::findOrFail(606))['final_outstanding'];

        $this->artisan('loans:write-off-repaid --dry-run')->assertSuccessful();
        $sha = hash('sha256', Storage::disk('local')->get('reports/write-off-repaid-2026-10-21.csv'));
        $this->artisan('loans:write-off-repaid --apply --confirm=' . $sha)->assertSuccessful();

        $row = DB::table('repayments')->where('transaction', 'WRITE OFF: kept repaid at restatement')->first();
        $this->assertEqualsWithDelta($owed, (float) $row->amount, 0.01);
        $this->assertSame('bad_debt', $row->kind);
        $this->assertEqualsWithDelta(0, CycleBalance::at(LoanCycle::findOrFail(606))['final_outstanding'], 0.01);
        $this->assertSame('repaid', Loan::findOrFail(529)->status);
        $this->assertSame('completed', LoanCycle::findOrFail(606)->status);

        $this->artisan('loans:write-off-repaid --dry-run')->assertSuccessful()->expectsOutputToContain('0 loan(s)');
        Carbon::setTestNow();
    }
}
