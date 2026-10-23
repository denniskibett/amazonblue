<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\Repayment;
use App\Models\User;
use App\Services\Ledger;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\TopUp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Top-ups as a new cycle on the same loan (owner, 23 Oct 2026). */
class TopUpTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private Ledger $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.ledger.enabled' => true, 'lending.penalty.cap' => 'in_duplum',
            'lending.first_time_limit' => 5000, 'lending.good_standing_limit' => 50000, 'lending.min_amount' => 1000, 'lending.risk_limit_caps' => []]);
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        $this->ledger = app(Ledger::class);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-01 09:00');
        // a previous loan repaid on time, so the borrower is on the 50,000 good-standing limit
        $this->loanRow(950, 1, 5000, 5000, '2026-09-01', '2026-09-11', 'repaid', 1, 30);
        $userId = DB::table('loans')->where('id', 950)->value('user_id');
        $c0 = LoanCycle::create(['loan_id' => 950, 'cycle_number' => 1, 'previous_balance' => 0, 'principal' => 5000, 'interest_capitalized' => 1000,
            'new_balance' => 6000, 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_days' => 0, 'default_threshold_days' => 30,
            'start_date' => '2026-09-01', 'due_date' => '2026-09-11', 'status' => 'completed']);
        DB::transaction(fn () => Disbursement::create(['loan_id' => 950, 'loan_cycle_id' => $c0->id, 'amount' => 5000, 'net_amount' => 5000, 'transaction' => 'QD950', 'kind' => 'cash', 'mode' => 'mpesa', 'disburse_date' => '2026-09-01', 'payment_date' => '2026-09-01']));
        DB::transaction(fn () => Repayment::create(['loan_id' => 950, 'loan_cycle_id' => $c0->id, 'amount' => 6000, 'repayment_date' => '2026-09-10', 'transaction' => 'QK950']));

        // the loan to top up: 10,000 at 20%, due 10-11
        $this->loanRow(951, 1, 10000, 10000, '2026-10-01', '2026-10-11', 'disbursed', 1, 30);
        DB::table('loans')->where('id', 951)->update(['user_id' => $userId]);
        $c = LoanCycle::create(['loan_id' => 951, 'cycle_number' => 1, 'previous_balance' => 0, 'principal' => 10000, 'interest_capitalized' => 2000,
            'new_balance' => 12000, 'interest_rate' => 20, 'penalty_rate' => 10, 'grace_days' => 0, 'default_threshold_days' => 30,
            'start_date' => '2026-10-01', 'due_date' => '2026-10-11', 'status' => 'active']);
        DB::transaction(fn () => Disbursement::create(['loan_id' => 951, 'loan_cycle_id' => $c->id, 'amount' => 10000, 'net_amount' => 10000, 'transaction' => 'QD951', 'kind' => 'cash', 'mode' => 'mpesa', 'disburse_date' => '2026-10-01', 'payment_date' => '2026-10-01']));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function receivable(): float
    {
        return $this->ledger->balance($this->ledger->account(Loan::findOrFail(951), 'gl-receivable'));
    }

    public function test_a_loan_less_than_half_paid_or_late_cannot_be_topped_up(): void
    {
        Carbon::setTestNow('2026-10-05 10:00');
        $c = app(TopUp::class)->check(Loan::findOrFail(951));
        $this->assertFalse($c['ok']);
        $this->assertStringContainsString('at least half', implode(' ', $c['why']));

        Carbon::setTestNow('2026-10-13 10:00');
        $this->assertStringContainsString('past its due date', implode(' ', app(TopUp::class)->check(Loan::findOrFail(951))['why']));
    }

    public function test_a_top_up_books_a_new_cycle_and_the_ledger_counts_the_new_money_once(): void
    {
        Carbon::setTestNow('2026-10-06 10:00');
        DB::transaction(fn () => Repayment::create(['loan_id' => 951, 'loan_cycle_id' => LoanCycle::where('loan_id', 951)->value('id'), 'amount' => 7000, 'repayment_date' => '2026-10-06', 'transaction' => 'QK951A']));
        $check = app(TopUp::class)->check(Loan::findOrFail(951));
        $this->assertTrue($check['ok'], implode(' ', $check['why']));
        $this->assertEqualsWithDelta(5000, $check['owed'], 0.001);                // 12,000 - 7,000
        $this->assertSame(45000, $check['max']);                                    // 50,000 limit - 5,000 owed

        $admin = User::factory()->create();
        $r = app(TopUp::class)->execute(Loan::findOrFail(951), 20000, ['transaction' => 'QDTOPUP951', 'mode' => 'mpesa'], $admin);

        $new = LoanCycle::where('loan_id', 951)->where('cycle_number', 2)->firstOrFail();
        $this->assertEqualsWithDelta(25000, $new->lentPrincipal(), 0.001);         // 5,000 carried + 20,000 new
        $this->assertEqualsWithDelta(5000, (float) $new->interest_capitalized, 0.001);
        $this->assertSame('2026-10-16', $new->due_date->toDateString());          // 10 days from today
        $this->assertSame('completed', LoanCycle::where('loan_id', 951)->where('cycle_number', 1)->value('status'));
        $this->assertSame($new->id, (int) $r['disbursement']->loan_cycle_id);
        $this->assertSame('cash', $r['disbursement']->kind);

        // the ledger: what is owed (25,000 + 5,000) and nothing booked as a rollover adjustment
        $owed = CycleBalance::at($new)['final_outstanding'];
        $this->assertEqualsWithDelta(30000, $owed, 0.001);
        $this->assertEqualsWithDelta($owed, $this->receivable(), 0.001);
        $this->assertEqualsWithDelta(0, $this->ledger->balance($this->ledger->houseAccount('gl-rollover-adjustments')), 0.001);
        $this->assertSame([], $this->ledger->checkReceivables()['issues']);
        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
        $this->assertSame(Loan::STATUS_DISBURSED, Loan::find(951)->status);
    }

    public function test_more_than_the_limit_allows_is_refused(): void
    {
        Carbon::setTestNow('2026-10-06 10:00');
        DB::transaction(fn () => Repayment::create(['loan_id' => 951, 'loan_cycle_id' => LoanCycle::where('loan_id', 951)->value('id'), 'amount' => 7000, 'repayment_date' => '2026-10-06', 'transaction' => 'QK951B']));
        $this->expectException(\DomainException::class);
        app(TopUp::class)->execute(Loan::findOrFail(951), 46000, ['transaction' => 'QDX'], User::factory()->create());
    }
    public function test_the_borrower_asks_and_staff_send_it_from_requests(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        config(['lending.requests.enabled' => true]);
        Carbon::setTestNow('2026-10-06 10:00');
        DB::transaction(fn () => Repayment::create(['loan_id' => 951, 'loan_cycle_id' => LoanCycle::where('loan_id', 951)->value('id'), 'amount' => 7000, 'repayment_date' => '2026-10-06', 'transaction' => 'QK951C']));
        $borrower = User::findOrFail(DB::table('loans')->where('id', 951)->value('user_id'));
        $borrower->forceFill(['email_verified_at' => now()])->save();
        $borrower->assignRole('borrower');

        $this->actingAs($borrower)->get(route('account.topup', ['loan' => 951, 'amount' => 10000]))->assertOk()->assertSee('You repay')->assertSee('18,000.00');
        $this->actingAs($borrower)->post(route('account.topup.request', 951), ['amount' => 10000, 'confirm' => '1'])->assertRedirect();
        $req = DB::table('borrower_requests')->where('type', 'topup')->first();
        $this->assertSame('open', $req->status);
        $this->assertSame(1, LoanCycle::where('loan_id', 951)->count(), 'asking changes nothing');

        $staff = User::factory()->create(['email_verified_at' => now()]);
        $staff->assignRole('admin');
        $this->actingAs($staff)->get(route('staff.requests'))->assertOk()->assertSee('Send top-up');
        $this->actingAs($staff)->post(route('staff.requests.topup', $req->id), ['transaction' => 'QDSENT951', 'mode' => 'mpesa'])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('approved', DB::table('borrower_requests')->where('id', $req->id)->value('status'));
        $this->assertEqualsWithDelta(15000, LoanCycle::where('loan_id', 951)->where('cycle_number', 2)->first()->lentPrincipal(), 0.001);
        $this->assertSame(1, Disbursement::where('loan_id', 951)->where('transaction', 'QDSENT951')->count());
    }
}
