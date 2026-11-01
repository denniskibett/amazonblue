<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\ShortfallFit;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** A short payment accepted as the cycle's full amount: the cycle is fitted to the cash, no repayment row (owner, 1 Nov 2026). */
class ShortfallFitTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.shortfall.max' => 2000]);
        Carbon::setTestNow('2026-10-25 12:00');
        $this->loanTypes();
        // 21,000 + 20% = 25,200 due 20 Oct
        $this->loanRow(50, 1, 21000.00, 21000.00, '2026-10-10', '2026-10-20', 'overdue', 1, 0);
        $this->cycleRow(50, 50, 1, 0, 4200.00, 25200.00, 20, '2026-10-10', '2026-10-20', 'active', 'Initial');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    public function test_25000_of_25200_closes_the_cycle_with_4000_interest_and_no_new_repayment(): void
    {
        $this->repay(50, 50, 25000.00, '2026-10-20', 'QJK0000001');

        $this->actingAs($this->admin())->post(route('loans.shortfall.accept', 50), ['reason' => 'agreed'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, DB::table('repayments')->where('loan_id', 50)->count(), 'only the real cash');
        $c = LoanCycle::findOrFail(50);
        $this->assertMoney(4000, $c->interest_amount);
        $this->assertMoney(4000, $c->interest_capitalized);
        $this->assertMoney(25000, $c->new_balance);
        $b = CycleBalance::at($c);
        $this->assertMoney(4000, $b['interest']);
        $this->assertMoney(0, $b['final_outstanding']);
        $this->assertMoney(0, $b['penalty']);
        $this->assertSame('repaid', Loan::findOrFail(50)->status);
    }

    public function test_late_cash_gets_grace_so_no_penalty_is_left(): void
    {
        $this->repay(50, 50, 25000.00, '2026-10-22', 'QJK0000002');   // two days late

        ShortfallFit::apply(Loan::findOrFail(50), 'agreed', $this->admin());

        $c = LoanCycle::findOrFail(50);
        $this->assertSame(2, (int) $c->grace_days);
        $this->assertMoney(0, CycleBalance::at($c)['final_outstanding']);
        $this->assertSame('repaid', Loan::findOrFail(50)->status);
    }

    public function test_a_shortfall_discount_row_from_the_first_version_is_removed(): void
    {
        $this->repay(50, 50, 25000.00, '2026-10-20', 'QJK0000003');
        DB::table('repayments')->insert(['loan_id' => 50, 'loan_cycle_id' => 50, 'amount' => 200.00, 'repayment_date' => '2026-10-24',
            'transaction' => 'SHORTFALL DISCOUNT: cycle 1', 'kind' => 'discount', 'mode' => 'book', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('loans')->where('id', 50)->update(['status' => 'repaid']);

        $this->assertTrue(ShortfallFit::check(Loan::findOrFail(50))['ok']);
        ShortfallFit::apply(Loan::findOrFail(50), 'redo without a book entry', $this->admin());

        $this->assertSame(0, DB::table('repayments')->where('loan_id', 50)->where('kind', 'discount')->whereNull('deleted_at')->count());
        $this->assertMoney(0, CycleBalance::at(LoanCycle::findOrFail(50))['final_outstanding']);
        $this->assertSame('repaid', Loan::findOrFail(50)->status);
    }

    public function test_no_cash_or_a_gap_above_the_limit_is_refused(): void
    {
        $this->assertFalse(ShortfallFit::check(Loan::findOrFail(50))['ok'], 'nothing paid in cash');

        $this->repay(50, 50, 22000.00, '2026-10-20', 'QJK0000004');
        $this->assertFalse(ShortfallFit::check(Loan::findOrFail(50))['ok'], '3,200 short is above the limit');

        $this->actingAs($this->admin())->post(route('loans.shortfall.accept', 50), ['reason' => 'try'])->assertSessionHasErrors('reason');
        $this->assertNull(LoanCycle::findOrFail(50)->interest_amount);
    }
}
