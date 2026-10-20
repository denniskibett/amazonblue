<?php

namespace Tests\Feature\Money;

use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Stage 4 Phase 3: each cash repayment is split between broker, funder and AC.
 */
class RepaymentSplitTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private Partner $broker;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['lending.engine' => 'v2', 'lending.split.enabled' => true, 'lending.ledger.enabled' => false, 'lending.penalty.cap' => 'none']);
        $this->loanTypes();
        $brokerUser = User::factory()->create();
        $this->broker = Partner::create(['name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test', 'type' => 'business', 'account_type' => 'principal', 'user_id' => $brokerUser->id]);
        DB::table('brokers')->insert(['user_id' => $brokerUser->id, 'interest_client' => 20, 'interest_broker' => 40, 'penalty_client' => 0, 'penalty_broker' => 50, 'cert_no' => 'BRK-TEST-1']);
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        Carbon::setTestNow('2026-10-21 10:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function loan(int $id, bool $brokered, ?int $investmentId = null): LoanCycle
    {
        // 10,000 at 20%, 10%/day, due 10-10
        $this->loanRow($id, 1, 10000, 10000, '2026-09-30', '2026-10-10', 'disbursed', 1, 30);
        DB::table('loans')->where('id', $id)->update(['broker_status' => (int) $brokered, 'partner_id' => $brokered ? $this->broker->id : null,
            'broker_rate' => 40, 'broker_penalty_rate' => $brokered ? 50 : null]);
        $this->cycleRow($id, $id, 1, 0, 2000, 12000, 20, '2026-09-30', '2026-10-10', 'active', 'Initial');
        DB::table('disbursements')->insert(['loan_id' => $id, 'loan_cycle_id' => $id, 'amount' => 10000, 'net_amount' => 10000, 'transaction' => 'QD' . $id,
            'kind' => 'cash', 'mode' => 'mpesa', 'disburse_date' => '2026-09-30', 'payment_date' => '2026-09-30', 'investment_id' => $investmentId]);

        return LoanCycle::findOrFail($id);
    }

    private function pay(int $loanId, float $amount, string $date, string $ref): Repayment
    {
        return DB::transaction(fn () => Repayment::create(['loan_id' => $loanId, 'loan_cycle_id' => $loanId, 'amount' => $amount, 'repayment_date' => $date, 'transaction' => $ref]));
    }

    private function earned(int $partnerId, string $type): float
    {
        return round((float) PartnerTransaction::where('partner_id', $partnerId)->where('type', $type)->sum('amount'), 2);
    }

    public function test_an_introducer_broker_earns_40_percent_of_interest_and_50_percent_of_penalty_paid(): void
    {
        $this->loan(701, true);
        // paid one day late: penalty 1,200 first, then interest 2,000, then principal 10,000
        $this->pay(701, 13200, '2026-10-11', 'QK701');

        $this->assertSame(800.0, $this->earned($this->broker->id, 'broker_fee_earned'));   // 40% of 2,000
        $this->assertSame(600.0, $this->earned($this->broker->id, 'penalty_share'));       // 50% of 1,200
        $this->assertEqualsWithDelta(1400, (float) $this->broker->getWallet('broker_fee')->refresh()->balanceFloat, 0.001);
    }

    public function test_a_reversed_payment_takes_its_shares_back_and_book_entries_earn_nothing(): void
    {
        $this->loan(702, true);
        $r = $this->pay(702, 13200, '2026-10-11', 'QK702');
        $this->pay(702, 500, '2026-10-11', 'CREDIT DISCOUNT');                   // book entry
        $this->assertSame(800.0, $this->earned($this->broker->id, 'broker_fee_earned'));

        DB::transaction(fn () => $r->delete());
        $this->assertSame(0.0, $this->earned($this->broker->id, 'broker_fee_earned'));
        $this->assertSame(0.0, $this->earned($this->broker->id, 'penalty_share'));
        $this->assertGreaterThan(2, PartnerTransaction::where('partner_id', $this->broker->id)->count(), 'history kept as adjustment rows');
    }

    public function test_a_broker_funding_our_client_earns_60_percent_and_principal_frees_their_capital(): void
    {
        $line = Investment::create(['name' => 'ISIRO top-up line', 'type' => 'other', 'facility_type' => 'short_term', 'return_type' => 'loan_share',
            'country' => 'Kenya', 'initial_amount' => 0, 'purchase_date' => '2026-09-01', 'status' => 'active']);
        InvestmentFunding::create(['investment_id' => $line->id, 'partner_id' => $this->broker->id, 'amount_committed' => 50000,
            'amount_disbursed' => 10000, 'amount_returned' => 0, 'status' => 'active']);
        $this->loan(703, true, $line->id);
        $this->pay(703, 12000, '2026-10-09', 'QK703');                          // on time: 2,000 interest, 10,000 principal

        $this->assertSame(1200.0, $this->earned($this->broker->id, 'broker_fee_earned'));   // 100 - 40 = 60% of 2,000
        $this->assertSame(10000.0, (float) InvestmentFunding::where('investment_id', $line->id)->value('amount_returned'));
        $this->assertSame(0.0, $this->broker->fresh()->lentOut());
    }

    public function test_an_unbrokered_house_loan_pays_nobody(): void
    {
        $this->loan(704, false);
        $this->pay(704, 13200, '2026-10-11', 'QK704');
        $this->assertSame(0, PartnerTransaction::count());
    }

    public function test_restatement_replaces_the_backfill_with_per_repayment_rows(): void
    {
        config(['lending.split.enabled' => false]);                              // history recorded before the switch
        $this->loan(705, true);
        $this->pay(705, 13200, '2026-10-11', 'QK705');
        app(\App\Services\PartnerLedger::class)->post($this->broker, 'broker_fee', 'broker_fee_earned', 999, reference: 'BACKFILL-7OCT-1');

        $this->artisan('allocations:restate --dry-run')->assertSuccessful();
        $sha = hash('sha256', Storage::disk('local')->get('reports/allocations-restate-2026-10-21.csv'));
        $this->artisan('allocations:restate --apply --confirm=' . $sha)->assertFailed();     // switch off
        config(['lending.split.enabled' => true]);
        $this->artisan('allocations:restate --apply --confirm=' . $sha)->assertSuccessful();

        $this->assertSame(800.0, $this->earned($this->broker->id, 'broker_fee_earned'));      // 999 backfill reversed, 800 split
        $this->assertSame(1, PartnerTransaction::where('reference', 'BACKFILL-7OCT-1')->count(), 'the backfill row stays');
        $this->artisan('allocations:restate --dry-run')->assertSuccessful();
        $sha2 = hash('sha256', Storage::disk('local')->get('reports/allocations-restate-2026-10-21.csv'));
        $this->artisan('allocations:restate --apply --confirm=' . $sha2)->assertSuccessful();
        $this->assertSame(800.0, $this->earned($this->broker->id, 'broker_fee_earned'), 'running again changes nothing');
    }

    public function test_a_fixed_rate_partner_earns_whole_calendar_months_from_the_month_after_funding(): void
    {
        $p = Partner::create(['name' => 'Long-term partner', 'email' => 'lt@example.test', 'type' => 'individual', 'account_type' => 'principal']);
        $line = Investment::create(['name' => '3% monthly', 'type' => 'other', 'facility_type' => 'long_term', 'return_type' => 'fixed_rate', 'interest_rate' => 3,
            'country' => 'Kenya', 'initial_amount' => 0, 'purchase_date' => '2026-03-14', 'status' => 'active']);
        $f = InvestmentFunding::create(['investment_id' => $line->id, 'partner_id' => $p->id, 'amount_committed' => 100000,
            'amount_disbursed' => 0, 'amount_returned' => 0, 'status' => 'active']);
        DB::table('investment_fundings')->where('id', $f->id)->update(['created_at' => '2026-03-14 09:00:00']);

        Carbon::setTestNow('2026-10-21 10:00');                                    // April..September = 6 months
        $this->artisan('partners:accrue-fixed')->assertSuccessful();
        $this->assertSame(18000.0, $this->earned($p->id, 'profit_distribution'));  // 6 x 3,000; nothing for 14-31 March
        $this->artisan('partners:accrue-fixed')->assertSuccessful();
        $this->assertSame(18000.0, $this->earned($p->id, 'profit_distribution'), 'once per month');

        // leaves on 20 Nov: October is complete, November is forfeited
        DB::table('investment_fundings')->where('id', $f->id)->update(['status' => 'settled', 'updated_at' => '2026-11-20 12:00:00']);
        Carbon::setTestNow('2026-12-05 10:00');
        $this->artisan('partners:accrue-fixed')->assertSuccessful();
        $this->assertSame(21000.0, $this->earned($p->id, 'profit_distribution'));
    }
}
