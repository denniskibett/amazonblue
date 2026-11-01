<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackPageView;
use App\Models\User;
use Database\Seeders\LookupTablesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Site analytics (owner, 1 Nov 2026): page views are recorded first-party and the staff page reads them. */
class SiteAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = 'Mozilla/5.0 (Linux; Android 14; SM-A145F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LookupTablesSeeder::class);
        $this->seed(RoleSeeder::class);
        // views recorded by earlier tests can outlive their transaction (a schema change commits it in MySQL)
        DB::table('page_views')->delete();
    }

    public function test_a_guest_page_view_is_recorded_without_ip_or_raw_session(): void
    {
        $this->withHeaders(['User-Agent' => self::PHONE, 'Referer' => 'https://www.facebook.com/somepost'])
            ->get('/?utm_source=facebook&utm_campaign=nov')->assertOk();

        $row = DB::table('page_views')->first();
        $this->assertNotNull($row);
        $this->assertSame('/', $row->path);
        $this->assertSame('home', $row->route_name);
        $this->assertSame('visitor', $row->role);
        $this->assertNull($row->user_id);
        $this->assertSame('mobile', $row->device);
        $this->assertSame('facebook.com', $row->referrer_host);
        $this->assertSame('facebook', $row->utm_source);
        $this->assertSame('nov', $row->utm_campaign);
        $this->assertSame(64, strlen((string) $row->session_hash));
        $this->assertArrayNotHasKey('ip', (array) $row);
    }

    public function test_json_requests_bots_and_switched_off_are_not_recorded(): void
    {
        $this->getJson('/')->assertOk();
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->get('/')->assertOk();
        config(['lending.analytics.enabled' => false]);
        $this->withHeaders(['User-Agent' => self::PHONE])->get('/')->assertOk();

        $this->assertSame(0, DB::table('page_views')->count());
    }

    public function test_device_detection(): void
    {
        $this->assertSame('mobile', TrackPageView::device(self::PHONE));
        $this->assertSame('tablet', TrackPageView::device('Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)'));
        $this->assertSame('desktop', TrackPageView::device('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0'));
    }

    public function test_staff_with_view_reports_see_the_page_and_a_journey(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->assertTrue($manager->can('view reports'));
        $borrower = User::factory()->create(['name' => 'Wanjiru Journey']);
        $borrower->assignRole('borrower');
        foreach (['home' => '/', 'register' => '/register', 'kyc.personal' => '/kyc/personal'] as $route => $path) {
            DB::table('page_views')->insert(['occurred_at' => now(), 'user_id' => $borrower->id, 'role' => 'borrower',
                'session_hash' => hash('sha256', 'abc'), 'path' => $path, 'route_name' => $route, 'device' => 'mobile', 'response_ms' => 120]);
        }

        $this->actingAs($manager)->get(route('staff.site-analytics', ['user' => $borrower->id]))
            ->assertOk()
            ->assertSee('Site analytics')
            ->assertSee('How to improve')
            ->assertSee('From the home page to a loan')
            ->assertSee('Wanjiru Journey')
            ->assertSee('kyc.personal');
    }

    public function test_the_page_is_forbidden_without_view_reports(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->assertFalse($teller->can('view reports'));

        $this->actingAs($teller)->get(route('staff.site-analytics'))->assertForbidden();
    }

    public function test_prune_keeps_recent_views(): void
    {
        foreach ([now()->subDays(200), now()->subDays(10)] as $at) {
            DB::table('page_views')->insert(['occurred_at' => $at, 'role' => 'visitor', 'path' => '/', 'route_name' => 'home', 'device' => 'desktop']);
        }
        $this->artisan('analytics:prune-views', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(2, DB::table('page_views')->count());
        $this->artisan('analytics:prune-views')->assertSuccessful();
        $this->assertSame(1, DB::table('page_views')->count());
    }
}
