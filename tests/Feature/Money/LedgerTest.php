<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\Repayment;
use App\Models\User;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Stage 4 Phase 1: the double-entry ledger kept in bavix wallets.
 */
class LedgerTest extends TestCase
{
    use RefreshDatabase;

    private Ledger $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.ledger.enabled' => false]); // the tests post explicitly
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        $this->ledger = app(Ledger::class);
    }

    public function test_cash_out_and_back_balances_and_matches_the_borrower_wallet(): void
    {
        $loan = $this->loan();
        $d = $this->disburse($loan, 5000);
        $r1 = $this->repay($loan, 2500, 'QK1');
        $r2 = $this->repay($loan, 3500, 'QK2');
        $this->repay($loan, 1000, 'ROLL OVER'); // book entry: not posted in Phase 1

        foreach ([$d] as $x) {
            $this->ledger->syncDisbursement($x);
        }
        foreach (Repayment::all() as $x) {
            $this->ledger->syncRepayment($x);
        }

        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
        $this->assertEqualsWithDelta(1000, $this->ledger->houseAccount('gl-cash')->balanceFloatNum, 0.0001); // 6000 in - 5000 out
        $this->assertEqualsWithDelta(-1000, $this->ledger->account($loan, 'gl-receivable')->balanceFloatNum, 0.0001);

        app(CustomerWallets::class)->syncBorrower($loan->user);
        $check = $this->ledger->checkBorrowerCash();
        $this->assertSame(1, $check['checked']);
        $this->assertSame([], $check['issues']);
    }

    public function test_posting_twice_posts_once(): void
    {
        $loan = $this->loan();
        $r = $this->repay($loan, 1200, 'QK1');

        $this->assertCount(1, $this->ledger->syncRepayment($r));
        $this->assertCount(0, $this->ledger->syncRepayment($r));
        $this->assertSame(1, Transaction::where('meta->ledger', 'repayment:' . $r->id)->where('type', 'deposit')->count());
    }

    public function test_a_retry_that_races_the_first_post_is_refused_by_the_uuid(): void
    {
        $loan = $this->loan();
        $r = $this->repay($loan, 1200, 'QK1');
        $legs = $this->ledger->planRepayment($r)['legs'];
        $this->ledger->sync('repayment', $r->id, $legs);

        // a second worker that computed its step before the first one committed posts the same transfer again
        $from = $legs[0]['from']; $to = $legs[0]['to'];
        $name = "repayment:{$r->id}|cash|{$from->id}|{$to->id}|0";
        $this->expectException(\Throwable::class);
        $from->forceTransfer($to, 12000000, new \Bavix\Wallet\External\Dto\Extra(
            deposit: new \Bavix\Wallet\External\Dto\Option([], true, \Ramsey\Uuid\Uuid::uuid5('6f1c2b5e-3c0a-4c7e-9a49-2f7b8d1e4a10', $name . '|d')->toString()),
            withdraw: new \Bavix\Wallet\External\Dto\Option([], true, \Ramsey\Uuid\Uuid::uuid5('6f1c2b5e-3c0a-4c7e-9a49-2f7b8d1e4a10', $name . '|w')->toString()),
            uuid: \Ramsey\Uuid\Uuid::uuid5('6f1c2b5e-3c0a-4c7e-9a49-2f7b8d1e4a10', $name . '|t')->toString(),
        ));
    }

    public function test_an_edit_posts_the_difference_and_a_delete_reverses_to_zero(): void
    {
        $loan = $this->loan();
        $r = $this->repay($loan, 1000, 'QK1');
        $this->ledger->syncRepayment($r);

        $r->update(['amount' => 1500]);
        $this->assertSame([['leg' => 'cash', 'from' => $this->ledger->account($loan, 'gl-receivable')->id, 'to' => $this->ledger->houseAccount('gl-cash')->id, 'minor' => 5000000]],
            $this->ledger->syncRepayment($r));
        $this->assertEqualsWithDelta(1500, $this->ledger->houseAccount('gl-cash')->refresh()->balanceFloatNum, 0.0001);

        $r->update(['kind' => 'discount']); // reclassified: cash leg reversed, discount leg posted
        $this->ledger->syncRepayment($r);
        $this->assertEqualsWithDelta(0, $this->ledger->houseAccount('gl-cash')->refresh()->balanceFloatNum, 0.0001);
        $this->assertEqualsWithDelta(1500, $this->ledger->houseAccount('gl-discounts')->refresh()->balanceFloatNum, 0.0001);

        $r->delete();
        $this->ledger->syncRepayment($r);
        $this->assertEqualsWithDelta(0, $this->ledger->houseAccount('gl-discounts')->refresh()->balanceFloatNum, 0.0001);
        $this->assertEqualsWithDelta(0, $this->ledger->account($loan, 'gl-receivable')->refresh()->balanceFloatNum, 0.0001);
        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
        $this->assertGreaterThan(0, Transaction::where('meta->ledger', 'repayment:' . $r->id)->where('meta->ledger_dir', -1)->count(), 'history kept as reversing entries');
    }

    public function test_partner_money_in_earnings_and_payouts(): void
    {
        $partner = Partner::create(['name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test', 'type' => 'business', 'account_type' => 'principal']);
        $c = $partner->addContribution(100000, 'AZP-0005-1');
        $p = $partner->distributeProfit(3000, 'October return');
        $w = $partner->withdrawPartnerBalance(3000, 'PAYOUT-1', null, 'interest');

        foreach ([$c, $p, $w] as $pt) {
            $this->ledger->syncPartnerTransaction($pt);
        }

        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
        $this->assertEqualsWithDelta(-100000, $this->ledger->account($partner, 'gl-capital')->balanceFloatNum, 0.0001, 'AC owes the partner their capital');
        $this->assertEqualsWithDelta(0, $this->ledger->account($partner, 'gl-earnings')->balanceFloatNum, 0.0001, 'earned 3,000, paid out 3,000');
        $this->assertEqualsWithDelta(3000, $this->ledger->houseAccount('gl-partner-share')->balanceFloatNum, 0.0001);
        $this->assertEqualsWithDelta(97000, $this->ledger->houseAccount('gl-cash')->balanceFloatNum, 0.0001);

        // the partner journal check ignores ledger accounts
        $this->assertSame([], app(CustomerWallets::class)->checkPartners()['issues']);
    }

    public function test_ledger_wallets_must_use_the_ledger_decimal_places(): void
    {
        $loan = $this->loan();
        $loan->createWallet(['name' => 'wrong', 'slug' => 'gl-receivable', 'decimal_places' => 2]);
        $this->expectException(\RuntimeException::class);
        $this->ledger->account($loan, 'gl-receivable');
    }

    public function test_switched_on_it_posts_after_commit_alongside_the_wallet(): void
    {
        config(['lending.ledger.enabled' => true]);
        $loan = $this->loan();
        DB::transaction(fn () => $this->repay($loan, 700, 'QK9'));

        $this->assertEqualsWithDelta(700, $this->ledger->houseAccount('gl-cash')->balanceFloatNum, 0.0001);
        $this->assertSame(0.0, $this->ledger->trialBalance()['total']);
    }

    private function loan(): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId(['name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10]);

        return Loan::create([
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => now()->toDateString(), 'status' => 'disbursed', 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
    }

    private function disburse(Loan $loan, float $amount): Disbursement
    {
        return Disbursement::create(['loan_id' => $loan->id, 'amount' => $amount, 'net_amount' => $amount, 'transaction' => 'QD' . $amount, 'mode' => 'mpesa', 'disburse_date' => now(), 'payment_date' => now()]);
    }

    private function repay(Loan $loan, float $amount, string $ref): Repayment
    {
        return Repayment::create(['loan_id' => $loan->id, 'amount' => $amount, 'repayment_date' => now(), 'transaction' => $ref]);
    }
}
