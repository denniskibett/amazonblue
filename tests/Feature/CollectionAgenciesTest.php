<?php

namespace Tests\Feature;

use App\Models\CollectionAgency;
use App\Models\CollectionPlacement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Outside debt collection agencies (owner, 3 Nov 2026): staff place late and forbearance loans; collectors see only theirs. */
class CollectionAgenciesTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => false, 'lending.ledger.enabled' => false, 'lending.split.enabled' => false]);
        $this->loanTypes();
        Carbon::setTestNow('2026-11-03 10:00');
        $this->officer = User::factory()->create(['email_verified_at' => now()]);
        $this->officer->assignRole('recovery-officer');

        $this->loanRow(1701, 1, 5000, 5000, '2026-10-01', '2026-10-11', 'overdue', 1, 30);
        $this->cycleRow(1701, 1701, 1, 0, 1000, 6000, 20, '2026-10-01', '2026-10-11', 'active', 'Initial');
        $this->loanRow(1702, 1, 5000, 5000, '2026-10-01', '2026-10-11', 'forbearance', 1, 30);
        $this->cycleRow(1702, 1702, 1, 0, 1000, 6000, 20, '2026-10-01', '2026-10-11', 'active', 'Initial');
        $this->loanRow(1703, 1, 5000, 5000, '2026-10-25', '2026-11-04', 'disbursed', 1, 30);   // not late: can't be placed
        $this->cycleRow(1703, 1703, 1, 0, 1000, 6000, 20, '2026-10-25', '2026-11-04', 'active', 'Initial');
    }

    private function agencyWithCollector(string $name): array
    {
        $this->actingAs($this->officer)->post(route('staff.agencies.store'), ['name' => $name, 'agreement_ref' => 'DSA-' . $name, 'commission_rate' => 15])->assertRedirect();
        $agency = CollectionAgency::where('name', $name)->firstOrFail();
        $this->post(route('staff.agencies.collectors.store', $agency), ['name' => $name . ' Collector', 'email' => strtolower($name) . '@agency.test', 'phone' => '07' . random_int(10000000, 99999999), 'password' => 'long-enough-pass'])
            ->assertSessionHasNoErrors();

        return [$agency, User::where('email', strtolower($name) . '@agency.test')->firstOrFail()];
    }

    public function test_staff_place_late_and_forbearance_loans_and_the_collector_sees_only_them(): void
    {
        [$agency, $collector] = $this->agencyWithCollector('Ndege');
        $this->assertTrue($collector->hasRole('collector'));
        $this->assertFalse($collector->isStaff());

        $this->post(route('staff.agencies.place', $agency), ['loan_ids' => [1701, 1702, 1703], 'ends_on' => '2027-02-03', 'notice_sent_on' => '2026-10-20'])
            ->assertSessionHas('success', '2 loan(s) placed with Ndege.');
        $this->assertSame(['1701', '1702'], CollectionPlacement::pluck('loan_id')->map(fn ($v) => (string) $v)->sort()->values()->all());

        $this->actingAs($collector)->get(route('collect.index'))->assertOk()->assertSee('#1701')->assertSee('#1702')->assertDontSee('#1703');
        $this->get(route('dashboard'))->assertRedirect(route('account.home'));   // not the staff app
        $this->get(route('account.home'))->assertRedirect(route('collect.index'));
    }

    public function test_another_agency_and_a_returned_placement_are_not_visible(): void
    {
        [$a, $collectorA] = $this->agencyWithCollector('Alpha');
        [$b] = $this->agencyWithCollector('Beta');
        $this->actingAs($this->officer)->post(route('staff.agencies.place', $b), ['loan_ids' => [1701], 'ends_on' => '2027-01-01', 'notice_sent_on' => '2026-10-20']);
        $placement = CollectionPlacement::firstOrFail();

        $this->actingAs($collectorA)->get(route('collect.show', $placement->id))->assertNotFound();
        $this->actingAs($this->officer)->post(route('staff.placements.take-back', $placement))->assertRedirect();
        $this->assertSame('returned', $placement->fresh()->status);
    }

    public function test_contact_waits_for_the_notice_period(): void
    {
        [$agency, $collector] = $this->agencyWithCollector('Gamma');
        $this->post(route('staff.agencies.place', $agency), ['loan_ids' => [1701], 'ends_on' => '2027-01-01']);   // borrower not told yet
        $placement = CollectionPlacement::firstOrFail();

        $this->actingAs($collector)->get(route('collect.show', $placement->id))->assertOk()->assertSee('contact this borrower yet');
        $this->post(route('collect.contact', $placement->id), ['channel' => 'call', 'outcome' => 'Reached'])->assertForbidden();

        $this->actingAs($this->officer)->post(route('staff.placements.notice', $placement), ['notice_sent_on' => '2026-10-27']);
        $this->actingAs($collector)->post(route('collect.contact', $placement->id), ['channel' => 'call', 'outcome' => 'Reached · promised', 'promise_amount' => 2000, 'promise_date' => '2026-11-10'])
            ->assertSessionHas('success');
        $this->assertSame(1, DB::table('collection_contacts')->where('loan_id', 1701)->count());
        $this->get(route('collect.profile', $placement->id))->assertOk()->assertSee('GAMMA · CONFIDENTIAL');
    }

    public function test_only_staff_with_the_permission_manage_agencies(): void
    {
        $teller = User::factory()->create(['email_verified_at' => now()]);
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.agencies'))->assertForbidden();
        $this->actingAs($this->officer)->get(route('staff.agencies'))->assertOk()->assertSee('Collection agencies');
    }
}
