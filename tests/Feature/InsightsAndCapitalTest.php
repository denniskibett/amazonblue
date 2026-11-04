<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use App\Services\CustomerWallets;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Owner, 4 Nov 2026: partner returns and payouts in investor terms, the six-group sidebar with module tabs, the P&L
 * and people analytics, and capital raising by investment managers who report to the owner.
 */
class InsightsAndCapitalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_a_partner_sees_their_portfolio_and_payouts_in_investor_terms(): void
    {
        $u = $this->user('partner');
        $p = Partner::create(['name' => 'Savanna Capital Ltd', 'email' => 's@example.test', 'type' => 'corporate', 'user_id' => $u->id, 'phone' => '0712345678']);
        app(CustomerWallets::class)->ensurePartnerWallets($p);
        $p->addContribution(100000, 'MPESA-ABC', 'capital', null, null, now()->subMonths(4)->toDateString());
        $p->distributeProfit(6000, 'interest', null, 'SPLIT-R1-X');

        $this->actingAs($u)->get('/account/partner')->assertOk()->assertSee('Net asset value')->assertSee('Dry powder')->assertSee('MOIC')->assertSee('Treasury view');
        $this->actingAs($u)->get('/account/partner/payouts')->assertOk()->assertSee('Distributions')->assertSee('How a distribution is built');
    }

    public function test_the_sidebar_has_six_groups_and_a_module_shows_its_tabs(): void
    {
        $admin = $this->user('super-admin');
        $groups = \App\Support\NavMenu::groups($admin, false);
        $this->assertSame([], array_values(array_diff(array_keys($groups), ['Overview', 'Needs Attention', 'Lending', 'Collections & Finance', 'Partners', 'Management'])));
        $this->assertSame(['Collections & Recovery', 'Payments', 'Financial Operations', 'Financial Performance'],
            array_values(array_intersect(array_column($groups['Collections & Finance'], 0), ['Collections & Recovery', 'Payments', 'Financial Operations', 'Financial Performance'])));

        $this->actingAs($admin)->get(route('staff.cash-forecast'))->assertOk()->assertSee('data-module-tabs', false)->assertSee('P&amp;L and unit economics', false);
    }

    public function test_pnl_and_people_analytics_open_for_reports_and_not_for_others(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin)->get(route('staff.pnl'))->assertOk()->assertSee('Where the money went')->assertSee('Income statement')->assertSee('Collection efficiency');
        $this->actingAs($admin)->get(route('staff.pnl', ['period' => 'ytd']))->assertOk();
        $this->actingAs($admin)->get(route('staff.people'))->assertOk()->assertSee('Who pays')->assertSee('never used');
        $b = $this->user('borrower');
        $this->actingAs($admin)->get(route('staff.people.person', $b))->assertOk()->assertSee('How they use the app');

        $this->actingAs($this->user('investment-manager'))->get(route('staff.pnl'))->assertForbidden();
    }

    public function test_an_investment_manager_keeps_a_pipeline_and_a_won_prospect_becomes_their_investor(): void
    {
        $im = $this->user('investment-manager');
        $this->actingAs($im)->post(route('staff.capital.store'), ['name' => 'Kisii Teachers Sacco', 'kind' => 'sacco', 'ticket' => 10000000, 'stage' => 'meeting', 'chance' => 50])->assertSessionHas('success');
        $id = DB::table('investor_prospects')->value('id');
        $this->assertSame($im->id, (int) DB::table('investor_prospects')->value('manager_id'));
        $this->actingAs($im)->get(route('staff.capital'))->assertOk()->assertSee('Kisii Teachers Sacco')->assertSee('KES 5.0M');

        $p = Partner::create(['name' => 'Kisii Teachers Sacco', 'email' => 'k@example.test', 'type' => 'corporate']);
        $this->actingAs($im)->post(route('staff.capital.update', $id), ['stage' => 'won', 'partner_id' => $p->id])->assertSessionHas('success');
        $this->assertSame($im->id, (int) $p->fresh()->investment_manager_id);

        // another manager can't move it; the owner sees every manager
        $this->actingAs($this->user('investment-manager'))->post(route('staff.capital.update', $id), ['stage' => 'lost'])->assertForbidden();
        $this->actingAs($this->user('super-admin'))->get(route('staff.capital'))->assertOk()->assertSee('reporting to the owner');
    }
}
