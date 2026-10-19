<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Services\LoanCalculator;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * G5: a loan that has moved no money gets a fresh cycle 1 when its amount, product or date is edited.
 */
class LoanReopenCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_reopening_a_rejected_loan_with_a_new_amount_rebuilds_cycle_one(): void
    {
        $loan = $this->loan('rejected');
        $this->assertSame(6000.0, (float) $loan->cycles()->value('new_balance'));

        $this->actingAs($this->staff('manager'))
            ->put("/loans/{$loan->id}", ['amount' => 4000, 'status' => 'pending'] + $this->form($loan))
            ->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertSame('pending', $loan->status);
        $this->assertSame(1, $loan->cycles()->count());
        $cycle = $loan->cycles()->first();
        $this->assertSame(4800.0, (float) $cycle->new_balance);
        $this->assertSame(800.0, (float) $cycle->interest_capitalized);
        $this->assertSame(4000.0, (float) $loan->original_amount);
    }

    public function test_cycle_one_is_left_alone_once_money_has_moved(): void
    {
        $loan = $this->loan('disbursed');
        DB::table('repayments')->insert([
            'loan_id' => $loan->id, 'loan_cycle_id' => $loan->cycles()->value('id'), 'amount' => 1000,
            'repayment_date' => now()->toDateString(), 'transaction' => 'QX1', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertFalse(app(LoanCalculator::class)->rebuildInitialCycle($loan));

        $this->actingAs($this->staff('admin'))
            ->put("/loans/{$loan->id}", ['amount' => 4000, 'status' => 'disbursed'] + $this->form($loan))
            ->assertSessionHasNoErrors();
        $this->assertSame(6000.0, (float) $loan->cycles()->value('new_balance'));
    }

    private function form(Loan $loan): array
    {
        return [
            'loan_type_id' => $loan->loan_type_id,
            'amount' => (float) $loan->amount,
            'borrow_date' => $loan->borrow_date instanceof \DateTimeInterface ? $loan->borrow_date->format('Y-m-d') : $loan->borrow_date,
            'broker_status' => 0,
            'reason' => $loan->reason,
            'consent' => '1',
        ];
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function loan(string $status): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);
        $loan = Loan::create([
            'user_id' => User::factory()->create()->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => now()->toDateString(), 'status' => $status, 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
        app(LoanCalculator::class)->createInitialCycle($loan);

        return $loan->fresh();
    }
}
