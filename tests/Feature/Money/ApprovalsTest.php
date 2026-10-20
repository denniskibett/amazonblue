<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Stage 4 Phase 5: maker-checker, approval limits, approver != disburser. */
class ApprovalsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $manager;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => true, 'lending.ledger.reversals' => true]);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');
        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function pending(int $id, float $amount): Loan
    {
        $this->loanRow($id, 1, $amount, $amount, '2026-10-21', '2026-10-31', 'pending', 1, 30);

        return Loan::findOrFail($id);
    }

    private function approve(User $by, Loan $loan)
    {
        return $this->actingAs($by)->putJson(route('loans.update', $loan), ['loan_type_id' => 1, 'amount' => (float) $loan->amount, 'borrow_date' => '2026-10-21',
            'status' => 'approved', 'broker_status' => 0, 'reason' => 'School fees for the new term', 'consent' => '1']);
    }

    public function test_within_the_limit_one_approver_is_enough(): void
    {
        $loan = $this->pending(1101, 50000);
        $this->approve($this->manager, $loan)->assertOk()->assertJson(['pending_approval' => false]);
        $this->assertSame('approved', $loan->fresh()->status);
    }

    public function test_above_the_limit_a_second_approver_must_approve_and_cannot_be_the_first(): void
    {
        $loan = $this->pending(1102, 80000);
        $this->approve($this->manager, $loan)->assertOk()->assertJson(['pending_approval' => true]);
        $this->assertSame('pending', $loan->fresh()->status);
        $id = DB::table('approvals')->value('id');

        $this->actingAs($this->manager)->post(route('staff.sign-offs.decide', $id), ['decision' => 'approve'])->assertSessionHas('error');
        $this->assertSame('pending', $loan->fresh()->status);

        $this->actingAs($this->admin)->post(route('staff.sign-offs.decide', $id), ['decision' => 'approve'])->assertSessionHas('success');
        $this->assertSame('approved', $loan->fresh()->status);
        $this->assertSame([$this->manager->id, $this->admin->id], [(int) DB::table('approvals')->value('requested_by'), (int) DB::table('approvals')->value('decided_by')]);
    }

    public function test_the_database_refuses_a_self_approval_too(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('approvals')->insert(['action' => 'approve_loan', 'subject_type' => 'loans', 'subject_id' => 1, 'status' => 'approved',
            'requested_by' => $this->manager->id, 'decided_by' => $this->manager->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_an_older_repayment_is_reversed_only_after_a_second_person_approves(): void
    {
        $this->loanRow(1103, 1, 5000, 5000, '2026-10-01', '2026-10-11', 'disbursed', 1, 30);
        $this->cycleRow(1103, 1103, 1, 0, 1000, 6000, 20, '2026-10-01', '2026-10-11', 'active', 'Initial');
        $r = Repayment::create(['loan_id' => 1103, 'loan_cycle_id' => 1103, 'amount' => 2000, 'repayment_date' => '2026-10-05', 'transaction' => 'QKAPPROVE1']);
        DB::table('repayments')->where('id', $r->id)->update(['created_at' => '2026-10-05 10:00:00']);

        $this->actingAs($this->manager)->deleteJson('/repayments/' . $r->id, ['reason' => 'Paid to the wrong loan'])->assertStatus(202);
        $this->assertSame(0, Repayment::where('reversal_of_id', $r->id)->count());

        $this->actingAs($this->admin)->post(route('staff.sign-offs.decide', DB::table('approvals')->value('id')), ['decision' => 'approve'])->assertSessionHas('success');
        $rev = Repayment::where('reversal_of_id', $r->id)->firstOrFail();
        $this->assertEquals(-2000, (float) $rev->amount);
        $this->assertStringContainsString('approved by ' . $this->admin->name, $rev->notes);
    }

    public function test_the_approver_cannot_also_disburse(): void
    {
        $loan = $this->pending(1104, 5000);
        $this->approve($this->manager, $loan)->assertOk();
        $this->manager->givePermissionTo('create disbursements');
        $this->actingAs($this->manager)->postJson(route('disbursements.store'), ['loan_id' => $loan->id, 'amount' => 5000,
            'disburse_date' => '2026-10-21', 'transaction' => 'QDAPPROVER', 'mode' => 'mpesa'])->assertStatus(422)->assertJsonValidationErrors('loan_id');
    }

    public function test_with_the_switch_off_one_person_does_it_as_before(): void
    {
        config(['lending.approvals.enabled' => false]);
        $loan = $this->pending(1105, 90000);
        $this->approve($this->manager, $loan)->assertOk();
        $this->assertSame('approved', $loan->fresh()->status);
    }
}
