<?php

namespace Tests\Feature\Money;

use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\LoanRepair;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoanRepairTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.penalty.cap' => 'in_duplum']);
        Carbon::setTestNow('2026-10-21 18:00');
        $this->loanTypes();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function disburse(int $loanId, float $amount, string $date, string $transaction = 'CASH'): void
    {
        DB::table('disbursements')->insert(['loan_id' => $loanId, 'amount' => $amount, 'transaction' => $transaction, 'mode' => 'mpesa', 'kind' => 'cash',
            'disburse_date' => $date, 'payment_date' => $date, 'created_at' => $date, 'updated_at' => $date]);
    }

    public function test_a_missing_disbursement_is_recorded_without_changing_what_is_owed(): void
    {
        $this->loanRow(59, 1, 78000, 78000, '2025-05-12', '2025-05-22', 'repaid', 1, 0);
        $this->cycleRow(59, 59, 1, 0, 15600, 93600, 20, '2025-05-12', '2025-05-22', 'completed', 'Initial');
        $this->repay(59, 59, 93600, '2025-05-22', 'CASHBACK', 59, 'mpesa');

        $msg = app(LoanRepair::class)->addMissingDisbursement(59);
        $this->assertStringContainsString('78,000.00', $msg);
        $this->assertSame(78000.0, (float) DB::table('disbursements')->where('loan_id', 59)->value('amount'));
        $this->assertMoney(0, app(LoanRepair::class)->owedFor(59));
        $this->assertSame('repaid', DB::table('loans')->where('id', 59)->value('status'));
    }

    public function test_a_top_up_goes_into_the_cycle_running_that_day_and_later_cycles_carry_it(): void
    {
        // like #139: cycle 1 lent 30,000; 10,000 more was sent mid-cycle; cycle 2 carried only the 36,000 of cycle 1
        $this->loanRow(139, 1, 30000, 30000, '2026-01-01', '2026-01-21', 'disbursed', 2, 0);
        $this->cycleRow(1391, 139, 1, 0, 6000, 36000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->cycleRow(1392, 139, 2, 36000, 7200, 43200, 20, '2026-01-11', '2026-01-21', 'active', 'Rollover');
        $this->disburse(139, 30000, '2026-01-01');
        $this->disburse(139, 10000, '2026-01-05');

        app(LoanRepair::class)->rebuildFromCash(139);

        $c1 = LoanCycle::findOrFail(1391);
        $c2 = LoanCycle::findOrFail(1392);
        $this->assertMoney(40000, $c1->lentPrincipal());
        $this->assertMoney(8000, $c1->interest_capitalized);
        $this->assertMoney(48000, $c2->lentPrincipal(), 'cycle 2 carries the 12,000 more cycle 1 now owes');
        $this->assertMoney(9600, $c2->interest_capitalized);
    }

    public function test_a_loan_in_forbearance_or_on_a_plan_is_never_rebuilt(): void
    {
        $this->loanRow(458, 1, 150000, 150000, '2026-06-30', '2026-07-10', 'forbearance', 1, 0);
        $this->cycleRow(4581, 458, 1, 0, 40000, 240000, 20, '2026-06-30', '2026-07-10', 'active', 'PAYMENT PLAN agreed by the owner');
        $this->disburse(458, 150000, '2026-06-30');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        try {
            app(LoanRepair::class)->rebuildFromCash(458);
        } finally {
            $this->assertMoney(200000, LoanCycle::findOrFail(4581)->lentPrincipal());
        }
    }

    public function test_a_write_off_bigger_than_the_debt_is_cut_to_what_was_owed(): void
    {
        $this->loanRow(220, 1, 10000, 10000, '2025-10-24', '2025-11-03', 'repaid', 1, 0);
        $this->cycleRow(220, 220, 1, 0, 2000, 12000, 20, '2025-10-24', '2025-11-03', 'completed', 'Initial');
        $this->repay(220, 220, 5000, '2025-11-03', 'CASH', 2201, 'mpesa');
        DB::table('repayments')->insert(['id' => 2202, 'loan_id' => 220, 'loan_cycle_id' => 220, 'amount' => 25000, 'repayment_date' => '2025-11-03',
            'transaction' => 'BAD DEBT', 'kind' => 'bad_debt', 'mode' => 'book', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertMoney(-18000, app(LoanRepair::class)->owedFor(220));

        app(LoanRepair::class)->bookCut(2202);

        $this->assertMoney(7000, DB::table('repayments')->where('id', 2202)->value('amount'));
        $this->assertMoney(0, app(LoanRepair::class)->owedFor(220));
    }

    public function test_a_broker_fee_the_broker_kept_is_ac_cost_named_after_the_broker(): void
    {
        DB::table('partners')->insert(['id' => 5, 'name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $this->loanRow(414, 1, 30000, 30000, '2026-05-13', '2026-05-23', 'repaid', 1, 0);
        $this->cycleRow(414, 414, 1, 0, 6000, 36000, 20, '2026-05-13', '2026-05-23', 'completed', 'Initial');
        $this->repay(414, 414, 33600, '2026-05-23', 'CASH', 4141, 'mpesa');
        $this->repay(414, 414, 2400, '2026-05-23', 'BROKER FEE', 4142, 'other');

        app(LoanRepair::class)->brokerKept(4142);

        $row = DB::table('repayments')->where('id', 4142)->first();
        $this->assertSame('discount', $row->kind);
        $this->assertStringStartsWith('BROKER FEE kept by ISIRO AGENCIES', $row->transaction, 'older than 3 months: pooled to ISIRO');
        $this->assertMoney(0, app(LoanRepair::class)->owedFor(414));
    }

    public function test_a_broker_fee_row_can_become_an_ac_discount_or_be_owed_again(): void
    {
        $this->loanRow(413, 1, 25000, 25000, '2026-05-11', '2026-05-21', 'repaid', 1, 0);
        $this->cycleRow(413, 413, 1, 0, 5000, 30000, 20, '2026-05-11', '2026-05-21', 'completed', 'Initial');
        $this->repay(413, 413, 28000, '2026-05-21', 'CASH', 4131, 'mpesa');
        $this->repay(413, 413, 2000, '2026-05-21', 'BROKER FEE', 4132, 'other');

        app(LoanRepair::class)->bookAs(4132, 'discount');
        $this->assertSame('discount', DB::table('repayments')->where('id', 4132)->value('kind'));
        $this->assertMoney(0, app(LoanRepair::class)->owedFor(413));

        // owed again: 2,000 short since the due date, so the penalty runs again (capped at the 2,000 unpaid)
        app(LoanRepair::class)->bookRemove(4132);
        $this->assertMoney(4000, app(LoanRepair::class)->owedFor(413));
        $this->assertMoney(2000, CycleBalance::at(LoanCycle::findOrFail(413))['penalty']);
    }

    public function test_a_book_row_can_be_set_to_a_typed_amount_but_cash_never(): void
    {
        $this->loanRow(347, 1, 10000, 10000, '2025-10-24', '2025-11-03', 'repaid', 1, 0);
        $this->cycleRow(347, 347, 1, 0, 2000, 12000, 20, '2025-10-24', '2025-11-03', 'completed', 'Initial');
        $this->repay(347, 347, 2000, '2025-11-03', 'CASH', 3471, 'mpesa');
        DB::table('repayments')->insert(['id' => 3472, 'loan_id' => 347, 'loan_cycle_id' => 347, 'amount' => 578000, 'repayment_date' => '2025-11-03',
            'transaction' => 'BAD DEBT', 'kind' => 'bad_debt', 'mode' => 'book', 'created_at' => now(), 'updated_at' => now()]);

        app(LoanRepair::class)->bookSet(3472, 10000);
        $this->assertMoney(10000, DB::table('repayments')->where('id', 3472)->value('amount'));
        $this->assertMoney(0, app(LoanRepair::class)->owedFor(347));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(LoanRepair::class)->bookSet(3471, 1);
    }

    public function test_setting_one_cycle_by_hand_does_not_re_carry_later_cycles(): void
    {
        $this->loanRow(513, 1, 40000, 40000, '2026-01-01', '2026-01-21', 'disbursed', 2, 0);
        $this->cycleRow(5131, 513, 1, 0, 8000, 48000, 20, '2026-01-01', '2026-01-11', 'completed', 'Initial');
        $this->cycleRow(5132, 513, 2, 48000, 9600, 57600, 20, '2026-01-11', '2026-01-21', 'active', 'Rollover');

        $msg = app(LoanRepair::class)->cycleSet(5131, 50000);

        $this->assertStringContainsString('50,000.00', $msg);
        $this->assertMoney(50000, LoanCycle::findOrFail(5131)->lentPrincipal());
        $this->assertMoney(10000, LoanCycle::findOrFail(5131)->interest_capitalized);
        $this->assertMoney(48000, LoanCycle::findOrFail(5132)->lentPrincipal(), 'cycle 2 keeps what it was booked with');
    }

    public function test_the_detail_modal_shows_the_plan_loan_cycles_and_fields_and_a_json_preview_saves_nothing(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('admin');
        $this->loanRow(347, 1, 10000, 10000, '2025-10-24', '2025-11-03', 'repaid', 1, 0);
        $this->cycleRow(347, 347, 1, 0, 2000, 12000, 20, '2025-10-24', '2025-11-03', 'completed', 'Initial');
        $this->repay(347, 347, 2000, '2025-11-03', 'CASH', 3471, 'mpesa');
        DB::table('repayments')->insert(['id' => 3472, 'loan_id' => 347, 'loan_cycle_id' => 347, 'amount' => 578000, 'repayment_date' => '2025-11-03',
            'transaction' => 'BAD DEBT', 'kind' => 'bad_debt', 'mode' => 'book', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin)->getJson(route('staff.operations-review.detail', 347))->assertOk()
            ->assertJsonPath('plan.group', 'A')
            ->assertJsonPath('loan.id', 347)
            ->assertJsonPath('cycles.0.n', 1)
            ->assertJsonPath('edits.0.type', 'book')
            ->assertJsonPath('edits.0.suggested', fn ($v) => abs($v - 10000) < 0.01);

        $this->actingAs($admin)->postJson(route('staff.operations-review.repair', 347), ['action' => 'book_set', 'row' => 3472, 'amount' => 10000, 'preview' => 1])
            ->assertOk()->assertJsonPath('preview', true)->assertJsonPath('after', fn ($v) => abs($v) < 0.01);
        $this->assertMoney(578000, DB::table('repayments')->where('id', 3472)->value('amount'), 'a preview saves nothing');
    }

    public function test_a_disbursement_on_the_wrong_loan_moves_to_the_borrowers_other_loan(): void
    {
        // like Leon: cash on a repaid loan (#315) that his other loan (#220) is missing
        $this->loanRow(315, 1, 10000, 10000, '2025-03-01', '2025-03-11', 'repaid', 1, 0);
        $this->cycleRow(315, 315, 1, 0, 2000, 12000, 20, '2025-03-01', '2025-03-11', 'completed', 'Initial');
        $this->repay(315, 315, 12000, '2025-03-11', 'CASH', 3151, 'mpesa');
        $this->loanRow(220, 1, 10000, 10000, '2025-10-24', '2025-11-03', 'repaid', 1, 0);
        $this->cycleRow(220, 220, 1, 0, 2000, 12000, 20, '2025-10-24', '2025-11-03', 'completed', 'Initial');
        $this->repay(220, 220, 12000, '2025-11-03', 'CASH', 2201, 'mpesa');
        DB::table('loans')->where('id', 220)->update(['user_id' => DB::table('loans')->where('id', 315)->value('user_id')]);
        $this->disburse(315, 10000, '2025-03-01');
        $this->disburse(315, 10000, '2025-10-24', 'SENT FOR 220');
        $id = (int) DB::table('disbursements')->where('transaction', 'SENT FOR 220')->value('id');

        $msg = app(LoanRepair::class)->moveDisbursement($id, 220);

        $this->assertStringContainsString('moved here', $msg);
        $this->assertSame(220, (int) DB::table('disbursements')->where('id', $id)->value('loan_id'));
        $this->assertSame(220, (int) DB::table('disbursements')->where('id', $id)->value('loan_cycle_id'));
        $this->assertSame(1, DB::table('disbursements')->where('loan_id', 315)->count());
    }

    public function test_a_loan_from_the_last_three_months_keeps_its_rollovers(): void
    {
        $this->loanRow(536, 1, 4000, 4000, '2026-09-01', '2026-09-11', 'repaid', 1, 0);
        $this->cycleRow(536, 536, 1, 0, 800, 4800, 20, '2026-09-01', '2026-09-11', 'completed', 'Initial');
        $this->loanRow(505, 1, 4000, 4000, '2026-08-01', '2026-08-11', 'repaid', 1, 0);
        $this->cycleRow(505, 505, 1, 0, 800, 4800, 20, '2026-08-01', '2026-08-11', 'completed', 'Initial');
        DB::table('loans')->whereIn('id', [536, 505])->update(['user_id' => DB::table('loans')->where('id', 536)->value('user_id')]);
        DB::table('repayments')->insert(['id' => 5361, 'loan_id' => 536, 'loan_cycle_id' => 536, 'amount' => 4800, 'repayment_date' => '2026-09-11',
            'transaction' => 'ROLL OVER', 'kind' => 'rollover', 'created_at' => now(), 'updated_at' => now()]);

        $svc = app(LoanRepair::class);
        $this->assertTrue($svc->recent(\App\Models\Loan::findOrFail(536)));
        try {
            $svc->bookAs(5361, 'bad_debt');
            $this->fail('a rollover row on a recent loan must not change');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertStringContainsString('3 months', $e->getMessage());
        }
        $this->assertSame('rollover', DB::table('repayments')->where('id', 5361)->value('kind'));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->moveReceivable(505, 536, 4000);
    }
}
