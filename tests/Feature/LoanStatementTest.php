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
            ->assertSee('Loan Statement')->assertSee('End of statement')->assertSee('Print full statement');
        $page->assertSeeInOrder(['Date', 'Cycle', 'Description', 'Charges', 'Payments', 'Balance (KES)', 'Rolled over from #1']);
        foreach (Disbursement::where('loan_id', 529)->where('kind', 'cash')->get() as $d) {
            $page->assertSee(number_format((float) $d->amount, 2));
        }
        foreach (Repayment::where('loan_id', 529)->where('kind', 'cash')->get() as $r) {
            $page->assertSee(number_format((float) $r->amount, 2));
        }

        // the running balance ends on what the loan engine says is owed
        $lines = $page->viewData('lines');
        $owed = \App\Services\Loans\CycleBalance::at($cycles->last())['final_outstanding'];
        $this->assertEqualsWithDelta($owed, end($lines)->balance, 0.01);
        $this->assertEqualsWithDelta($owed, $page->viewData('balance'), 0.01);
        $this->assertEqualsWithDelta(end($lines)->balance, collect($lines)->sum('charge') - collect($lines)->sum('payment'), 0.01);

        $this->actingAs($staff)->get(route('users.loans.show', [$loan->user_id, 529]))->assertOk()
            ->assertSee(route('users.loans.statement', [$loan->user_id, 529]), false);
        $this->actingAs($staff)->get(route('users.loans.statement', [$loan->user_id + 999, 529]))->assertNotFound();
    }
    public function test_forbearance_shows_with_its_reason_and_interest_is_posted_on_the_due_date(): void
    {
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-25 10:00');
        $this->loan529();   // cycle 2 was due 16 Oct
        $c = LoanCycle::where('loan_id', 529)->orderByDesc('cycle_number')->first();
        $c->update(['penalty_waived_until' => '2026-10-30', 'notes' => "Loan rollover\nForbearance, pay by 30 Oct 2026 (owner, 17 Oct 2026)"]);
        $staff = User::factory()->create();
        $staff->assignRole('manager');

        $page = $this->actingAs($staff)->get(route('users.loans.statement', [Loan::findOrFail(529)->user_id, 529]))->assertOk()
            ->assertSee('Forbearance')->assertSee('to 30 Oct 2026')->assertSee('waived · pay by 30 Oct 2026');
        $interest = collect($page->viewData('lines'))->first(fn ($l) => $l->n === $c->cycle_number && str_starts_with($l->text, 'Interest'));
        $this->assertSame($c->due_date->toDateString(), $interest->date, 'interest is posted at the end of the cycle');
        $this->assertStringContainsString('on ' . number_format((float) \App\Services\Loans\CycleBalance::at($c)['principal'], 2), $interest->text);
    }
}
