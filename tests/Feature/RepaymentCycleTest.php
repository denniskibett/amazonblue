<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * G3 (rule 8): a repayment belongs to a cycle of the same loan, checked on the server.
 */
class RepaymentCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_repayment_on_another_loans_cycle_is_refused(): void
    {
        $this->seed(RoleSeeder::class);
        $teller = User::factory()->create();
        $teller->assignRole('teller');

        $mine = $this->loan();
        $other = $this->loan();
        $payment = ['amount' => 1000, 'repayment_date' => now()->toDateString(), 'transaction' => 'QX1', 'mode' => 'cash'];

        $this->actingAs($teller)
            ->postJson('/repayments', ['loan_id' => $mine->id, 'loan_cycle_id' => $this->cycleId($other)] + $payment)
            ->assertStatus(422)
            ->assertJsonValidationErrors('loan_cycle_id');
        $this->assertSame(0, DB::table('repayments')->count());

        $this->actingAs($teller)
            ->postJson('/repayments', ['loan_id' => $mine->id, 'loan_cycle_id' => $this->cycleId($mine)] + $payment)
            ->assertOk();
        $this->assertSame(1, DB::table('repayments')->where('loan_id', $mine->id)->count());
    }

    private function cycleId(Loan $loan): int
    {
        return (int) (DB::table('loan_cycles')->where('loan_id', $loan->id)->value('id')
            ?? DB::table('loan_cycles')->insertGetId([
                'loan_id' => $loan->id, 'cycle_number' => 1, 'new_balance' => 6000, 'interest_rate' => 20,
                'start_date' => now()->toDateString(), 'due_date' => now()->addDays(10)->toDateString(), 'status' => 'active',
            ]));
    }

    private function loan(): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);

        return Loan::create([
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => now()->toDateString(), 'status' => 'disbursed', 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
    }
}
