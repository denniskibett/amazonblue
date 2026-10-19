<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Services\BorrowingEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * G7 (decision 18): a borrower with a written-off loan can't borrow again until it is settled.
 */
class WrittenOffBorrowerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_written_off_loan_blocks_a_new_application(): void
    {
        $user = $this->borrower();
        $titles = fn () => array_column(app(BorrowingEligibility::class)->blockers($user), 0);

        $this->loan($user, 'repaid');
        $this->assertNotContains('Balance still owed', $titles());

        $this->loan($user, 'written_off');
        $this->assertContains('Balance still owed', $titles());

        $before = Loan::count();
        $this->actingAs($user)->post('/account/borrow', ['amount' => 5000, 'consent' => 1])
            ->assertRedirect(route('account.borrow'))
            ->assertSessionHas('alert', fn ($alert) => $alert['title'] !== '');
        $this->assertSame($before, Loan::count());
    }

    private function borrower(): User
    {
        Role::findOrCreate('borrower', 'web');
        $user = User::factory()->create(['kyc_status' => 'approved']);
        $user->assignRole('borrower');

        return $user;
    }

    private function loan(User $user, string $status): Loan
    {
        $typeId = DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);

        return Loan::create([
            'user_id' => $user->id, 'loan_type_id' => $typeId, 'amount' => 5000, 'original_amount' => 5000,
            'borrow_date' => now()->subMonths(3)->toDateString(), 'status' => $status, 'broker_status' => 0,
            'reason' => 'School fees for the new term', 'consent' => true, 'consent_date' => now(), 'cycle' => 1,
        ]);
    }
}
