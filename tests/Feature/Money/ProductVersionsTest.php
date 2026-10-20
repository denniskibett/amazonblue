<?php

namespace Tests\Feature\Money;

use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\CycleBalance;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Stage 4 Phase 5: editing a product keeps its old values and leaves running loans alone. */
class ProductVersionsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    public function test_an_edit_is_versioned_and_a_running_cycle_keeps_its_terms(): void
    {
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2']);
        $this->loanTypes();
        $this->loan529();
        DB::table('loan_cycles')->where('id', 606)->update(['penalty_rate' => 10]);   // snapshot, as Phase 2 records it
        Carbon::setTestNow('2026-10-17 10:00');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('staff.products.update', 1), ['name' => 'Emergency 10 Days', 'interest_rate' => 25, 'penalty_rate' => 5,
            'period' => 10, 'unit' => 'days', 'grace_period_days' => 0, 'default_threshold_days' => 30, 'reason' => 'Owner pricing review'])->assertSessionHas('success');

        $this->assertSame(1, DB::table('loan_type_versions')->count());
        $this->assertEquals(10, json_decode(DB::table('loan_type_versions')->value('values'), true)['penalty_rate']);
        $this->assertEquals(5, (float) DB::table('loan_types')->where('id', 1)->value('penalty_rate'));
        $this->assertEqualsWithDelta(1478, CycleBalance::at(LoanCycle::findOrFail(606))['penalty'], 0.001, 'the running cycle keeps 10%');
        Carbon::setTestNow();
    }
}
