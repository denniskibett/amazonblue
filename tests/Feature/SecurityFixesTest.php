<?php

namespace Tests\Feature;

use App\Models\Broker;
use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Fixes from the 3 Nov 2026 code audit: who may change whom, broker scoping, disbursement guards, partner fields. */
class SecurityFixesTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => false, 'lending.ledger.enabled' => false, 'lending.split.enabled' => false]);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_a_manager_cannot_change_or_delete_a_super_admin_or_an_admin(): void
    {
        $manager = $this->user('manager');
        $boss = $this->user('super-admin');
        $admin = $this->user('admin');
        $hash = $boss->password;

        $this->actingAs($manager)->put(route('users.update', $boss), ['name' => 'x', 'email' => $boss->email, 'role' => 'borrower', 'password' => 'Taken0ver!x'])
            ->assertForbidden();
        $this->actingAs($manager)->delete(route('users.destroy', $admin))->assertForbidden();

        $this->assertSame($hash, $boss->fresh()->password);
        $this->assertTrue($boss->fresh()->hasRole('super-admin'));
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_nobody_deletes_their_own_account(): void
    {
        $boss = $this->user('super-admin');
        $this->actingAs($boss)->delete(route('users.destroy', $boss))->assertForbidden();
        $this->assertNotNull(User::find($boss->id));
    }

    public function test_a_short_term_partner_has_no_staff_loan_pages(): void
    {
        // the broker role was retired (3 Nov 2026): introducers are partners with terms in `brokers`
        $brokerUser = $this->user('partner');
        $broker = Broker::create(['user_id' => $brokerUser->id, 'cert_no' => 'BRK-1', 'interest_client' => 20, 'interest_broker' => 10,
            'penalty_client' => 1, 'penalty_broker' => 0.5]);

        $this->loanRow(1601, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'disbursed', 1, 30);
        $this->cycleRow(1601, 1601, 1, 0, 1000, 6000, 20, '2026-10-21', '2026-10-31', 'active', 'Initial');
        $this->loanRow(1602, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'disbursed', 1, 30);
        $this->cycleRow(1602, 1602, 1, 0, 1000, 6000, 20, '2026-10-21', '2026-10-31', 'active', 'Initial');
        DB::table('borrowers')->updateOrInsert(['user_id' => Loan::find(1601)->user_id], ['broker_id' => $broker->id, 'client_type' => 'individual', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertTrue($brokerUser->isShortTermPartner());
        $this->assertFalse($brokerUser->isStaff());
        $this->actingAs($brokerUser)->getJson(route('loans.cycles', 1601))->assertForbidden();
        $this->actingAs($brokerUser)->getJson(route('loans.cycles', 1602))->assertForbidden();
    }

    private function disburse(User $by, int $loanId, string $ref)
    {
        return $this->actingAs($by)->postJson(route('disbursements.store'), ['loan_id' => $loanId, 'amount' => 5000,
            'disburse_date' => '2026-10-21', 'transaction' => $ref, 'mode' => 'mpesa']);
    }

    public function test_the_same_disbursement_reference_is_refused_on_a_loan(): void
    {
        $teller = $this->user('teller');
        $teller->givePermissionTo('create disbursements');
        $this->loanRow(1611, 1, 5000, 5000, '2026-10-21', '2026-10-31', 'approved', 1, 30);

        $this->disburse($teller, 1611, 'QDDOUBLE01')->assertCreated();
        $this->disburse($teller, 1611, 'QDDOUBLE01')->assertStatus(422)->assertJsonValidationErrors('transaction');
        $this->assertSame(1, Disbursement::where('loan_id', 1611)->count());
    }

    public function test_money_is_not_sent_on_a_closed_or_pending_loan(): void
    {
        $teller = $this->user('teller');
        $teller->givePermissionTo('create disbursements');
        foreach ([1621 => 'repaid', 1622 => 'written_off', 1623 => 'rejected', 1624 => 'pending'] as $id => $status) {
            $this->loanRow($id, 1, 5000, 5000, '2026-10-21', '2026-10-31', $status, 1, 30);
            $this->disburse($teller, $id, 'QDCLOSED' . ($id - 1600))->assertStatus(422)->assertJsonValidationErrors('loan_id');
        }
        $this->assertSame(0, Disbursement::count());
    }

    public function test_a_partner_edit_cannot_set_balances_or_house_flags(): void
    {
        $admin = $this->user('admin');
        $partner = Partner::create(['name' => 'Line Partner', 'email' => 'line@example.test', 'type' => 'individual', 'status' => 'active',
            'risk_tolerance' => 'moderate', 'current_balance' => 100, 'is_house_account' => false]);

        $this->actingAs($admin)->putJson('/partners/update/' . $partner->id, ['name' => 'Renamed', 'broker_rate' => 35,
            'current_balance' => 9999999, 'is_house_account' => true, 'wallet_slug' => 'gl-cash'])->assertOk();

        $p = $partner->fresh();
        $this->assertSame('Renamed', $p->name);
        $this->assertEquals(35, (float) $p->broker_rate);
        $this->assertEquals(100, (float) $p->current_balance);
        $this->assertFalse((bool) $p->is_house_account);
        $this->assertNotSame('gl-cash', $p->wallet_slug);
    }
}
