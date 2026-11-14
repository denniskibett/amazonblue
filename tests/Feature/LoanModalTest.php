<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The new-loan modal (owner, 15 Nov 2026): it can see a signature on file, and the old page sends you to it. */
class LoanModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_modal_can_ask_whether_a_borrower_has_a_signature_and_the_old_page_opens_the_modal(): void
    {
        $this->seed(RoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('super-admin');
        $borrower = User::factory()->create();
        $borrower->assignRole('borrower');

        $this->actingAs($staff)->getJson(route('users.signature-check', $borrower))->assertOk()->assertJson(['hasSignature' => false]);
        $this->actingAs($staff)->get(route('users.loans.create', $borrower))->assertRedirect(route('users.show', $borrower) . '?new_loan=1#loans');
        $this->actingAs($staff)->get(route('users.show', $borrower) . '?new_loan=1')->assertOk()->assertSee('data-create-loan-for', false)->assertSee('The money has been sent');
    }
}
