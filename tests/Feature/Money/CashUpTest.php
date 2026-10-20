<?php

namespace Tests\Feature\Money;

use App\Models\Repayment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Stage 4 Phase 5: a teller closes their day; a manager approves. */
class CashUpTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    public function test_close_records_the_variance_once_and_another_person_approves(): void
    {
        $this->seed(RoleSeeder::class);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 17:00');
        $this->loanRow(1201, 1, 5000, 5000, '2026-10-15', '2026-10-25', 'disbursed', 1, 30);
        $this->cycleRow(1201, 1201, 1, 0, 1000, 6000, 20, '2026-10-15', '2026-10-25', 'active', 'Initial');
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller);
        Repayment::create(['loan_id' => 1201, 'loan_cycle_id' => 1201, 'amount' => 1500, 'repayment_date' => '2026-10-21', 'transaction' => 'CASH-1', 'mode' => 'cash']);
        Repayment::create(['loan_id' => 1201, 'loan_cycle_id' => 1201, 'amount' => 2000, 'repayment_date' => '2026-10-21', 'transaction' => 'UJLCASHUP1', 'mode' => 'mpesa']);

        $this->post(route('staff.end-of-day.close'), ['date' => '2026-10-21', 'counted' => ['cash' => 1400, 'mpesa' => 2000]])->assertSessionHas('success');
        $close = DB::table('teller_day_closes')->first();
        $this->assertEquals(-100, (float) $close->variance);
        $this->assertEquals(['cash' => 1500, 'mpesa' => 2000], json_decode($close->expected, true));
        $this->post(route('staff.end-of-day.close'), ['date' => '2026-10-21', 'counted' => ['cash' => 1500]])->assertSessionHas('error');

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager)->post(route('staff.end-of-day.approve', $close->id))->assertSessionHas('success');
        $this->assertSame('approved', DB::table('teller_day_closes')->value('status'));
        Carbon::setTestNow();
    }
}
