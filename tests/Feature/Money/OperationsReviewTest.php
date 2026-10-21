<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperationsReviewTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.ledger.enabled' => true]);
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_open_the_loan_discrepancy_review(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('staff.operations-review', ['section' => 'receivables']))
            ->assertOk()->assertSee('Ledger receivable vs latest cycle')->assertSee('No receivable differences match these filters.');
    }

    public function test_discrepancy_table_shows_borrower_and_two_latest_cycles_with_filters(): void
    {
        $this->loan529();
        $loan = Loan::with('user')->findOrFail(529);
        \Illuminate\Support\Facades\DB::table('repayments')->insert([
            'loan_id' => 529, 'loan_cycle_id' => 593, 'amount' => 12000, 'repayment_date' => '2026-10-05',
            'transaction' => 'ROLL OVER', 'kind' => 'rollover', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->mock(\App\Services\Ledger::class)
            ->shouldReceive('checkReceivables')->once()->andReturn(['checked' => 1, 'issues' => [[
                'loan' => 529, 'owed' => 16258.00, 'ledger' => 43258.00, 'difference' => 27000.00,
            ]]]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('staff.operations-review', [
            'section' => 'receivables', 'q' => substr($loan->user->name, 0, 5), 'status' => 'overdue',
            'due_from' => '2026-10-16', 'due_to' => '2026-10-16', 'rollover' => 'yes',
        ]))->assertOk()
            ->assertSee($loan->user->name)
            ->assertSee('Cycle 2')
            ->assertSee('2026-10-06 to 2026-10-16')
            ->assertSee('Cycle 1')
            ->assertSee('2026-09-26 to 2026-10-06')
            ->assertSee('Stored outstanding: KES 14,780.00')
            ->assertSee('Calculated outstanding: KES 14,780.00')
            ->assertSee('Amount difference: KES 0.00')
            ->assertSee('1 matching discrepancy');
    }

    public function test_misaligned_latest_cycle_shows_correction_and_forbearance_actions(): void
    {
        $this->loan529();
        LoanCycle::where('loan_id', 529)->where('cycle_number', 2)->update(['due_date' => '2026-10-18']);
        Loan::whereKey(529)->update(['due_date' => '2026-10-18']);
        $this->mock(\App\Services\Ledger::class)
            ->shouldReceive('checkReceivables')->once()->andReturn(['checked' => 1, 'issues' => [[
                'loan' => 529, 'owed' => 16258.00, 'ledger' => 43258.00, 'difference' => 27000.00,
            ]]]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('staff.operations-review', ['section' => 'receivables']))
            ->assertOk()
            ->assertSee('2026-10-16 · differs')
            ->assertSee('Record forbearance')
            ->assertSee('Align to product date:')
            ->assertSee('Align due date');
    }

    public function test_admin_can_record_forbearance_for_a_reviewed_active_cycle(): void
    {
        Carbon::setTestNow('2026-10-17 10:00');
        $this->loan529();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('staff.operations-review.forbearance', 529), [
            'until' => '2026-10-31',
            'reason' => 'Borrower extension approved by staff',
        ])->assertRedirect(route('staff.operations-review', ['section' => 'receivables']));

        $this->assertSame(Loan::STATUS_FORBEARANCE, Loan::findOrFail(529)->status);
        $this->assertSame('2026-10-31', LoanCycle::where('loan_id', 529)->where('cycle_number', 2)->firstOrFail()->penalty_waived_until->toDateString());
        $this->assertDatabaseHas('audit_logs', ['table_name' => 'loans', 'record_id' => 529, 'action' => 'update']);
        $this->assertDatabaseHas('audit_logs', ['table_name' => 'loans', 'record_id' => 529, 'new_values->event' => 'forbearance_granted']);
    }

    public function test_admin_can_correct_the_cycle_due_date_without_changing_repayments(): void
    {
        Carbon::setTestNow('2026-10-17 10:00');
        $this->loan529();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('staff.operations-review.due-date', 529), [
            'reason' => 'Align cycle due date to approved repayment schedule',
        ])->assertRedirect(route('staff.operations-review', ['section' => 'receivables']))
            ->assertSessionHas('success');

        $this->assertSame('2026-10-16', Carbon::parse(Loan::findOrFail(529)->due_date)->toDateString());
        $this->assertSame('2026-10-16', Carbon::parse(LoanCycle::where('loan_id', 529)->where('cycle_number', 2)->firstOrFail()->due_date)->toDateString());
        $this->assertDatabaseHas('repayments', ['loan_id' => 529, 'transaction' => 'UJ7CQ9KQCR', 'amount' => 2500]);
        $this->assertDatabaseHas('audit_logs', ['table_name' => 'loans', 'record_id' => 529, 'new_values->event' => 'cycle_due_date_corrected']);
    }
}