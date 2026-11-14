<?php

namespace Tests\Feature;

use App\Models\FacilitySplit;
use App\Models\Investment;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/** Fixed-return lines and the facility split screen (owner, 14 Nov 2026). */
class FixedReturnAndFacilityScreenTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $staff;
    private Partner $house;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->staff = User::factory()->create();
        $this->staff->assignRole('super-admin');
        $this->house = Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
    }

    public function test_a_fixed_return_line_is_owed_its_percent_and_paid_back_from_the_wallets(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-11-24 12:00');
        $yvonne = Partner::create(['name' => 'Yvonne Test', 'email' => 'yvonne@example.test', 'type' => 'individual']);
        // 20% x 40% = 8% for 10 days
        $this->actingAs($this->staff)->post(route('staff.partners.lines.store', $yvonne), [
            'amount' => 100000, 'code' => 'BANK845145', 'date' => '2026-11-14', 'term' => 'short_term',
            'kind' => 'fixed_return', 'fixed_rate' => 8, 'fixed_days' => 10, 'share_rate' => 60, 'penalty_rate' => 50,
        ])->assertSessionHasNoErrors()->assertSessionHas('alert');

        $line = Investment::where('return_type', 'fixed_return')->firstOrFail();
        $this->assertSame('2026-11-24', $line->maturity_date->toDateString());
        $this->assertEquals(8, (float) $line->expected_return);
        $this->assertSame([100000.0, 8000.0, 108000.0, '2026-11-24'], app(\App\Services\Partners\PartnerLines::class)->fixedDue($line));
        $this->assertEquals(0, (float) $line->partner_share_rate, 'it takes no share of the loans it funds');
        $this->actingAs($this->staff)->get(route('partners.show', $yvonne))->assertOk()->assertSee('Fixed 8% · 10 days')->assertSee('Record payout');

        $this->actingAs($this->staff)->post(route('staff.partners.lines.pay-back', [$yvonne, $line]), ['paid' => 108000, 'date' => '2026-11-24', 'code' => 'PAY24NOV'])
            ->assertSessionHasNoErrors();
        $line->refresh();
        $this->assertSame('matured', $line->status);
        $this->assertEquals(0, app(\App\Services\Partners\PartnerLines::class)->status($line)['idle'], 'a paid-back fixed line has nothing free');
        $this->assertEquals(8000, (float) PartnerTransaction::where('partner_id', $yvonne->id)->where('type', 'profit_distribution')->sum('amount'));
        $this->assertEqualsWithDelta(0, (float) $yvonne->getWallet('principal')->refresh()->balanceFloat, 0.001);
        $this->assertEqualsWithDelta(0, (float) $yvonne->getWallet('interest')->refresh()->balanceFloat, 0.001);
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_a_payout_below_the_capital_is_refused(): void
    {
        $p = Partner::create(['name' => 'Ruth Test', 'email' => 'ruth@example.test', 'type' => 'individual']);
        $this->actingAs($this->staff)->post(route('staff.partners.lines.store', $p), [
            'amount' => 10000, 'code' => 'UKDMY1TEST', 'date' => '2026-11-13', 'term' => 'short_term', 'kind' => 'fixed_return', 'fixed_rate' => 4, 'fixed_days' => 1,
        ])->assertSessionHasNoErrors();
        $line = Investment::where('return_type', 'fixed_return')->firstOrFail();
        $this->actingAs($this->staff)->post(route('staff.partners.lines.pay-back', [$p, $line]), ['paid' => 9000, 'date' => '2026-11-14', 'code' => 'X1'])
            ->assertSessionHasErrors('paid');
        $this->assertSame('active', $line->fresh()->status);
    }

    public function test_the_facility_split_screen_previews_saves_and_removes(): void
    {
        $this->loanTypes();
        $this->loanRow(458, 1, 244000, 244000, '2026-06-30', '2026-07-10', 'disbursed', 1, 0);
        $this->cycleRow(4581, 458, 1, 0, 48800, 292800, 20, '2026-06-30', '2026-07-10', 'active', 'Initial');
        $isiro = Partner::create(['name' => 'ISIRO AGENCIES', 'email' => 'isiro@example.test', 'type' => 'business']);
        $form = ['parties' => [
            ['partner_id' => $this->house->id, 'principal' => 200000, 'penalty_pct' => 50, 'received_before' => 150000],
            ['partner_id' => $isiro->id, 'principal' => 44000, 'penalty_pct' => 50, 'received_before' => 70000],
        ], 'pi_target' => 421632, 'total_cap' => 1120000, 'counted_from' => '2026-11-14'];

        $this->actingAs($this->staff)->get(route('staff.loans.partners', 458))->assertOk()->assertSee('Facility split');
        $this->actingAs($this->staff)->post(route('staff.loans.facility-split', 458), $form + ['mode' => 'preview'])->assertSessionHas('facility_preview');
        $this->assertSame(0, FacilitySplit::count());

        $this->actingAs($this->staff)->post(route('staff.loans.facility-split', 458), ['parties' => [$form['parties'][0], ['partner_id' => $isiro->id, 'principal' => 44000, 'penalty_pct' => 40]]]
            + $form + ['mode' => 'save'])->assertSessionHasErrors('facility');   // penalty shares add up to 90%

        $this->actingAs($this->staff)->post(route('staff.loans.facility-split', 458), $form + ['mode' => 'save'])->assertSessionHasNoErrors();
        $this->assertEquals(76032, (float) DB::table('facility_split_parties')->where('partner_id', $isiro->id)->value('pi_entitled'));
        $this->actingAs($this->staff)->get(route('staff.loans.partners', 458))->assertSee('355,216.00')->assertSee('544,784.00');

        $this->actingAs($this->staff)->delete(route('staff.loans.facility-split.destroy', 458))->assertSessionHasNoErrors();
        $this->assertSame(0, FacilitySplit::count());
    }

    public function test_an_investor_paid_from_another_line_takes_that_lines_money_and_terms_can_change(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-11-14 12:00');
        $yvonne = Partner::create(['name' => 'Yvonne Test', 'email' => 'yv@example.test', 'type' => 'individual']);
        $ruth = Partner::create(['name' => 'Ruth Test', 'email' => 'ru@example.test', 'type' => 'individual']);
        $this->actingAs($this->staff)->post(route('staff.partners.lines.store', $yvonne), ['amount' => 100000, 'code' => 'BANK1', 'date' => '2026-11-14',
            'term' => 'short_term', 'kind' => 'fixed_return', 'fixed_rate' => 8, 'fixed_share' => 40, 'fixed_days' => 10])->assertSessionHasNoErrors();
        $this->actingAs($this->staff)->post(route('staff.partners.lines.store', $ruth), ['amount' => 10000, 'code' => 'MPESA1', 'date' => '2026-11-13',
            'term' => 'short_term', 'kind' => 'fixed_return', 'fixed_rate' => 4, 'fixed_share' => 20, 'fixed_days' => 1])->assertSessionHasNoErrors();
        [$yl, $rl] = [Investment::where('name', 'like', 'Yvonne%')->first(), Investment::where('name', 'like', 'Ruth%')->first()];
        $this->assertStringContainsString('Fixed 40% of 20% interest · 10 days', $yl->name);

        // Ruth is paid back on our money, then the payout is placed on Yvonne's line
        $this->actingAs($this->staff)->post(route('staff.partners.lines.pay-back', [$ruth, $rl]), ['paid' => 10400, 'date' => '2026-11-14', 'code' => 'PAYR'])->assertSessionHasNoErrors();
        $this->assertEquals(100000, $yl->fresh()->remainingCommitted());
        $this->actingAs($this->staff)->post(route('staff.partners.lines.payout-source', [$ruth, $rl]), ['from_line' => $yl->id])->assertSessionHasNoErrors();
        $this->assertEquals(89600, $yl->fresh()->remainingCommitted());
        // saving it twice does not take it twice; moving it back gives the money back
        $this->actingAs($this->staff)->post(route('staff.partners.lines.payout-source', [$ruth, $rl]), ['from_line' => $yl->id])->assertSessionHasNoErrors();
        $this->assertEquals(89600, $yl->fresh()->remainingCommitted());
        $this->actingAs($this->staff)->post(route('staff.partners.lines.payout-source', [$ruth, $rl]), ['from_line' => null])->assertSessionHasNoErrors();
        $this->assertEquals(100000, $yl->fresh()->remainingCommitted());

        // terms: 20% x 50% for 12 days -> 10%, due 26 Nov
        $this->actingAs($this->staff)->post(route('staff.partners.lines.terms', [$yvonne, $yl]), ['gross' => 20, 'share' => 50, 'days' => 12])->assertSessionHasNoErrors();
        $yl->refresh();
        $this->assertEquals(10, (float) $yl->interest_rate);
        $this->assertSame('2026-11-26', $yl->maturity_date->toDateString());
        $this->actingAs($this->staff)->get(route('partners.show', $yvonne))->assertOk()->assertSee('Fixed 50% of 20% interest · 12 days');
        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_the_loans_list_can_receive_a_payment(): void
    {
        $this->actingAs($this->staff)->get(route('loans.index'))->assertOk()->assertSee('window.openRepaymentModal', false);
    }
}
