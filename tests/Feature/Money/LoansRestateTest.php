<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * loans:restate (Stage 4 Phase 2): dry-run CSV + SHA, apply only with the signed SHA and engine v2.
 */
class LoansRestateTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['lending.engine' => 'v1', 'lending.penalty.cap' => 'in_duplum']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-20 10:00');
        $this->loan461();                                         // disbursed, owes 44,352 on cycle 2
        $this->loan529();                                         // overdue
        DB::table('loans')->where('id', 529)->update(['status' => 'repaid']); // "repaid" but owes (P1 #10)
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dry_run_writes_the_csv_and_changes_nothing(): void
    {
        $before = DB::table('loans')->orderBy('id')->get()->toArray();
        $this->artisan('loans:restate --dry-run')->assertSuccessful()->expectsOutputToContain('SHA-256: ');

        $csv = Storage::disk('local')->get('reports/restate-2026-10-20.csv');
        $this->assertStringContainsString('loan_id,user_id,status_old,status_new', $csv);
        $this->assertStringContainsString('REPAID_BUT_OWES', $csv);
        $this->assertEquals($before, DB::table('loans')->orderBy('id')->get()->toArray());
        $this->assertNull(LoanCycle::findOrFail(606)->closing_balance);
    }

    public function test_apply_needs_v2_and_the_signed_sha(): void
    {
        $this->artisan('loans:restate --dry-run')->assertSuccessful();
        $sha = hash('sha256', Storage::disk('local')->get('reports/restate-2026-10-20.csv'));

        $this->artisan('loans:restate --apply --confirm=' . $sha)->assertFailed();   // still v1
        config(['lending.engine' => 'v2']);
        $this->artisan('loans:restate --apply --confirm=wrong')->assertFailed();

        // the data moved after signing: refused
        $this->repay(461, 608, 1000, '2026-10-20', 'AFTER-SIGNING');
        $this->artisan('loans:restate --apply --confirm=' . $sha)->assertFailed();
    }

    public function test_apply_restates_loans_from_their_cycles_and_leaves_repaid_but_owing_to_the_owner(): void
    {
        $repayments = DB::table('repayments')->orderBy('id')->get()->toArray();
        $this->artisan('loans:restate --dry-run')->assertSuccessful();
        $sha = hash('sha256', Storage::disk('local')->get('reports/restate-2026-10-20.csv'));
        config(['lending.engine' => 'v2']);

        $this->artisan('loans:restate --apply --confirm=' . $sha)->assertSuccessful();

        $l461 = Loan::findOrFail(461);
        $this->assertEqualsWithDelta(36960, (float) $l461->amount, 0.01);       // current principal
        $this->assertEqualsWithDelta(85600, (float) $l461->original_amount, 0.01);
        $this->assertSame('disbursed', $l461->status);
        $c = LoanCycle::findOrFail(608);
        $this->assertEqualsWithDelta(44352, (float) $c->closing_balance, 0.01);
        $this->assertSame('10.000', (string) $c->penalty_rate);                 // product terms recorded

        $this->assertSame('repaid', Loan::findOrFail(529)->status, 'repaid but owes: the owner decides');
        $this->assertEquals($repayments, DB::table('repayments')->orderBy('id')->get()->toArray(), 'repayments untouched');
    }

    public function test_reopen_puts_a_named_repaid_loan_back_to_owing(): void
    {
        $this->artisan('loans:restate --dry-run')->assertSuccessful();
        $sha = hash('sha256', Storage::disk('local')->get('reports/restate-2026-10-20.csv'));
        config(['lending.engine' => 'v2']);

        $this->artisan('loans:restate --apply --confirm=' . $sha . ' --reopen=529')->assertSuccessful();
        $this->assertSame('overdue', Loan::findOrFail(529)->status);
    }
}
