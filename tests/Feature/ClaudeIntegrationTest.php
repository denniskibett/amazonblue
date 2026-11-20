<?php

namespace Tests\Feature;

use App\Http\Controllers\Staff\IntegrationTestController;
use App\Models\Business;
use App\Models\BusinessDocument;
use App\Models\BusinessMember;
use App\Models\Integration;
use App\Models\User;
use App\Services\Claude\Claude;
use App\Support\Integrations;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Claude (20 Nov 2026): the sysadmin's platform key card (encrypted, wins over .env), its real Test connection, and
 * Read with Claude on a business review. Anthropic and the SMS gateway are faked: no real call is ever made.
 */
class ClaudeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'api.anthropic.com/*';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seed(RoleSeeder::class);
        config(['services.anthropic.enabled' => false, 'services.anthropic.key' => null, 'services.anthropic.model' => 'claude-sonnet-5-5']);
    }

    /** A Messages API reply whose one text block is $json. */
    private function reply(array $json, string $stop = 'end_turn'): array
    {
        return ['id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-5-5',
            'content' => [['type' => 'text', 'text' => json_encode($json)]], 'stop_reason' => $stop, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 120, 'output_tokens' => 40]];
    }

    private function sysadmin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('super-admin');

        return $u;
    }

    private function claudeOn(string $key = 'sk-ant-saved-key'): void
    {
        Integration::create(['slug' => 'claude', 'name' => 'Claude (Anthropic)', 'category' => 'AI', 'enabled' => true,
            'settings' => ['ANTHROPIC_API_KEY' => $key, 'ANTHROPIC_MODEL' => 'claude-sonnet-5-5', 'CLAUDE_READ_DOCUMENTS' => '1']]);
        Cache::flush();
        Integrations::apply();
    }

    public function test_the_saved_key_is_encrypted_wins_over_env_and_only_a_sysadmin_sees_the_card(): void
    {
        config(['services.anthropic.key' => 'sk-ant-from-env']);
        $sys = $this->sysadmin();
        $this->actingAs($sys)->post(route('staff.integrations.update', 'claude'), ['fields' => ['ANTHROPIC_API_KEY' => 'sk-ant-card-key', 'ANTHROPIC_MODEL' => '']])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($sys)->post(route('staff.integrations.toggle', 'claude'))->assertRedirect();

        $raw = \DB::table('integrations')->where('slug', 'claude')->value('settings');
        $this->assertStringNotContainsString('sk-ant-card-key', $raw);
        Cache::flush();
        Integrations::apply();
        $this->assertSame('sk-ant-card-key', Claude::key());
        $this->assertSame('claude-sonnet-5-5', Claude::model());
        $this->assertTrue(Claude::enabled());

        $this->actingAs($sys)->post(route('staff.integrations.update', 'claude'), ['fields' => ['ANTHROPIC_API_KEY' => 'not-a-key']])
            ->assertSessionHasErrors('fields.ANTHROPIC_API_KEY');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('staff.integrations'))->assertOk()->assertDontSee('Claude (Anthropic)');
        $this->actingAs($admin)->post(route('staff.integrations.update', 'claude'), ['fields' => ['ANTHROPIC_API_KEY' => 'sk-ant-hijack']])->assertForbidden();
        $this->actingAs($admin)->post(route('staff.integrations.claude.test'))->assertForbidden();
        $this->actingAs($sys)->get(route('staff.integrations'))->assertOk()->assertSee('Claude (Anthropic)');
    }

    public function test_test_connection_calls_anthropic_with_the_saved_key_and_keeps_the_result(): void
    {
        $this->claudeOn();
        Http::fake([self::API => Http::response($this->reply(['ok' => true, 'note' => 'Claude is connected']))]);

        $this->actingAs($this->sysadmin())->post(route('staff.integrations.claude.test'))->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.anthropic.com/v1/messages'
            && $r->header('Authorization')[0] === 'Bearer sk-ant-saved-key'
            && $r->header('anthropic-beta')[0] === 'server-side-fallback-2026-07-01'
            && $r['model'] === 'claude-sonnet-5-5'
            && $r['output_config']['format']['type'] === 'json_schema'
            && $r['fallbacks'] === 'default');
        $this->assertTrue(Integration::where('slug', 'claude')->first()->settings[Integrations::TEST]['ok']);
    }

    public function test_a_refused_key_is_reported_not_thrown(): void
    {
        $this->claudeOn('sk-ant-bad');
        Http::fake([self::API => Http::response(['type' => 'error', 'error' => ['type' => 'authentication_error', 'message' => 'invalid x-api-key']], 401)]);

        $this->actingAs($this->sysadmin())->post(route('staff.integrations.claude.test'))->assertSessionHas('warning');
        $test = Integration::where('slug', 'claude')->first()->settings[Integrations::TEST];
        $this->assertFalse($test['ok']);
        $this->assertStringContainsString('authentication_error', $test['message']);
    }

    private function business(): array
    {
        $owner = User::factory()->create();
        $business = Business::create(['owner_user_id' => $owner->id, 'legal_name' => 'Mavuno Agro Supplies Limited', 'registration_number' => 'PVT-ABC123',
            'kra_pin' => 'P051234567X', 'status' => Business::STATUS_SUBMITTED]);
        BusinessMember::create(['business_id' => $business->id, 'full_name' => 'Jane Wanjiku Kamau', 'role' => 'director', 'id_number' => '30111222']);
        BusinessMember::create(['business_id' => $business->id, 'full_name' => 'Peter Otieno', 'role' => 'director', 'id_number' => '28999111']);
        Storage::disk('local')->put('business-documents/cr12.pdf', '%PDF-1.4 test');
        $doc = BusinessDocument::create(['business_id' => $business->id, 'type' => 'cr12', 'path' => 'business-documents/cr12.pdf',
            'original_name' => 'cr12.pdf', 'mime' => 'application/pdf', 'size' => 14]);

        return [$business, $doc];
    }

    public function test_read_with_claude_stores_the_fields_and_compares_them_with_the_application(): void
    {
        Storage::fake('local');
        $this->claudeOn();
        [$business, $doc] = $this->business();
        Http::fake([self::API => Http::response($this->reply([
            'company_name' => 'MAVUNO AGRO SUPPLIES LTD', 'registration_number' => 'PVT-ABC123', 'directors' => 'JANE WANJIKU KAMAU; JOHN DOE',
            'shareholders' => 'JANE WANJIKU KAMAU (1000)', 'date_of_issue' => '2026-10-02', 'legible' => true, 'looks_like' => 'CR12', 'notes' => '',
        ]))]);

        $this->actingAs($this->sysadmin())->get(route('business-reviews.show', $business))->assertOk()->assertSee('Read with Claude');
        $this->actingAs($this->sysadmin())->post(route('business-reviews.read', [$business, $doc]))->assertSessionHas('success');

        Http::assertSent(function (Request $r) {
            $block = $r['messages'][0]['content'][0];

            return $block['type'] === 'document' && $block['source']['media_type'] === 'application/pdf'
                && base64_decode($block['source']['data']) === '%PDF-1.4 test';
        });
        $doc->refresh();
        $this->assertSame('read', $doc->read_status);
        $this->assertSame('PVT-ABC123', $doc->extracted['registration_number']);
        $checks = collect($doc->extracted['_checks'])->mapWithKeys(fn ($c) => [$c[0] => $c[1]]);
        $this->assertTrue($checks['Name matches the business']);
        $this->assertTrue($checks['Registration number matches']);
        $this->assertTrue($checks['Director Jane Wanjiku Kamau is on the CR12']);
        $this->assertFalse($checks['Director Peter Otieno is on the CR12']);

        $this->actingAs($this->sysadmin())->get(route('business-reviews.show', $business))->assertOk()
            ->assertSee('Read by Claude')->assertSee('Director Peter Otieno is on the CR12');
    }

    public function test_nothing_is_sent_while_claude_is_off(): void
    {
        Storage::fake('local');
        [$business, $doc] = $this->business();
        Http::fake();

        $this->actingAs($this->sysadmin())->get(route('business-reviews.show', $business))->assertOk()->assertDontSee('Read with Claude');
        $this->actingAs($this->sysadmin())->post(route('business-reviews.read', [$business, $doc]))->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame('off', $doc->fresh()->read_status);
    }

    public function test_the_platform_test_sms_goes_only_to_the_owners_phone_as_sharetent(): void
    {
        Http::fake(['sms.example.test/*' => Http::response(['status' => 'success', 'message_id' => 'm1'])]);
        config(['lending.notifications.sms.driver' => 'kenyasms', 'lending.notifications.sms.kenyasms.url' => 'https://sms.example.test/send',
            'lending.notifications.sms.kenyasms.api_key' => 'lender-key', 'lending.notifications.sms.kenyasms.sender_id' => 'AIMZONE',
            'lending.notifications.sms.platform.sender_id' => 'SHARETENT', 'lending.notifications.sms.platform.api_key' => 'platform-key']);

        $this->actingAs($this->sysadmin())->post(route('staff.integrations.sms.test'))->assertSessionHas('success');

        Http::assertSentCount(1);
        $sent = Http::recorded()->first()[0];
        $this->assertSame('SHARETENT', $sent->data()['sender_id']);
        $this->assertStringEndsWith('717492048', (string) $sent->data()['recipient']);
        $this->assertSame('0717492048', IntegrationTestController::TEST_PHONE);
        $this->assertTrue(Integration::where('slug', 'sms_platform')->first()->settings[Integrations::TEST]['ok']);
    }
}
