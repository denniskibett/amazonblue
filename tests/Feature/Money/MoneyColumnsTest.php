<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use App\Services\CustomerWallets;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Stage 4 Phase 1: repayments and disbursements store their kind and who entered them.
 */
class MoneyColumnsTest extends TestCase
{
    use RefreshDatabase;

    private const REFERENCES = [
        'QK12AB34CD' => 'cash', 'ROLL OVER' => 'rollover', 'CARRY FORWARD' => 'rollover', 'CREDIT DISCOUNT' => 'discount',
        'WAIVED PENALTY' => 'discount', 'BAD DEBT' => 'bad_debt', 'WRITE-OFF' => 'bad_debt', 'TO BE PAID' => 'non_cash',
        'BROKER FEES' => 'non_cash', 'SPLIT' => 'non_cash',
    ];

    public function test_a_new_row_stores_the_kind_its_reference_meant_and_who_entered_it(): void
    {
        $loan = $this->loan();
        $staff = User::factory()->create();
        $this->actingAs($staff);

        foreach (self::REFERENCES as $ref => $kind) {
            $r = Repayment::create(['loan_id' => $loan->id, 'amount' => 100, 'repayment_date' => now(), 'transaction' => $ref]);
            $this->assertSame($kind, $r->kind, $ref);
            $this->assertSame($staff->id, $r->created_by);
        }

        $d = Disbursement::create(['loan_id' => $loan->id, 'amount' => 5000, 'transaction' => 'ROLL OVER', 'mode' => 'none', 'disburse_date' => now(), 'payment_date' => now()]);
        $this->assertSame('rollover', $d->kind);
        $this->assertSame($staff->id, $d->created_by);
    }

    public function test_a_kind_sent_by_the_form_wins_over_the_reference_text(): void
    {
        $r = Repayment::create(['loan_id' => $this->loan()->id, 'amount' => 100, 'repayment_date' => now(), 'transaction' => 'SPLIT PAYMENT QK1', 'kind' => 'cash']);
        $this->assertSame('cash', $r->fresh()->kind);
        $this->assertSame('cash', CustomerWallets::kindOf($r));

        $r->update(['notes' => 'checked']);
        $this->assertSame('cash', $r->fresh()->kind, 'saving other fields keeps the stored kind');

        $r->update(['transaction' => 'ROLL OVER']);
        $this->assertSame('rollover', $r->fresh()->kind, 'changing only the reference re-reads it, as before');
    }

    public function test_backfill_fills_every_row_to_match_the_text_rule_and_runs_twice_the_same(): void
    {
        $loan = $this->loan();
        $staff = User::factory()->create();
        foreach (array_keys(self::REFERENCES) as $i => $ref) {
            $id = DB::table('repayments')->insertGetId(['loan_id' => $loan->id, 'amount' => 100, 'repayment_date' => now()->toDateString(), 'transaction' => $ref]);
            if ($i === 0) {
                DB::table('audit_logs')->insert(['table_name' => 'repayments', 'record_id' => $id, 'action' => 'create', 'user_id' => $staff->id, 'created_at' => now()]);
            }
        }

        $this->artisan('money:backfill-columns --dry-run')->assertSuccessful();
        $this->assertSame(count(self::REFERENCES), DB::table('repayments')->whereNull('kind')->count(), 'dry run changes nothing');

        $this->artisan('money:backfill-columns')->assertSuccessful();
        $snapshot = DB::table('repayments')->orderBy('id')->get(['id', 'transaction', 'kind', 'created_by']);
        foreach ($snapshot as $row) {
            $this->assertSame(CustomerWallets::kind($row->transaction), $row->kind);
        }
        $this->assertSame($staff->id, (int) $snapshot->first()->created_by);

        $this->artisan('money:backfill-columns')->assertSuccessful();
        $this->assertEquals($snapshot, DB::table('repayments')->orderBy('id')->get(['id', 'transaction', 'kind', 'created_by']));
    }

    public function test_where_cash_reads_the_stored_kind_and_falls_back_to_the_text(): void
    {
        $loan = $this->loan();
        DB::table('repayments')->insert([
            ['loan_id' => $loan->id, 'amount' => 100, 'repayment_date' => now()->toDateString(), 'transaction' => 'QK1', 'kind' => null],       // cash by text
            ['loan_id' => $loan->id, 'amount' => 200, 'repayment_date' => now()->toDateString(), 'transaction' => 'ROLL OVER', 'kind' => null], // book by text
            ['loan_id' => $loan->id, 'amount' => 400, 'repayment_date' => now()->toDateString(), 'transaction' => 'SPLIT', 'kind' => 'cash'],   // stored wins
            ['loan_id' => $loan->id, 'amount' => 800, 'repayment_date' => now()->toDateString(), 'transaction' => 'QK2', 'kind' => 'discount'], // stored wins
        ]);

        $this->assertEquals(500, (float) CustomerWallets::whereCash(Repayment::query())->sum('amount'));
        $this->assertEquals(500, (float) DB::table('repayments')->get()->filter(fn ($r) => CustomerWallets::kindOf($r) === 'cash')->sum('amount'));
    }

    public function test_the_repayment_form_accepts_a_kind_and_refuses_an_unknown_one(): void
    {
        $this->seed(RoleSeeder::class);
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $loan = $this->loan();
        $cycle = DB::table('loan_cycles')->insertGetId([
            'loan_id' => $loan->id, 'cycle_number' => 1, 'new_balance' => 6000, 'interest_rate' => 20,
            'start_date' => now()->toDateString(), 'due_date' => now()->addDays(10)->toDateString(), 'status' => 'active',
        ]);
        $payment = ['loan_id' => $loan->id, 'loan_cycle_id' => $cycle, 'amount' => 1000, 'repayment_date' => now()->toDateString(), 'transaction' => 'QX1'];

        $this->actingAs($teller)->postJson('/repayments', $payment + ['kind' => 'gift'])->assertStatus(422)->assertJsonValidationErrors('kind');
        $this->actingAs($teller)->postJson('/repayments', $payment + ['kind' => 'discount'])->assertOk();
        $this->assertSame('discount', DB::table('repayments')->value('kind'));
        $this->assertSame($teller->id, (int) DB::table('repayments')->value('created_by'));
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
}
