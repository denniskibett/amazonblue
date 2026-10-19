<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * P1 #17, decision 17: customers confirm their email before submitting KYC; staff are not locked out.
 */
class KycEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_an_unverified_borrower_cannot_submit_kyc(): void
    {
        $user = User::factory()->unverified()->create();
        $user->assignRole('borrower');

        $this->actingAs($user)->get('/kyc/review')->assertOk()->assertSee('Confirm your email first');
        $this->actingAs($user)->post('/kyc/review', [])->assertRedirect(route('verification.notice'));
        $this->assertNotSame('submitted', $user->fresh()->kyc_status);
    }

    public function test_a_verified_borrower_reaches_the_submit_step(): void
    {
        $user = User::factory()->create();
        $user->assignRole('borrower');

        $this->actingAs($user)->get('/kyc/review')->assertOk()->assertDontSee('Confirm your email first');
        $response = $this->actingAs($user)->post('/kyc/review', []);
        $this->assertNotSame(route('verification.notice'), $response->headers->get('Location'));
    }

    public function test_staff_with_an_unconfirmed_email_keep_access(): void
    {
        $teller = User::factory()->unverified()->create();
        $teller->assignRole('teller');

        $this->assertTrue($teller->hasVerifiedEmail());
        $this->actingAs($teller)->get('/dashboard')->assertOk();
    }

    public function test_the_verification_email_is_sent_on_request(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $user->assignRole('borrower');

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
