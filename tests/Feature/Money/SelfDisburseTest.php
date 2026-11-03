<?php

namespace Tests\Feature\Money;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditTrail;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Small team (owner, 3 Nov 2026): admin and super-admin may disburse a loan they approved; others may not. */
class SelfDisburseTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => true]);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function approvedBy(string $role, int $loanId): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo('create disbursements');
        $this->loanRow($loanId, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'approved', 1, 30);
        $this->actingAs($user);
        AuditTrail::record('loans', $loanId, 'update', ['status' => 'pending'], ['status' => 'approved']);

        return $user;
    }

    private function disburse(int $loanId, string $ref)
    {
        return $this->postJson(route('disbursements.store'), ['loan_id' => $loanId, 'amount' => 5000, 'disburse_date' => '2026-10-21',
            'transaction' => $ref, 'mode' => 'mpesa']);
    }

    public function test_an_admin_may_disburse_a_loan_they_approved_and_it_is_audited(): void
    {
        $this->approvedBy('admin', 1501);
        $this->disburse(1501, 'QDSELFADM1')->assertCreated();
        $this->assertTrue(AuditLog::where('table_name', 'loans')->where('record_id', 1501)->where('new_values->event', 'self_disbursed')->exists());
    }

    public function test_a_manager_still_may_not(): void
    {
        $this->approvedBy('manager', 1502);
        $this->disburse(1502, 'QDSELFMGR1')->assertStatus(422)->assertJsonValidationErrors('loan_id');
    }
}
