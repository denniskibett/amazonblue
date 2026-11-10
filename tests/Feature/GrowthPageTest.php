<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Growth\AdCenter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Growth page (10 Nov 2026): this site's numbers from the Sharet growth hub, read with the saved Sharet key. */
class GrowthPageTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
        config(['sharet.api_key' => 'sk_test_growth', 'sharet.base_url' => 'https://sharet.test/api/v1']);
        Cache::flush();
    }

    private function site(array $over = []): array
    {
        return array_replace_recursive([
            'key' => 'loans', 'name' => 'Sharet Loans', 'url' => 'https://loans.sharet.africa', 'last_data_at' => '2026-11-10T08:00:00Z',
            'totals' => ['visits' => 640, 'page_views' => 1500, 'conversions' => 32, 'conversion_rate' => 5.0, 'organic_share' => 55, 'paid_share' => 20],
            'daily' => [['date' => '2026-11-09', 'page_views' => 50, 'visits' => 21, 'conversions' => 1], ['date' => '2026-11-10', 'page_views' => 60, 'visits' => 25, 'conversions' => 2]],
            'conversions' => [['name' => 'loan_application', 'label' => 'Loan application', 'count' => 12], ['name' => 'signup', 'label' => 'Sign-up', 'count' => 20]],
            'channels' => [['channel' => 'organic', 'label' => 'Organic search', 'visits' => 352, 'share' => 55.0, 'conversions' => 20, 'conversion_rate' => 5.68]],
            'sources' => [['source' => 'facebook', 'medium' => 'paid-social', 'campaign' => 'boda-logbook', 'visits' => 90, 'conversions' => 9, 'conversion_rate' => 10.0]],
            'landing_pages' => [
                ['path' => '/boda-boda-loans', 'visits' => 200, 'conversions' => 18, 'conversion_rate' => 9.0],
                ['path' => '/commodities', 'visits' => 45, 'conversions' => 0, 'conversion_rate' => 0.0],
            ],
            'seo' => ['checked_at' => '2026-11-10T02:30:00Z', 'sitemap_urls' => 16, 'counts' => ['red' => 1, 'amber' => 2, 'green' => 13],
                'fix_first' => [['level' => 'red', 'url' => 'https://loans.sharet.africa/careers', 'text' => 'Title is longer than 65 characters.']]],
        ], $over);
    }

    private function fakeOk(array $site): void
    {
        Http::fake(['sharet.test/*' => Http::response(['object' => 'growth.summary', 'version' => 1, 'from' => '2026-10-12', 'to' => '2026-11-10', 'sites' => [$site]])]);
    }

    public function test_connected_shows_numbers_seo_and_what_to_push(): void
    {
        $this->fakeOk($this->site());

        $this->actingAs($this->manager)->get(route('staff.growth', ['days' => 90]))->assertOk()
            ->assertSee('Growth')->assertSee('640')->assertSee('Loan application')->assertSee('Organic search')
            ->assertSee('boda-logbook')->assertSee('/boda-boda-loans')->assertSee('Title is longer than 65 characters.')
            ->assertSee('Send ads to /boda-boda-loans')
            ->assertSee('/commodities gets traffic but no conversions')
            ->assertDontSee('Not enough data yet');

        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer sk_test_growth')
            && str_starts_with($r->url(), 'https://sharet.test/api/v1/growth/summary')
            && $r['from'] === today('Africa/Nairobi')->subDays(89)->toDateString());
    }

    public function test_answers_are_cached_for_five_minutes(): void
    {
        $this->fakeOk($this->site());
        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk();
        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_little_data_says_not_enough_data(): void
    {
        $this->fakeOk(['landing_pages' => [], 'sources' => [], 'daily' => [], 'conversions' => [], 'channels' => []]
            + $this->site(['totals' => ['visits' => 40, 'conversions' => 2]]));

        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk()
            ->assertSee('Not enough data yet')->assertSee('No conversions in this period.');
    }

    public function test_empty_site_list_is_explained(): void
    {
        Http::fake(['sharet.test/*' => Http::response(['object' => 'growth.summary', 'sites' => []])]);
        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk()->assertSee('has no Growth site for this key yet');
    }

    public function test_not_connected_without_a_key(): void
    {
        config(['sharet.api_key' => null]);
        Http::fake();
        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk()->assertSee('Not connected')->assertSee('Ready ad copy');
        Http::assertNothingSent();
    }

    public function test_error_answers_become_friendly_states(): void
    {
        $status = 0;
        Http::fake(function () use (&$status) {
            return Http::response(['type' => 'about:blank', 'title' => 'Problem', 'detail' => 'growth_no_site'], $status, ['Content-Type' => 'application/problem+json']);
        });
        foreach ([401 => 'Sharet did not accept the key', 403 => 'This key cannot read Growth', 503 => 'Growth is resting', 500 => 'Could not load Growth'] as $status => $text) {
            Cache::flush();
            $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk()->assertSee($text);
        }
    }

    public function test_a_timeout_is_not_a_500(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out'));
        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk()->assertSee('Sharet took too long');
    }

    public function test_only_staff_who_see_reports_get_in(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.growth'))->assertForbidden();

        $borrower = User::factory()->create();
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->get(route('staff.growth'))->assertRedirect(); // customers are sent to /account

        $this->get(route('staff.growth'))->assertRedirect(); // guests sign in first
    }

    public function test_link_builder_tags_only_our_own_pages(): void
    {
        $this->fakeOk($this->site());
        $page = route('site.page', 'boda-boda-loans');
        $this->actingAs($this->manager)->get(route('staff.growth', ['page' => $page, 'source' => 'Facebook', 'medium' => 'paid social', 'campaign' => 'Boda Nov']))
            ->assertOk()->assertSee($page . '?utm_source=facebook&amp;utm_medium=paid-social&amp;utm_campaign=boda-nov', false);

        $this->actingAs($this->manager)->get(route('staff.growth', ['page' => 'https://evil.example', 'source' => 'x', 'medium' => 'y', 'campaign' => 'z']))
            ->assertOk()->assertSee('Choose one of our pages');
    }

    public function test_ad_copy_is_truthful_and_fits_google_limits(): void
    {
        $ads = app(AdCenter::class);
        $copy = $ads->copy();
        foreach ($copy['google']['parts']['Headlines'] as $h) {
            $this->assertLessThanOrEqual(30, mb_strlen($h), $h);
        }
        foreach ($copy['google']['parts']['Descriptions'] as $d) {
            $this->assertLessThanOrEqual(90, mb_strlen($d), $d);
        }
        $all = strtolower(json_encode($copy));
        // bike purchase finance is not open: no ad may offer it, and no rate is quoted for the cash loan
        foreach (['buy a bike', 'buy a new', 'own a new bike', 'hire purchase', 'deposit', 'guaranteed', 'no crb', '% interest', '20%', 'per day'] as $claim) {
            $this->assertStringNotContainsString($claim, str_replace(['"hire purchase"', '"bike deposit"'], '', $all), $claim);
        }
        // every ad link lands on our own site with utm tags
        foreach ($copy as $ad) {
            $link = $ads->link($ad['landing'], ...$ad['utm']);
            $this->assertNotNull($link);
            $this->assertStringStartsWith(url('/'), $link);
            $this->assertStringContainsString('utm_source=', $link);
        }
    }

    public function test_growth_is_in_the_staff_menu(): void
    {
        $this->fakeOk($this->site());
        $this->actingAs($this->manager)->get(route('staff.growth'))->assertOk()->assertSee(route('staff.growth'), false);
    }
}
