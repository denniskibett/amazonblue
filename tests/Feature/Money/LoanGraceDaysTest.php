<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\CycleBalance;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Grace days set by staff on a loan's current cycle (owner, 1 Nov 2026). */
class LoanGraceDaysTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        Carbon::setTestNow('2026-11-01 12:00');
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_one_grace_day_clears_the_penalty_of_a_loan_paid_one_day_late(): void
    {
        // 5,000 + 20% = 6,000 due 20 Oct, paid in full on 21 Oct
        $this->loanRow(40, 1, 5000.00, 5000.00, '2026-10-10', '2026-10-20', 'overdue', 1, 0);
        $this->cycleRow(40, 40, 1, 0, 1000.00, 6000.00, 20, '2026-10-10', '2026-10-20', 'active', 'Initial');
        $this->repay(40, 40, 6000.00, '2026-10-21', 'QJK1234567');
        $this->assertGreaterThan(0, CycleBalance::at(LoanCycle::findOrFail(40))['penalty'], 'one day of penalty before grace');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('loans.grace', 40), ['grace_days' => 1, 'reason' => 'M-Pesa came a day late'])
            ->assertRedirect()->assertSessionHas('success');

        $cycle = LoanCycle::findOrFail(40);
        $this->assertSame(1, (int) $cycle->grace_days);
        $this->assertSame('2026-10-20', $cycle->due_date->toDateString(), 'the due date is kept');
        $b = CycleBalance::at($cycle);
        $this->assertMoney(0, $b['penalty']);
        $this->assertMoney(0, $b['final_outstanding']);
        $this->assertSame('repaid', Loan::findOrFail(40)->status);
    }

    public function test_a_borrower_cannot_set_grace_days(): void
    {
        $this->loanRow(41, 1, 5000.00, 5000.00, '2026-10-10', '2026-10-20', 'overdue', 1, 0);
        $this->cycleRow(41, 41, 1, 0, 1000.00, 6000.00, 20, '2026-10-10', '2026-10-20', 'active', 'Initial');

        $borrower = User::factory()->create();
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->post(route('loans.grace', 41), ['grace_days' => 5, 'reason' => 'please'])->assertSessionMissing('success');
        $this->assertNull(LoanCycle::findOrFail(41)->grace_days);
    }
}
