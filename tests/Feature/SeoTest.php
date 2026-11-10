<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO pass (10 Nov 2026): bike loan landing pages, structured data, sitemap, robots, noindex on private pages,
 * and the Sharet analytics conversions.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private const LANDING = ['boda-boda-loans', 'electric-bike-loans', 'motorbike-loans-kenya', 'bike-loan-requirements'];

    /** @return array<int, array> every JSON-LD block on the page, decoded */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(function ($json) {
            $data = json_decode($json, true);
            $this->assertIsArray($data, 'JSON-LD does not parse: ' . $json);
            $this->assertSame('https://schema.org', $data['@context']);

            return $data;
        }, $m[1]);
    }

    private function types(string $html): array
    {
        return array_column($this->jsonLd($html), '@type');
    }

    public function test_landing_pages_are_indexable_with_their_own_title_canonical_h1_and_structured_data(): void
    {
        $titles = [];
        foreach (self::LANDING as $slug) {
            $response = $this->get('/' . $slug)->assertOk();
            $html = $response->getContent();

            $this->assertFalse($response->headers->has('X-Robots-Tag'), $slug);
            $this->assertStringNotContainsString('noindex', $html, $slug);
            $this->assertStringContainsString('<link rel="canonical" href="' . url('/' . $slug) . '" />', $html);
            $this->assertSame(1, substr_count($html, '<h1'), $slug . ' has one h1');
            preg_match('#<title>(.*?)</title>#', $html, $t);
            $titles[] = $t[1];

            $types = $this->types($html);
            foreach (['Organization', 'FAQPage', 'BreadcrumbList'] as $type) {
                $this->assertContains($type, $types, "{$slug} has {$type}");
            }
            // the FAQ shown on the page is the FAQ in the structured data
            $faq = collect($this->jsonLd($html))->firstWhere('@type', 'FAQPage');
            foreach ($faq['mainEntity'] as $q) {
                $this->assertStringContainsString(e($q['name']), $html);
            }
        }
        $this->assertCount(count(self::LANDING), array_unique($titles), 'every landing page has its own title');
    }

    public function test_bike_pages_quote_only_published_terms(): void
    {
        $html = $this->get('/boda-boda-loans')->assertOk()->getContent();
        $this->assertStringContainsString('Boda boda loans in Kenya', $html);
        $loan = collect($this->jsonLd($html))->firstWhere('@type', 'LoanOrCredit');
        $this->assertSame(3, $loan['loanTerm']['maxValue']);
        $this->assertSame('MON', $loan['loanTerm']['unitCode']);
        $this->assertArrayNotHasKey('interestRate', $loan);
        $this->assertArrayNotHasKey('annualPercentageRate', $loan);

        // the asset finance page these terms come from still says them
        $this->get('/asset-finance')->assertOk()->assertSee('up to 70%', false)->assertSee('Up to 3 months', false);
    }

    public function test_organization_names_sharet_africa_as_parent(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $org = collect($this->jsonLd($html))->firstWhere('@type', 'Organization');
        $this->assertSame('Sharet Africa Ltd', $org['parentOrganization']['name']);
        $this->assertSame('https://sharet.africa', $org['parentOrganization']['url']);
        $this->assertContains('FinancialService', $this->types($html));
        $this->assertStringContainsString('name="twitter:image"', $html);
        $this->assertStringContainsString('href="' . url('/boda-boda-loans') . '"', $html);
    }

    public function test_footer_links_the_sister_sites_but_not_itself(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        foreach (['https://sharet.africa', 'https://breve.sharet.africa', 'https://aiyotickets.com', 'https://eserviz.aiyotickets.com', 'https://scharge.sharet.africa', 'https://shoope.africa'] as $site) {
            $this->assertStringContainsString('href="' . $site . '"', $html);
        }
        $this->assertStringNotContainsString('href="https://loans.sharet.africa"', $html);
    }

    public function test_sitemap_lists_public_pages_with_lastmod_and_nothing_private(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);

        $locs = [];
        foreach ($xml->url as $url) {
            $locs[] = (string) $url->loc;
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', (string) $url->lastmod);
        }
        foreach (self::LANDING as $slug) {
            $this->assertContains(url('/' . $slug), $locs);
        }
        foreach ($locs as $loc) {
            $this->assertDoesNotMatchRegularExpression('#/(account|kyc|login|register|dashboard|staff)\b#', $loc);
            $this->get(parse_url($loc, PHP_URL_PATH) ?: '/')->assertOk();
        }
    }

    public function test_robots_lists_private_areas_and_the_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        foreach (['/dashboard', '/account', '/kyc', '/staff', '/collect', '/preview', '/api', '/loans/', '/users'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $body);
        }
        $this->assertStringContainsString('Sitemap: ' . url('/sitemap.xml'), $body);
        // tests run outside production: the whole site is closed to crawlers
        $this->assertStringContainsString("Disallow: /\n", $body);
    }

    public function test_private_pages_answer_noindex(): void
    {
        $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/register')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/account')->assertRedirect()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/no-such-page-here')->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->seed(RoleSeeder::class);
        $borrower = User::factory()->create();
        $borrower->assignRole('borrower');
        $this->actingAs($borrower)->get('/kyc/personal')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_tracker_loads_only_in_production_on_public_pages(): void
    {
        config(['services.sharet_analytics.url' => 'https://sharet.africa']);
        $this->assertStringNotContainsString('sharet-analytics.js', $this->get('/')->getContent());

        $this->app['env'] = 'production';
        $html = $this->get('/boda-boda-loans')->getContent();
        $this->assertStringContainsString('<script defer src="https://sharet.africa/js/sharet-analytics.js" data-site="loans"></script>', $html);
        $this->assertStringNotContainsString('sharet-analytics.js', $this->get('/login')->getContent());
    }

    public function test_signup_reports_a_conversion_once(): void
    {
        $this->seed(RoleSeeder::class);
        $this->post('/register', [
            'account_type' => 'individual',
            'name' => 'Test Rider',
            'email' => 'rider@example.com',
            'phone' => '0712345678',
            'password' => 'Secret-pass-123',
            'terms' => '1',
        ])->assertRedirect(route('kyc.personal'))->assertSessionHas('sa_conversion', 'signup');

        $this->get(route('kyc.personal'))->assertOk()->assertSee("push(['conversion','signup'])", false);
        $this->get(route('kyc.personal'))->assertOk()->assertDontSee("push(['conversion'", false);
    }

    public function test_contact_enquiry_is_a_lead_and_bike_is_a_topic(): void
    {
        $this->get('/contact?topic=bike')->assertOk()->assertSee('value="bike" selected', false);

        $this->post('/contact', [
            'topic' => 'bike',
            'name' => 'Test Rider',
            'email' => 'rider@example.com',
            'phone' => '0712345678',
            'message' => 'I want to finance an electric motorbike.',
            'consent' => '1',
        ])->assertRedirect()->assertSessionHas('sa_conversion', 'lead');
        $this->assertDatabaseHas('enquiries', ['topic' => 'bike', 'route_to' => 'clients@aimzoneventures.com']);

        // the same enquiry again is not a new lead
        $this->post('/contact', [
            'topic' => 'bike', 'name' => 'Test Rider', 'email' => 'rider@example.com', 'phone' => '0712345678',
            'message' => 'Again', 'consent' => '1',
        ])->assertRedirect()->assertSessionMissing('sa_conversion');
    }

    public function test_a_loan_application_reports_a_conversion(): void
    {
        $this->seed(RoleSeeder::class);
        $typeId = \Illuminate\Support\Facades\DB::table('loan_types')->insertGetId([
            'name' => 'Emergency 10 Days', 'period' => 10, 'unit' => 'days', 'interest_rate' => 20, 'penalty_rate' => 10,
        ]);
        config(['lending.borrower_loan_type_id' => $typeId]);
        $user = User::factory()->create(['kyc_status' => 'approved']);
        $user->assignRole('borrower');
        \App\Models\Borrower::create(['user_id' => $user->id, 'client_type' => 'individual', 'status' => 1]);

        $this->actingAs($user)->post('/account/borrow', ['amount' => 1000, 'consent' => 1])
            ->assertRedirect(route('account.home'))
            ->assertSessionHas('sa_conversion', 'loan_application');
    }
}
