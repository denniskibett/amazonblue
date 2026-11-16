<?php

namespace Tests\Feature;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** The full statement (owner, 16 Nov 2026): every cycle, oldest first, each with its own money rows. */
class LoanStatementTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_full_statement_lists_every_cycle_with_its_own_disbursements_and_repayments(): void
    {
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-17 10:00');
        $this->loan529();
        $loan = Loan::findOrFail(529);
        $staff = User::factory()->create();
        $staff->assignRole('manager');
        $cycles = LoanCycle::where('loan_id', 529)->orderBy('cycle_number')->get();
        $this->assertGreaterThanOrEqual(2, $cycles->count());

        $page = $this->actingAs($staff)->get(route('users.loans.statement', [$loan->user_id, 529]))->assertOk()
            ->assertSee('Full Loan Statement')->assertSee('Print full statement');
        $page->assertSeeInOrder(['Cycle #1', 'Cycle #2']);
        foreach (Disbursement::where('loan_id', 529)->get() as $d) {
            $page->assertSee(number_format((float) $d->amount, 2));
        }
        foreach (Repayment::where('loan_id', 529)->get() as $r) {
            $page->assertSee(number_format((float) $r->amount, 2));
        }

        $this->actingAs($staff)->get(route('users.loans.show', [$loan->user_id, 529]))->assertOk()
            ->assertSee(route('users.loans.statement', [$loan->user_id, 529]), false);
        $this->actingAs($staff)->get(route('users.loans.statement', [$loan->user_id + 999, 529]))->assertNotFound();
    }
}
