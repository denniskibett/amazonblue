<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\ProductChange;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** A sent loan moved to another product on Edit loan: only the open cycle changes (owner, 1 Nov 2026). */
class ProductChangeTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2']);
        Carbon::setTestNow('2026-10-14 12:00');
        $this->loanTypes();
        DB::table('loan_types')->insert(['id' => 14, 'name' => 'Emergency 5 Days', 'period' => 5, 'unit' => 'days',
            'interest_rate' => 10, 'penalty_rate' => 7.5, 'grace_period_days' => 0, 'default_threshold_days' => 30]);
        $this->loan529(); // cycle 2: 14,400 at 20%, 6 Oct → 16 Oct
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_preview_shows_the_open_cycle_before_and_after(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $p = $this->actingAs($admin)->getJson(route('loans.product-change.preview', [529, 'loan_type_id' => 14]))
            ->assertOk()->json('data');

        $this->assertSame(2, $p['cycle_number']);
        $this->assertSame('2026-10-16', $p['before']['due_date']);
        $this->assertSame('2026-10-11', $p['after']['due_date']);
        $this->assertMoney(2880, $p['before']['interest']);
        $this->assertMoney(1440, $p['after']['interest']);
        $this->assertGreaterThan(0, $p['after']['penalty'], 'now three days late on the 5-day product');
        $this->assertMoney(0, $p['before']['penalty']);
        $this->assertSame('2026-10-16', LoanCycle::findOrFail(606)->due_date->toDateString(), 'a preview saves nothing');
    }

    public function test_apply_reprices_only_the_open_cycle(): void
    {
        DB::table('loans')->where('id', 529)->update(['loan_type_id' => 14]);
        ProductChange::apply(Loan::findOrFail(529));

        $c2 = LoanCycle::findOrFail(606);
        $this->assertSame('2026-10-11', $c2->due_date->toDateString());
        $this->assertSame('2026-10-06', Carbon::parse($c2->start_date)->toDateString());
        $this->assertMoney(10, $c2->interest_rate);
        $this->assertMoney(1440, $c2->interest_capitalized);
        $this->assertMoney(15840, $c2->new_balance);
        $this->assertSame('2026-10-11', Carbon::parse(Loan::findOrFail(529)->due_date)->toDateString());

        $c1 = LoanCycle::findOrFail(593);
        $this->assertSame('2026-10-06', $c1->due_date->toDateString(), 'earlier cycles stay as booked');
        $this->assertMoney(2400, $c1->interest_capitalized);
    }

    public function test_edit_current_cycle_sets_a_due_date_and_rate(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('loans.cycle.edit', 529), ['due_date' => '2026-10-20', 'interest_rate' => 15, 'reason' => 'agreed with borrower'])
            ->assertRedirect()->assertSessionHas('success');

        $c2 = LoanCycle::findOrFail(606);
        $this->assertSame('2026-10-20', $c2->due_date->toDateString());
        $this->assertMoney(15, $c2->interest_rate);
        $this->assertMoney(2160, $c2->interest_capitalized);
        $this->assertSame(1, (int) Loan::findOrFail(529)->loan_type_id, 'no product chosen: the product stays');

        $this->actingAs($admin)->post(route('loans.cycle.edit', 529), ['due_date' => '2026-10-01', 'reason' => 'typo'])
            ->assertSessionHasErrors('due_date');
    }

    public function test_rollover_into_a_shorter_product(): void
    {
        Carbon::setTestNow('2026-10-16 10:00');
        $r = app(\App\Services\Loans\Rollover::class)->execute(Loan::findOrFail(529), ['loan_type_id' => 14]);

        $this->assertSame('2026-10-16', $r['start_date']);
        $this->assertSame('2026-10-21', $r['due_date'], 'five days, the new product');
        $this->assertMoney(10, $r['interest_rate']);
        $this->assertMoney(7.5, $r['created']->penalty_rate);
        $this->assertSame(14, (int) Loan::findOrFail(529)->loan_type_id);
    }
}
