<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\Partner;
use App\Models\Repayment;
use App\Models\User;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Stage 4 Phase 1: a repayment entered before today is reversed by a new entry, not deleted.
 */
class RepaymentReversalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        Partner::create(['name' => 'House Account', 'email' => 'house@example.test', 'type' => 'company', 'account_type' => 'principal', 'is_house_account' => true]);
        config(['lending.ledger.enabled' => true, 'lending.ledger.reversals' => true]);
    }

    public function test_an_older_repayment_is_reversed_by_a_new_entry_and_the_original_stays(): void
    {
        [$loan, $r] = $this->paid(2500, 'QK1', yesterday: true);

        $this->actingAs($this->admin)->deleteJson('/repayments/' . $r->id, ['reason' => 'Paid to the wrong loan'])->assertOk()
            ->assertJsonPath('reversal_id', fn ($id) => $id !== null);

        $this->assertFalse($r->fresh()->trashed(), 'the original is kept');
        $reversal = Repayment::where('reversal_of_id', $r->id)->firstOrFail();
        $this->assertEquals(-2500, (float) $reversal->amount);
        $this->assertSame('cash', $reversal->kind);
        $this->assertSame($r->repayment_date->toDateString(), $reversal->repayment_date->toDateString());
        $this->assertSame('Paid to the wrong loan', $reversal->notes);
        $this->assertEquals(0, (float) Repayment::where('loan_id', $loan->id)->sum('amount'));

        // wallet and ledger net to zero; the history shows both entries
        $this->assertEqualsWithDelta(0, (float) $loan->user->getWallet(CustomerWallets::BORROWER_WALLET)->balanceFloat, 0.001);
        $ledger = app(Ledger::class);
        $this->assertEqualsWithDelta(0, $ledger->houseAccount('gl-cash')->balanceFloatNum, 0.0001);
        $this->assertEqualsWithDelta(0, $ledger->account($loan, 'gl-receivable')->balanceFloatNum, 0.0001);
        $this->assertSame(0.0, $ledger->trialBalance()['total']);

        // once only
        $this->actingAs($this->admin)->deleteJson('/repayments/' . $r->id)->assertStatus(422);
        $this->actingAs($this->admin)->deleteJson('/repayments/' . $reversal->id)->assertOk(); // undoing the reversal today is a same-day fix
        $this->assertTrue($reversal->fresh()->trashed());
    }

    public function test_a_same_day_mistake_is_still_deleted(): void
    {
        [, $r] = $this->paid(1000, 'QK2', yesterday: false);
        $this->actingAs($this->admin)->deleteJson('/repayments/' . $r->id)->assertOk()->assertJsonPath('reversal_id', null);
        $this->assertTrue($r->fresh()->trashed());
        $this->assertSame(0, Repayment::where('reversal_of_id', $r->id)->count());
    }

    public function test_with_the_switch_off_every_delete_is_a_soft_delete(): void
    {
        config(['lending.ledger.reversals' => false]);
        [, $r] = $this->paid(1000, 'QK3', yesterday: true);
        $this->actingAs($this->admin)->deleteJson('/repayments/' . $r->id)->assertOk();
        $this->assertTrue($r->fresh()->trashed());
    }

    private function paid(float $amount, string $ref, bool $yesterday): array
    {
        $typeId = DB::table('loan_types')->insertGetId(['name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10]);
        $loan = Loan::create([
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => now()->toDateString(), 'status' => 'disbursed', 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
        $cycle = DB::table('loan_cycles')->insertGetId([
            'loan_id' => $loan->id, 'cycle_number' => 1, 'new_balance' => 6000, 'interest_rate' => 20,
            'start_date' => now()->toDateString(), 'due_date' => now()->addDays(10)->toDateString(), 'status' => 'active',
        ]);
        $r = DB::transaction(fn () => Repayment::create(['loan_id' => $loan->id, 'loan_cycle_id' => $cycle, 'amount' => $amount, 'repayment_date' => now()->subDay(), 'transaction' => $ref]));
        if ($yesterday) {
            DB::table('repayments')->where('id', $r->id)->update(['created_at' => now()->subDay()]);
        }

        return [$loan, $r->fresh()];
    }
}
