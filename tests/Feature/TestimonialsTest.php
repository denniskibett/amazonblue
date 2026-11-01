<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Testimonials (owner, 1 Nov 2026): only real borrowers whose loan was sent can write one, staff approve it,
 * and the website shows approved ones only (the section is hidden when there are none).
 */
class TestimonialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_section_is_hidden_when_there_are_none(): void
    {
        $this->get('/')->assertOk()->assertDontSee('What our clients say')->assertDontSee('James Mwangi');
    }

    public function test_an_approved_testimonial_is_shown_and_a_pending_one_is_not(): void
    {
        $this->testimonial($this->borrower(), 'approved', 'Fast and fair, the money came the same day.', 'Sarah K.');
        $this->testimonial($this->borrower(), 'pending', 'Still waiting to be read by the team.', 'Peter O.');

        $this->get('/')->assertOk()->assertSee('What our clients say')
            ->assertSee('Fast and fair, the money came the same day.')->assertSee('Sarah K.')->assertSee('SK')
            ->assertDontSee('Still waiting to be read by the team.');
    }

    public function test_a_pending_one_alone_keeps_the_section_hidden(): void
    {
        $this->testimonial($this->borrower(), 'pending', 'Not approved yet, should not show.', 'Peter O.');

        $this->get('/')->assertOk()->assertDontSee('What our clients say');
    }

    public function test_a_borrower_without_a_sent_loan_cannot_write_one(): void
    {
        $user = $this->borrower();
        $this->loan($user, 'pending');

        $this->actingAs($user)->get(route('account.testimonial'))->assertForbidden();
        $this->actingAs($user)->post(route('account.testimonial.save'), $this->form())->assertForbidden();
        $this->assertSame(0, Testimonial::count());
    }

    public function test_a_borrower_with_a_repaid_loan_writes_one_and_an_edit_goes_back_to_pending(): void
    {
        $user = $this->borrower();
        $loan = $this->loan($user, 'repaid');

        $this->actingAs($user)->get(route('account.home'))->assertOk()->assertSee('Share your experience');
        $this->actingAs($user)->get(route('account.testimonial'))->assertOk();
        $this->actingAs($user)->post(route('account.testimonial.save'), $this->form())->assertRedirect(route('account.testimonial'));
        $t = Testimonial::sole();
        $this->assertSame(['pending', $loan->id, 4], [$t->status, $t->loan_id, $t->rating]);

        $t->update(['status' => 'approved', 'approved_at' => now()]);
        $this->actingAs($user)->post(route('account.testimonial.save'), $this->form(['body' => 'Changed my words after a second loan.']))->assertRedirect();
        $this->assertSame(1, Testimonial::count());
        $this->assertSame('pending', $t->fresh()->status);

        // consent is required
        $this->actingAs($user)->post(route('account.testimonial.save'), $this->form(['consent' => null]))->assertSessionHasErrors('consent');
    }

    public function test_staff_with_edit_users_approve_and_hide(): void
    {
        $t = $this->testimonial($this->borrower(), 'pending', 'The team was kind when I needed more time.', 'Grace W.');
        Permission::findOrCreate('edit users', 'web');
        $staff = User::factory()->create();
        $staff->assignRole(Role::findOrCreate('manager', 'web'));
        $staff->givePermissionTo('edit users');

        $this->actingAs($staff)->get(route('staff.testimonials'))->assertOk()->assertSee('Grace W.');
        $this->actingAs($staff)->post(route('staff.testimonials.decide', $t), ['decision' => 'approve'])->assertRedirect();
        $t->refresh();
        $this->assertSame(['approved', $staff->id], [$t->status, $t->approved_by]);
        $this->get('/')->assertSee('The team was kind when I needed more time.');

        $this->actingAs($staff)->post(route('staff.testimonials.decide', $t), ['decision' => 'hide'])->assertRedirect();
        $this->assertSame('hidden', $t->fresh()->status);
        $this->get('/')->assertDontSee('What our clients say');

        // without the permission
        $other = User::factory()->create();
        $other->assignRole(Role::findOrCreate('accountant', 'web'));
        $this->actingAs($other)->post(route('staff.testimonials.decide', $t), ['decision' => 'approve'])->assertForbidden();
    }

    private function form(array $over = []): array
    {
        return array_merge(['rating' => 4, 'body' => 'Quick and clear, no hidden charges.', 'display_name' => 'Jane D.',
            'display_role' => 'Trader, Nakuru', 'consent' => '1'], $over);
    }

    private function borrower(): User
    {
        Role::findOrCreate('borrower', 'web');
        $user = User::factory()->create(['kyc_status' => 'approved']);
        $user->assignRole('borrower');

        return $user;
    }

    private function testimonial(User $user, string $status, string $body, string $name): Testimonial
    {
        return Testimonial::create(['user_id' => $user->id, 'rating' => 5, 'body' => $body, 'display_name' => $name,
            'status' => $status, 'approved_at' => $status === 'approved' ? now() : null]);
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
