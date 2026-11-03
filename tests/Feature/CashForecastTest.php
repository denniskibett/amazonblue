<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CashForecast;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Cash and growth (owner, 3 Nov 2026): the book's money comes back weighted by lateness, in the right week. */
class CashForecastTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.approvals.enabled' => false, 'lending.ledger.enabled' => false, 'lending.split.enabled' => false]);
        $this->loanTypes();
        Carbon::setTestNow('2026-11-03 10:00');   // a Tuesday: week 0 starts Mon 2 Nov
    }

    public function test_a_loan_not_yet_due_comes_back_in_its_due_week_at_the_current_rate(): void
    {
        $this->loanRow(1801, 1, 5000, 5000, '2026-11-01', '2026-11-11', 'disbursed', 1, 30);
        $this->cycleRow(1801, 1801, 1, 0, 1000, 6000, 20, '2026-11-01', '2026-11-11', 'active', 'Initial');
        $this->loanRow(1802, 1, 5000, 5000, '2026-08-01', '2026-08-11', 'defaulted', 1, 30);   // far past due
        $this->cycleRow(1802, 1802, 1, 0, 1000, 6000, 20, '2026-08-01', '2026-08-11', 'active', 'Initial');

        $f = app(CashForecast::class)->weeks();
        $owed = $f['book']['current'];
        $this->assertGreaterThan(0, $owed);
        // due Wed 11 Nov: week 1; the late loan is spread over the first 4 weeks at its bucket's rate
        $late = collect($f['book'])->except('current')->map(fn ($v, $k) => $v * config("lending.forecast.collect.{$k}"))->sum() / 4;
        $this->assertGreaterThan(0, $late);
        $this->assertEqualsWithDelta($owed * 0.9 + $late, $f['weeks'][1]['in_book'], 0.05);
        $this->assertEqualsWithDelta($late, $f['weeks'][0]['in_book'], 0.05);
    }

    public function test_only_staff_who_see_reports_open_the_page(): void
    {
        $teller = User::factory()->create(['email_verified_at' => now()]);
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.cash-forecast'))->assertForbidden();

        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        $this->actingAs($manager)->get(route('staff.cash-forecast'))->assertOk()->assertSee('Cash over the next 12 weeks')->assertSee('Growth by month');
    }
}
