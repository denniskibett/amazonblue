<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Models\User;
use App\Services\Sms\SmsSender;
use App\Support\Integrations;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SMS sender IDs (20 Nov 2026): Sharet Ent (SHARETENT) is the platform default and always sends one-time codes;
 * the lender's own sender ID is used only for the lender's own messages. KenyaSMS is faked: never a real send.
 */
class SmsSenderIdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['sms.example.test/*' => Http::response(['status' => 'success', 'message_id' => 'm1'])]);
        config(['lending.notifications.sms.driver' => 'kenyasms',
            'lending.notifications.sms.kenyasms.url' => 'https://sms.example.test/send',
            'lending.notifications.sms.kenyasms.api_key' => 'lender-key',
            'lending.notifications.sms.kenyasms.sender_id' => null,
            'lending.notifications.sms.platform.sender_id' => 'SHARETENT',
            'lending.notifications.sms.platform.api_key' => 'platform-key']);
    }

    private function sentAs(): array
    {
        $r = Http::recorded()->last()[0];

        return [$r->data()['sender_id'] ?? null, $r->header('Authorization')[0] ?? null];
    }

    public function test_without_its_own_sender_the_lender_sends_as_sharetent(): void
    {
        $this->assertTrue(app(SmsSender::class)->send('0712345678', 'Your payment is due tomorrow.'));
        $this->assertSame(['SHARETENT', 'Bearer platform-key'], $this->sentAs());
    }

    public function test_the_lenders_own_sender_id_is_used_for_its_messages_but_never_for_codes(): void
    {
        config(['lending.notifications.sms.kenyasms.sender_id' => 'AIMZONE']);
        app(SmsSender::class)->send('0712345678', 'Your payment is due tomorrow.');
        $this->assertSame(['AIMZONE', 'Bearer lender-key'], $this->sentAs());

        app(SmsSender::class)->otp('0712345678', 'Your code is 482913');
        $this->assertSame(['SHARETENT', 'Bearer platform-key'], $this->sentAs());
        $this->assertSame('SHARETENT', SmsSender::sender('otp')['sender_id']);
        $this->assertTrue(SmsSender::sender('otp')['platform']);
    }

    public function test_the_sysadmins_platform_setting_wins_over_the_env_and_only_a_sysadmin_can_change_it(): void
    {
        Integration::create(['slug' => 'sms_platform', 'name' => 'Sharet Ent SMS (platform)', 'category' => 'SMS', 'enabled' => true,
            'settings' => ['SMS_PLATFORM_SENDER_ID' => 'SHARETENT2']]);
        Cache::flush();
        Integrations::apply();
        $this->assertSame('SHARETENT2', SmsSender::sender('otp')['sender_id']);

        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('staff.integrations.update', 'sms_platform'), ['fields' => ['SMS_PLATFORM_SENDER_ID' => 'HIJACK']])->assertForbidden();
        $this->actingAs($admin)->get(route('staff.integrations'))->assertOk()->assertDontSee('Sharet Ent SMS (platform)');
        $sys = User::factory()->create();
        $sys->assignRole('super-admin');
        $this->actingAs($sys)->get(route('staff.integrations'))->assertOk()->assertSee('Sharet Ent SMS (platform)');
    }

    public function test_a_blocked_gateway_fails_quietly(): void
    {
        config(['lending.notifications.sms.kenyasms.url' => 'https://blocked.example.test/send']);
        Http::fake(['blocked.example.test/*' => Http::response('Attention Required! | Cloudflare', 403)]);
        $this->assertFalse(app(SmsSender::class)->otp('0712345678', 'Your code is 111111'));
    }
}
