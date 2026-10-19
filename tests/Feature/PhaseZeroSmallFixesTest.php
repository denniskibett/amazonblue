<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\PaymentMethodType;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stage 4 Phase 0.10: small fixes (clear-cache is a POST, demo pages gone, robots, register throttle, PaymentMethod type).
 */
class PhaseZeroSmallFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_clear_cache_needs_a_post(): void
    {
        $this->seed(RoleSeeder::class);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)->get('/system/clear-cache')->assertStatus(405);
        $this->actingAs($super)->post('/system/clear-cache')->assertRedirect();
    }

    public function test_tailadmin_demo_pages_are_gone(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        foreach (['/calendar', '/basic-tables', '/form-elements', '/line-chart', '/buttons', '/signin'] as $path) {
            $this->actingAs($admin)->get($path)->assertNotFound();
        }
    }

    public function test_robots_keeps_private_areas_out(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        foreach (['/account', '/storage', '/files', '/kyc', '/system'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $body);
        }
    }

    public function test_registration_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', [])->assertStatus(302);
        }
        $this->post('/register', [])->assertStatus(429);
    }

    public function test_payment_method_type_relation_resolves(): void
    {
        $type = PaymentMethodType::create(['name' => 'Mobile money', 'slug' => 'mobile_money']);
        $method = new PaymentMethod(['account_name' => 'Jane', 'mobile_network' => 'Safaricom', 'mobile_number' => '0712345678']);
        $method->method_type_id = $type->id;

        $this->assertSame('mobile_money', $method->methodType->slug);
        $this->assertSame('Safaricom - 0712345678', $method->display_name);
    }
}
