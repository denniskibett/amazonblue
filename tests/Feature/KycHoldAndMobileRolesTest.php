<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\KycHold;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Owner, 21 Nov 2026: clients without full details are on hold until KYC is finished (borrowers and partners), with
 * what KYC asks for shown in their UI; and the mobile app checks roles on every call.
 */
class KycHoldAndMobileRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['mobile.enabled' => true]);
    }

    private function client(string $role, array $attrs = []): User
    {
        $u = User::factory()->create($attrs + ['password' => bcrypt('secret-pass'), 'email_verified_at' => now()]);
        $u->assignRole($role);

        return $u;
    }

    private function clearAll(): void
    {
        $this->partialMock(KycHold::class, fn ($m) => $m->shouldReceive('status')->andReturn([
            'state' => 'clear', 'sections' => [], 'done' => 4, 'total' => 4, 'next' => '/kyc/review', 'notes' => null, 'emailConfirmed' => true,
        ]));
    }

    public function test_a_new_borrower_sees_the_hold_and_what_kyc_asks_for(): void
    {
        $u = $this->client('borrower');

        $this->actingAs($u)->get('/account')->assertOk()
            ->assertSee('your account is on hold')
            ->assertSee('What we need from you')
            ->assertSee('KRA PIN')
            ->assertSee('Income &amp; payout', false)
            ->assertSee('Next of kin &amp; guarantor', false)
            ->assertSee('Agree to a credit reference bureau (CRB) check')
            ->assertSee('Start verification');
    }

    public function test_new_business_waits_but_reading_and_paying_stay_open(): void
    {
        $u = $this->client('borrower');

        $this->actingAs($u)->get('/account/borrow')->assertRedirect(route('account.home'));
        $this->actingAs($u)->post('/account/borrow', ['amount' => 5000])->assertRedirect(route('account.home'));
        $this->actingAs($u)->get('/account/loans')->assertOk();
        $this->actingAs($u)->get('/account/repay')->assertOk(); // paying is never behind the hold
        $this->actingAs($u)->get('/kyc')->assertOk()->assertSee('Here\'s what you\'ll need');
    }

    public function test_a_partner_is_held_on_identity_only_and_cannot_move_money(): void
    {
        $p = $this->client('partner');

        $this->actingAs($p)->get('/account')->assertOk()
            ->assertSee('your account is on hold')
            ->assertSee('add funds, invest or withdraw')
            ->assertSee('Identity documents')
            ->assertDontSee('Next of kin &amp; guarantor', false)
            ->assertDontSee('credit reference bureau');
        $this->actingAs($p)->get('/account/partner/withdraw')->assertRedirect(route('account.home'));
        $this->actingAs($p)->get('/account/partner/add-funds')->assertRedirect(route('account.home'));
    }

    public function test_submitted_and_rejected_say_where_things_stand(): void
    {
        $this->actingAs($this->client('borrower', ['kyc_status' => 'submitted', 'kyc_submitted_at' => now()]))
            ->get('/account')->assertSee('We\'re checking your details')->assertDontSee('Start verification');
        $this->actingAs($this->client('borrower', ['kyc_status' => 'rejected', 'kyc_review_notes' => 'The ID photo is blurred.']))
            ->get('/account')->assertSee('needs changes')->assertSee('The ID photo is blurred.')->assertSee('Fix my details');
    }

    public function test_approved_but_incomplete_is_still_held(): void
    {
        $u = $this->client('borrower', ['kyc_status' => 'approved']);

        $this->assertTrue(app(KycHold::class)->onHold($u));
        $this->actingAs($u)->get('/account')->assertSee('A few details are missing');
    }

    public function test_a_clear_client_is_not_held_and_staff_never_are(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $this->assertFalse(app(KycHold::class)->onHold($staff));

        $this->clearAll();
        $u = $this->client('borrower', ['kyc_status' => 'approved']);
        $this->actingAs($u)->get('/account')->assertOk()->assertDontSee('your account is on hold');
    }

    // ---------- mobile app: roles ----------

    private function api(): self
    {
        [, $key] = ApiKey::issue('Test APK', null, 'mobile');

        return $this->withHeaders(['X-Api-Key' => $key, 'X-Install-Id' => 'install-1']);
    }

    private function login(User $u): \Illuminate\Testing\TestResponse
    {
        $this->app['auth']->forgetGuards(); // a new account in the same test: the token guard must not remember the last one

        return $this->api()->postJson('/api/mobile/v1/auth/login', ['login' => $u->email, 'password' => 'secret-pass', 'install_id' => 'install-1']);
    }

    public function test_the_app_refuses_accounts_it_has_nothing_for(): void
    {
        $collector = $this->client('collector');
        $this->login($collector)->assertUnprocessable()->assertJsonPath('errors.login.0', 'This app is for Aimzone clients, partners and loan staff. Please use the website.');

        Role::findOrCreate('auditor', 'web')->revokePermissionTo(Permission::findOrCreate('view loans', 'web'));
        $this->login($this->client('auditor'))->assertUnprocessable();
    }

    public function test_a_borrower_reaches_only_borrowing_and_is_held(): void
    {
        $borrower = $this->client('borrower');
        $token = $this->login($borrower)->assertOk()->json('token');
        $this->api()->withToken($token)->getJson('/api/mobile/v1/me')->assertOk()
            ->assertJsonPath('user.can', ['loans', 'borrow', 'repay', 'statement'])
            ->assertJsonPath('kyc.on_hold', true)
            ->assertJsonPath('kyc.state', 'not_started');
        $this->api()->withToken($token)->getJson('/api/mobile/v1/loans')->assertOk();
        $this->api()->withToken($token)->getJson('/api/mobile/v1/partner')->assertForbidden();
        $this->api()->withToken($token)->getJson('/api/mobile/v1/desk')->assertForbidden();
        // on hold: applying is refused with what is missing
        $this->api()->withToken($token)->postJson('/api/mobile/v1/loans', ['amount' => 5000])->assertForbidden()->assertJsonPath('on_hold', true);
    }

    public function test_a_partner_reaches_only_the_portfolio(): void
    {
        $partner = $this->client('partner');
        $token = $this->login($partner)->assertOk()->json('token');
        $this->api()->withToken($token)->getJson('/api/mobile/v1/partner')->assertOk()->assertJsonPath('linked', false);
        $this->api()->withToken($token)->getJson('/api/mobile/v1/loans')->assertForbidden();
        $this->api()->withToken($token)->postJson('/api/mobile/v1/quotes', ['amount' => 5000])->assertForbidden();
    }

    public function test_loan_staff_reach_only_the_desk(): void
    {
        $officer = $this->client('loan-officer');
        $this->assertTrue($officer->can('view loans'));
        $token = $this->login($officer)->assertOk()->json('token');
        $this->api()->withToken($token)->getJson('/api/mobile/v1/desk')->assertOk()->assertJsonPath('queues.0.key', 'approve');
        $this->api()->withToken($token)->getJson('/api/mobile/v1/loans')->assertForbidden();
        $this->api()->withToken($token)->getJson('/api/mobile/v1/me')->assertOk()->assertJsonPath('kyc.on_hold', false);
    }
}
