<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_register_as_individual_borrowers(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class); // sign-up assigns the borrower role

        $response = $this->post('/register', [
            'account_type' => 'individual',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0712345678',
            'password' => 'password',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole('borrower'));
        $response->assertRedirect(route('kyc.personal', absolute: false));
    }

    public function test_business_registration_is_not_open_yet(): void
    {
        $response = $this->post('/register', [
            'account_type' => 'business',
            'name' => 'Test Business',
            'email' => 'biz@example.com',
            'phone' => '0712345679',
            'password' => 'password',
            'terms' => '1',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('account_type');
    }
}
