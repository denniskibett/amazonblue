<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Integration;
use App\Models\User;
use App\Support\Integrations;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Integrations page, API keys and the header search (owner, 3 Nov 2026). */
class IntegrationsApiKeysTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->super = User::factory()->create();
        $this->super->assignRole('super-admin');
        Integrations::forget();
    }

    public function test_import_stores_env_values_encrypted_and_saved_values_override_config(): void
    {
        config(['lending.notifications.sms.kenyasms.sender_id' => 'FROMENV']);
        $this->artisan('integrations:import-env')->assertSuccessful();
        $raw = DB::table('integrations')->where('slug', 'kenyasms')->value('settings');
        $this->assertStringNotContainsString('FROMENV', (string) $raw);
        $this->assertSame('FROMENV', Integration::where('slug', 'kenyasms')->first()->settings['KENYASMS_SENDER_ID']);

        $this->actingAs($this->super)->post(route('staff.integrations.update', 'kenyasms'), ['fields' => ['KENYASMS_SENDER_ID' => 'AIMZONE', 'KENYASMS_API_KEY' => '']])
            ->assertSessionHas('success');
        Integrations::apply();
        $this->assertSame('AIMZONE', config('lending.notifications.sms.kenyasms.sender_id'));
        $this->assertDatabaseHas('audit_logs', ['table_name' => 'integrations']);
    }

    public function test_secrets_are_never_sent_to_the_browser_and_a_blank_secret_keeps_the_saved_one(): void
    {
        Integration::create(['slug' => 'mail', 'name' => 'Email', 'category' => 'Email', 'enabled' => true, 'settings' => ['MAIL_PASSWORD' => 'hunter2-very-secret']]);
        $this->actingAs($this->super)->get(route('staff.integrations'))->assertOk()->assertDontSee('hunter2-very-secret')->assertSee('I&amp;M Bank account', false);

        $this->post(route('staff.integrations.update', 'mail'), ['fields' => ['MAIL_PASSWORD' => '', 'MAIL_HOST' => 'smtp.example.com']]);
        $this->assertSame('hunter2-very-secret', Integration::where('slug', 'mail')->first()->settings['MAIL_PASSWORD']);
    }

    public function test_the_bank_account_is_read_only_here(): void
    {
        $this->actingAs($this->super)->post(route('staff.integrations.update', 'imbank'), ['fields' => []])->assertForbidden();
    }

    public function test_switching_mpesa_off_turns_payments_off(): void
    {
        config(['lending.mpesa.enabled' => true]);
        $this->actingAs($this->super)->post(route('staff.integrations.toggle', 'mpesa'));
        Integrations::apply();
        $this->assertFalse(config('lending.mpesa.enabled'));
    }

    public function test_an_api_key_works_once_made_and_stops_when_switched_off(): void
    {
        $this->getJson('/api/v1/ping')->assertUnauthorized();
        $this->actingAs($this->super)->post(route('staff.api-keys.store'), ['name' => 'Accounting sync'])->assertSessionHas('new_key');
        $plain = session('new_key')['value'];
        $key = ApiKey::firstOrFail();
        $this->assertNotSame($plain, $key->token_hash);

        $this->withToken($plain)->getJson('/api/v1/ping')->assertOk()->assertJson(['ok' => true, 'key' => 'Accounting sync']);
        $this->post(route('staff.api-keys.toggle', $key));
        $this->withToken($plain)->getJson('/api/v1/ping')->assertUnauthorized();
    }

    public function test_search_finds_a_loan_by_number_and_only_for_staff_who_may_see_it(): void
    {
        $borrower = User::factory()->create(['name' => 'Wanjiku Search']);
        $this->actingAs($this->super)->postJson(route('staff.search'), ['q' => 'Wanjiku'])->assertOk()
            ->assertJsonFragment(['label' => 'Wanjiku Search']);

        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller)->postJson(route('staff.search'), ['q' => 'Wanjiku'])->assertOk()->assertJsonMissing(['label' => 'Wanjiku Search']);
        $this->assertNotNull($borrower);
    }

    public function test_pages_need_their_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('staff.integrations'))->assertForbidden();
        $this->actingAs($admin)->get(route('staff.api-keys'))->assertForbidden();
    }
}
