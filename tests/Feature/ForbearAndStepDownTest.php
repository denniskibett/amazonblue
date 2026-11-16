<?php

namespace Tests\Feature;

use App\Http\Controllers\LoanStatementController;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\CycleBalance;
use App\Support\StepDown;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Owner, 16 Nov 2026: forbear a late loan from the side panel; step-down rates; the borrower's link hides waived penalty. */
class ForbearAndStepDownTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_forbearing_a_late_loan_removes_its_penalty_and_stops_new_penalty(): void
    {
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-25 10:00');
        $this->loan529();   // cycle 2 was due 16 Oct: 9 days late
        $cycle = LoanCycle::where('loan_id', 529)->orderByDesc('cycle_number')->first();
        $this->assertGreaterThan(0, CycleBalance::at($cycle)['penalty']);
        $staff = User::factory()->create();
        $staff->assignRole('manager');

        $this->actingAs($staff)->get(route('users.loans.show', [Loan::find(529)->user_id, 529]))->assertOk()->assertSee('Forbear this loan');
        $this->actingAs($staff)->post(route('loans.forbear', 529), ['until' => '2026-11-20', 'reason' => 'Agreed to pay by 20 Nov'])
            ->assertRedirect()->assertSessionHas('success');

        $cycle->refresh();
        $this->assertEqualsWithDelta(0, CycleBalance::at($cycle)['penalty'], 0.01, 'penalty already charged is removed');
        $this->assertEqualsWithDelta(0, CycleBalance::at($cycle, '2026-11-15')['penalty'], 0.01, 'and none accrues until the date');
        $this->assertSame('forbearance', Loan::find(529)->status);
        $this->assertStringContainsString('Agreed to pay by 20 Nov', $cycle->notes);
    }

    public function test_step_down_rates_halve_then_end_in_forbearance(): void
    {
        $this->assertSame([20.0, 20.0, 20.0, 10.0, 5.0, null], array_map(fn ($n) => StepDown::rate(20, $n), [1, 2, 3, 4, 5, 6]));
    }

    public function test_the_borrowers_link_leaves_out_penalty_that_was_taken_off_again(): void
    {
        $l = fn ($n, $text, $charge, $payment, $note = false) => (object) ['date' => '2026-10-01', 'n' => $n, 'text' => $text, 'ref' => null, 'charge' => $charge, 'payment' => $payment, 'balance' => 0, 'note' => $note];
        $data = ['lines' => [$l(1, 'Loan sent', 1000, 0), $l(1, 'Interest 20% on 1,000.00', 200, 0), $l(1, 'Late penalty · 3 days', 360, 0),
            $l(2, 'Rolled over from #1', 0, 0, true), $l(2, 'Carry-over adjustment', 0, 360), $l(2, 'Interest 20% on 1,200.00', 240, 0),
            $l(2, 'Late penalty · 1 day', 50, 0)], 'totals' => ['penalty' => 410.0]];
        $data['lines'][7] = $l(2, 'Forbearance', 0, 0, true);
        $data['lines'][7]->ref = 'to 30 Nov 2026 · 50.00 waived · pay by 30 Nov';

        $out = LoanStatementController::withoutWaivedPenalties($data);
        $texts = array_column($out['lines'], 'text');
        $this->assertNotContains('Late penalty · 3 days', $texts);
        $this->assertNotContains('Carry-over adjustment', $texts);
        $this->assertContains('Late penalty · 1 day', $texts, 'penalty still owed stays');
        $this->assertSame(1490.0, end($out['lines'])->balance, 'the closing balance does not change');
        $this->assertSame('to 30 Nov 2026 · pay by 30 Nov', end($out['lines'])->ref);
        $this->assertSame(50.0, $out['totals']['penalty']);
    }
}
