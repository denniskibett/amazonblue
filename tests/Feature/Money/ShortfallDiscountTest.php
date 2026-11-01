<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\ShortfallDiscount;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** A short payment accepted as paid in full, the gap booked as a discount (owner, 1 Nov 2026). */
class ShortfallDiscountTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.shortfall.max' => 2000]);
        Carbon::setTestNow('2026-10-20 12:00');
        $this->loanTypes();
        // 5,000 + 20% = 6,000 due 20 Oct
        $this->loanRow(50, 1, 5000.00, 5000.00, '2026-10-10', '2026-10-20', 'disbursed', 1, 0);
        $this->cycleRow(50, 50, 1, 0, 1000.00, 6000.00, 20, '2026-10-10', '2026-10-20', 'active', 'Initial');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_400_shortfall_is_booked_as_a_discount_and_closes_the_loan(): void
    {
        $this->repay(50, 50, 5600.00, '2026-10-20', 'QJK0000001');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('loans.shortfall.accept', 50), ['reason' => 'M-Pesa charges'])
            ->assertRedirect()->assertSessionHas('success');

        $row = DB::table('repayments')->where('loan_id', 50)->where('kind', 'discount')->first();
        $this->assertNotNull($row);
        $this->assertMoney(400, $row->amount);
        $this->assertSame('book', $row->mode);
        $this->assertMoney(0, CycleBalance::at(LoanCycle::findOrFail(50))['final_outstanding']);
        $this->assertSame('repaid', Loan::findOrFail(50)->status);
    }

    public function test_a_gap_above_the_limit_or_with_no_cash_is_refused(): void
    {
        $this->assertFalse(ShortfallDiscount::check(Loan::findOrFail(50))['ok'], 'nothing paid in cash');

        $this->repay(50, 50, 3000.00, '2026-10-20', 'QJK0000002');
        $this->assertFalse(ShortfallDiscount::check(Loan::findOrFail(50))['ok'], '3,000 short is above the limit');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('loans.shortfall.accept', 50), ['reason' => 'try'])->assertSessionHasErrors('reason');
        $this->assertSame(0, DB::table('repayments')->where('loan_id', 50)->where('kind', 'discount')->count());
    }
}
