<?php

namespace Tests\Feature\Money;

use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Loans\LoanJourney;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Second-person and audit gaps closed on 3 Nov 2026 (roles and journey audit). */
class ControlGapsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $manager;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => true]);
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

    public function test_a_loan_created_already_approved_cannot_be_disbursed_by_its_creator(): void
    {
        $this->loanRow(1201, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'approved', 1, 30);
        $this->actingAs($this->manager);
        AuditTrail::record('loans', 1201, 'create', null, ['status' => 'approved', 'amount' => 5000]);

        $this->postJson(route('disbursements.store'), ['loan_id' => 1201, 'amount' => 5000, 'disburse_date' => '2026-10-21',
            'transaction' => 'QDCREATOR', 'mode' => 'mpesa'])->assertStatus(422)->assertJsonValidationErrors('loan_id');
    }

    public function test_an_admin_may_disburse_a_loan_they_approved_and_it_is_audited(): void
    {
        $this->loanRow(1205, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'approved', 1, 30);
        $this->actingAs($this->admin);
        AuditTrail::record('loans', 1205, 'update', ['status' => 'pending'], ['status' => 'approved']);

        $this->postJson(route('disbursements.store'), ['loan_id' => 1205, 'amount' => 5000, 'disburse_date' => '2026-10-21',
            'transaction' => 'QDSELFADMIN', 'mode' => 'mpesa'])->assertCreated();
        $this->assertTrue(AuditLog::where('table_name', 'loans')->where('record_id', 1205)->where('new_values->event', 'self_disbursed')->exists());
    }

    public function test_accepting_a_shortfall_waits_for_a_second_person(): void
    {
        $this->loanRow(1202, 1, 5000, 5000, '2026-10-01', '2026-10-11', 'disbursed', 1, 30);

        $this->actingAs($this->manager)->post(route('loans.shortfall.accept', 1202), ['reason' => 'Paid short by agreement'])->assertSessionHas('success');

        $this->assertSame('accept_shortfall', DB::table('approvals')->value('action'));
        $this->assertSame('pending', DB::table('approvals')->value('status'));
    }

    public function test_deleting_a_disbursement_waits_for_a_second_person_then_happens(): void
    {
        $this->loanRow(1203, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'disbursed', 1, 30);
        $id = DB::table('disbursements')->insertGetId(['loan_id' => 1203, 'amount' => 5000, 'processing_fee' => 0, 'net_amount' => 5000,
            'transaction' => 'QDDELETE', 'mode' => 'mpesa', 'disburse_date' => '2026-10-21', 'payment_date' => '2026-10-21', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->manager)->delete(route('disbursements.destroy', $id), ['reason' => 'Entered twice'])->assertSessionHas('success');
        $this->assertNull(DB::table('disbursements')->where('id', $id)->value('deleted_at'));
        $approval = DB::table('approvals')->where('action', 'delete_disbursement')->value('id');

        $this->actingAs($this->manager)->post(route('staff.sign-offs.decide', $approval), ['decision' => 'approve'])->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('staff.sign-offs.decide', $approval), ['decision' => 'approve'])->assertSessionHas('success');
        $this->assertNotNull(DB::table('disbursements')->where('id', $id)->value('deleted_at'));
    }

    public function test_an_auditor_cannot_approve_a_cash_up(): void
    {
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');
        $this->actingAs($auditor)->post(route('staff.end-of-day.approve', 1))->assertForbidden();
    }

    public function test_sign_in_is_in_the_audit_log(): void
    {
        $user = User::factory()->create();
        event(new \Illuminate\Auth\Events\Login('web', $user, false));

        $this->assertTrue(AuditLog::where('table_name', 'users')->where('record_id', $user->id)->where('new_values->event', 'login')->exists());
    }

    public function test_the_journey_reads_the_loan_and_its_money_and_skips_non_changes(): void
    {
        $this->loanRow(1204, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'pending', 1, 30);
        $this->actingAs($this->manager);
        AuditTrail::record('loans', 1204, 'create', null, ['status' => 'pending', 'amount' => 5000]);
        AuditTrail::record('loans', 1204, 'update', ['status' => 'pending'], ['status' => 'approved']);
        AuditTrail::record('loans', 1204, 'update', ['due_date' => '2026-10-31'], ['due_date' => '2026-10-31 00:00:00']);
        $d = DB::table('disbursements')->insertGetId(['loan_id' => 1204, 'amount' => 5000, 'processing_fee' => 0, 'net_amount' => 5000,
            'transaction' => 'QDJOURNEY', 'mode' => 'mpesa', 'disburse_date' => '2026-10-21', 'payment_date' => '2026-10-21', 'created_at' => now(), 'updated_at' => now()]);
        AuditTrail::record('disbursements', $d, 'create', null, ['amount' => 5000, 'mode' => 'mpesa']);

        $steps = app(LoanJourney::class)->for(Loan::findOrFail(1204))->pluck('text')->all();

        $this->assertSame(['Loan created for KES 5,000.00 (pending)', 'Status pending → approved', 'Disbursed KES 5,000.00 by mpesa'], $steps);
    }
}
