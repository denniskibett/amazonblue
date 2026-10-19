<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->staffUser();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->staffUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile/update', [
                'marital_status' => 'married',
                'religion' => 'Christian',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('married', $user->marital_status);
        $this->assertSame('Christian', $user->religion);
    }

    public function test_name_and_email_are_not_changed_from_the_profile(): void
    {
        // identity details are managed through KYC, not the profile form
        $user = $this->staffUser();
        $name = $user->name;
        $email = $user->email;

        $this
            ->actingAs($user)
            ->patch('/profile/update', [
                'name' => 'Someone Else',
                'email' => 'someone-else@example.com',
                'religion' => 'None',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame($name, $user->name);
        $this->assertSame($email, $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->staffUser();

        $response = $this
            ->actingAs($user)
            ->delete('/profile/destroy', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        // users are soft-deleted so their loan history stays intact
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = $this->staffUser();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile/destroy', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    /**
     * /profile is in the staff area; a user with no role is a customer and is sent to /account.
     */
    private function staffUser(): User
    {
        $user = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $user->assignRole('admin');

        return $user;
    }
}
