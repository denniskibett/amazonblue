<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * G2: `disbursed` and `repaid` come only from Disburse and Receive payment, never from the loan form.
 */
class LoanStatusByEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_editing_a_loan_to_repaid_or_disbursed_is_refused(): void
    {
        $admin = $this->staff('admin');

        foreach (['approved' => 'repaid', 'pending' => 'disbursed', 'disbursed' => 'repaid'] as $from => $to) {
            $loan = $this->loan($from);
            $this->actingAs($admin)->putJson("/loans/{$loan->id}", ['status' => $to] + $this->form($loan))
                ->assertStatus(422)
                ->assertJsonValidationErrors('status');
            $this->assertSame($from, $loan->fresh()->status);
        }
    }

    public function test_creating_a_loan_as_repaid_or_disbursed_is_refused(): void
    {
        $admin = $this->staff('admin');
        $borrower = User::factory()->create();
        $loan = $this->loan('pending');

        foreach (['disbursed', 'repaid'] as $status) {
            $this->actingAs($admin)->postJson('/loans', ['user_id' => $borrower->id, 'status' => $status] + $this->form($loan))
                ->assertStatus(422)
                ->assertJsonValidationErrors('status');
        }
        $this->assertSame(0, Loan::where('user_id', $borrower->id)->count());
    }

    public function test_editing_details_keeps_the_current_money_status(): void
    {
        $loan = $this->loan('disbursed');

        $this->actingAs($this->staff('admin'))
            ->put("/loans/{$loan->id}", ['status' => 'disbursed', 'reason' => 'School fees, updated note'] + $this->form($loan))
            ->assertSessionHasNoErrors();
        $this->assertSame('disbursed', $loan->fresh()->status);
        $this->assertSame('School fees, updated note', $loan->fresh()->reason);
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

        return Loan::create([
            'user_id' => User::factory()->create()->id,
            'loan_type_id' => $typeId,
            'amount' => 5000,
            'original_amount' => 5000,
            'borrow_date' => now()->toDateString(),
            'status' => $status,
            'broker_status' => 0,
            'reason' => 'School fees for the new term',
            'consent' => true,
            'consent_date' => now(),
            'cycle' => 1,
        ]);
    }
}
