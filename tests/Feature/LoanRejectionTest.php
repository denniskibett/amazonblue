<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rejecting is an approval decision; a rejected loan reopens only when staff update its details.
 */
class LoanRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_staff_who_can_approve_may_reject(): void
    {
        $loan = $this->loan('pending');

        $this->actingAs($this->staff('teller', ['view loans', 'edit loans']))
            ->patch("/loans/{$loan->id}/reject", ['rejection_reason' => 'Income could not be confirmed'])
            ->assertForbidden();
        $this->assertSame('pending', $loan->fresh()->status);

        $this->actingAs($this->staff('manager', ['view loans', 'edit loans', 'approve loans']))
            ->patch("/loans/{$loan->id}/reject", ['rejection_reason' => 'Income could not be confirmed'])
            ->assertRedirect();
        $this->assertSame('rejected', $loan->fresh()->status);
    }

    public function test_a_rejected_loan_reopens_only_when_its_details_change(): void
    {
        $loan = $this->loan('rejected');
        $manager = $this->staff('manager', ['view loans', 'edit loans', 'approve loans']);
        $form = [
            'loan_type_id' => $loan->loan_type_id,
            'amount' => $loan->amount,
            'borrow_date' => $loan->borrow_date instanceof \DateTimeInterface ? $loan->borrow_date->format('Y-m-d') : $loan->borrow_date,
            'status' => 'pending',
            'broker_status' => 0,
            'reason' => $loan->reason,
            'consent' => '1',
        ];

        // nothing changed: stays rejected
        $this->actingAs($manager)->put("/loans/{$loan->id}", $form)->assertSessionHasErrors('status');
        $this->assertSame('rejected', $loan->fresh()->status);

        // details updated: back to pending, with a note
        $this->actingAs($manager)->put("/loans/{$loan->id}", ['amount' => 4000] + $form)->assertSessionHasNoErrors();
        $this->assertSame('pending', $loan->fresh()->status);
        $this->assertStringContainsString('Reopened by', (string) $loan->fresh()->recovery_notes);
    }

    private function staff(string $role, array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function loan(string $status): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);
        $borrower = User::factory()->create();

        return Loan::create([
            'user_id' => $borrower->id,
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
