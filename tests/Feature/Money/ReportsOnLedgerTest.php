<?php

namespace Tests\Feature\Money;

use App\Models\Partner;
use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Stage 4 Phase 7: the portfolio, dashboard and reports render on engine v2 and agree with the cycles. */
class ReportsOnLedgerTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    public function test_portfolio_dashboard_and_every_report_render_on_v2(): void
    {
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.ledger.enabled' => true]);
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        $this->loanTypes();
        $this->loan529();
        $this->loan461();
        DB::table('disbursements')->insert(['loan_id' => 529, 'loan_cycle_id' => 606, 'amount' => 12000, 'net_amount' => 12000, 'transaction' => 'QD529',
            'kind' => 'cash', 'mode' => 'mpesa', 'disburse_date' => '2026-09-26', 'payment_date' => '2026-09-26']);
        Carbon::setTestNow('2026-10-17 10:00');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $p = app(\App\Services\LedgerReports::class)->portfolio();
        $this->assertEqualsWithDelta(16258 + 44352, $p['owed'], 0.01);
        $this->assertEqualsWithDelta(12000, $p['cash_lent'], 0.01);

        $this->actingAs($admin)->get(route('staff.portfolio'))->assertOk()->assertSee('16,258.00');
        $this->actingAs($admin)->get(route('staff.portfolio.csv'))->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Owed to us today');
        foreach (['loan_portfolio', 'active_loans', 'completed_loans', 'defaulted_loans', 'customer_balance_summary', 'customer_reports', 'cashflow'] as $type) {
            $this->actingAs($admin)->get('/reports/' . $type)->assertOk();
        }
        Carbon::setTestNow();
    }
}
