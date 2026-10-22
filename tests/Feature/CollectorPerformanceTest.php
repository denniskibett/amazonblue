<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CollectorPerformance;
use Database\Seeders\LookupTablesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Collected per officer per month on the Collections page (owner, 23 Oct 2026). */
class CollectorPerformanceTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-23 10:00');
        $this->seed(LookupTablesSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function caseFor(int $loanId, ?int $officer, string $status = 'open'): int
    {
        return DB::table('debt_recovery_cases')->insertGetId([
            'user_id' => DB::table('loans')->where('id', $loanId)->value('user_id'), 'loan_id' => $loanId,
            'case_number' => 'DR-TEST-' . $loanId, 'total_debt_amount' => 12000, 'default_date' => '2026-09-01',
            'status_id' => DB::table('recovery_statuses')->where('slug', $status)->value('id'),
            'priority_id' => DB::table('recovery_priorities')->value('id'), 'assigned_to' => $officer,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function pay(int $loanId, float $amount, string $date, string $ref): void
    {
        DB::table('repayments')->insert(['loan_id' => $loanId, 'loan_cycle_id' => $loanId, 'amount' => $amount, 'repayment_date' => $date,
            'transaction' => $ref, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function promise(int $caseId, int $officer, float $amount, string $on, string $by): void
    {
        DB::table('recovery_actions')->insert(['case_id' => $caseId, 'action_type_id' => DB::table('action_types')->value('id'),
            'action_date' => $on, 'performed_by' => $officer, 'outcome' => 'promise_to_pay', 'promised_amount' => $amount,
            'promised_date' => $by, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_cash_paid_on_an_officers_cases_counts_and_promises_are_scored(): void
    {
        $amina = User::factory()->create(['name' => 'Amina Officer']);
        foreach ([801, 802] as $id) {
            $this->loanRow($id, 1, 10000, 10000, '2026-08-01', '2026-08-11', 'defaulted', 1, 30);
            $this->cycleRow($id, $id, 1, 0, 2000, 12000, 20, '2026-08-01', '2026-08-11', 'active', 'Initial');
        }
        $c1 = $this->caseFor(801, $amina->id);
        $c2 = $this->caseFor(802, null);

        $this->promise($c1, $amina->id, 3000, '2026-10-05', '2026-10-10');   // kept: 3,000 cash by the 10th
        $this->promise($c1, $amina->id, 5000, '2026-10-12', '2026-10-20');   // missed: only 1,000 more by the 20th
        $this->pay(801, 3000, '2026-10-08', 'QK801A');
        $this->pay(801, 1000, '2026-10-15', 'QK801B');
        $this->pay(801, 2000, '2026-10-16', 'CREDIT DISCOUNT');              // book entry: not money
        $this->pay(801, 9999, '2026-09-30', 'QK801OLD');                     // last month
        $this->pay(802, 700, '2026-10-02', 'QK802');                         // case with no officer

        $rows = CollectorPerformance::forMonth(Carbon::parse('2026-10-01'))->keyBy('name');

        $a = $rows['Amina Officer'];
        $this->assertSame(1, $a->open_cases);
        $this->assertEqualsWithDelta(4000, $a->collected, 0.001);
        $this->assertSame(2, $a->payments);
        $this->assertSame(2, $a->promises);
        $this->assertSame(2, $a->due_promises);
        $this->assertSame(1, $a->kept);
        $this->assertSame(50, (int) $a->kept_rate);

        $this->assertEqualsWithDelta(700, $rows['Not assigned']->collected, 0.001);
        $this->assertSame(1, $rows['Not assigned']->open_cases);
    }

    public function test_the_collections_page_shows_the_officer_table(): void
    {
        $this->seed(RoleSeeder::class);
        $officer = User::factory()->create();
        $officer->assignRole('recovery-officer');

        $this->actingAs($officer)->get(route('staff.collections', ['month' => '2026-09']))
            ->assertOk()->assertSee('Collected per officer · September 2026');
    }
}
