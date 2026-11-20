<?php

namespace Tests\Feature;

use App\Mail\FundingOutreachEmail;
use App\Models\FinanceApplication;
use App\Models\Funding\FundingCampaign;
use App\Models\Funding\FundingEmail;
use App\Models\Funding\FundingProspect;
use App\Models\Funding\Suppression;
use App\Models\User;
use App\Services\Funding\EmailEngagement;
use App\Services\Funding\Outreach;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Funding CRM (20 Nov 2026): business prospects, cold email from sales@sharet.africa with AIM- references, open and
 * click tracking, opt-out and suppression, campaigns, import from lawful sources, and the link into the application.
 */
class FundingCrmTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(RoleSeeder::class);
        $this->officer = User::factory()->create(['name' => 'Peter Langat']);
        $this->officer->assignRole('loan-officer');
    }

    private function prospect(array $over = []): FundingProspect
    {
        return FundingProspect::create($over + ['name' => 'Wambui Hardware Ltd', 'segment' => 'distributor', 'email' => 'Finance@WambuiHardware.co.ke',
            'town' => 'Thika', 'source' => 'company_website', 'source_url' => 'https://wambuihardware.co.ke/contact']);
    }

    public function test_staff_add_a_business_prospect_with_its_source(): void
    {
        $this->actingAs($this->officer)->post(route('staff.funding.store'), ['name' => 'Rift Logistics Ltd', 'segment' => 'logistics', 'source' => 'tender_award',
            'email' => 'ops@riftlogistics.co.ke'])->assertRedirect();
        $p = FundingProspect::firstOrFail();
        $this->assertSame('AIM-P-00001', $p->reference);
        $this->assertSame('asset', $p->need);
        $this->assertSame($this->officer->id, $p->owner_id);
        $this->actingAs($this->officer)->post(route('staff.funding.store'), ['name' => 'X', 'segment' => 'logistics', 'source' => 'bought_list'])->assertSessionHasErrors('source');
        $this->actingAs($this->officer)->post(route('staff.funding.store'), ['name' => 'Jane Doe', 'segment' => 'individual', 'source' => 'referral'])->assertSessionHasErrors('segment');
        $this->actingAs($this->officer)->get(route('staff.funding.index'))->assertOk()->assertSee('Rift Logistics Ltd')->assertSee('AIM-P-00001');
        $this->actingAs($this->officer)->get(route('staff.funding.show', $p))->assertOk()->assertSee('Another truck for Rift Logistics Ltd?');
    }

    public function test_a_cold_email_goes_from_sales_with_an_aim_reference_and_books_a_follow_up(): void
    {
        $p = $this->prospect();
        $this->actingAs($this->officer)->post(route('staff.funding.email', $p), ['subject' => 'Float for {company}', 'body' => "Hello,\n\nI'm {manager}."])->assertSessionHasNoErrors();
        $email = FundingEmail::firstOrFail();
        $this->assertSame('AIM-E-000001', $email->reference);
        $this->assertSame('finance@wambuihardware.co.ke', $email->to);
        $this->assertStringContainsString("I'm Peter Langat.", $email->body);
        Mail::assertSent(FundingOutreachEmail::class, function (FundingOutreachEmail $m) {
            $env = $m->envelope();
            $headers = $m->headers()->text;

            return $env->from->address === 'sales@sharet.africa' && $env->subject === 'Float for Wambui Hardware Ltd [AIM-E-000001]'
                && str_contains($headers['List-Unsubscribe'], route('funding.optout', $m->email->track)) && $headers['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click';
        });
        $p->refresh();
        $this->assertSame('contacted', $p->stage);
        $this->assertNotNull($p->last_emailed_at);
        $this->assertSame(1, $p->activities()->where('type', 'follow_up')->whereNull('done_at')->count());

        $html = (new FundingOutreachEmail($email, $this->officer))->render();
        $this->assertStringContainsString(route('funding.open', $email->track), $html);
        $this->assertStringContainsString(route('funding.optout', $email->track), $html);
        $this->assertStringContainsString('nothing here is an offer', $html);
    }

    public function test_opens_and_clicks_record_time_place_and_device_and_a_click_leads_to_the_application(): void
    {
        $p = $this->prospect();
        $email = app(Outreach::class)->send($p, $this->officer, 'Float', 'Hello');

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 14; SM-A546E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Mobile Safari/537.36', 'CF-IPCountry' => 'KE'])
            ->get(route('funding.open', $email->track))->assertOk()->assertHeader('Content-Type', 'image/gif');
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36 Edg/124.0'])
            ->get(route('funding.click', $email->track))->assertRedirect(route('finance.choose', ['p' => $email->track]));
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0 (via ggpht.com GoogleImageProxy)'])->get(route('funding.open', $email->track));

        $events = DB::table('funding_email_events')->orderBy('id')->get();
        $this->assertCount(3, $events);
        $this->assertSame(['phone', 'Android', 'Chrome', 'KE'], [$events[0]->device, $events[0]->os, $events[0]->client, $events[0]->country]);
        $this->assertSame(['computer', 'Windows', 'Edge'], [$events[1]->device, $events[1]->os, $events[1]->client]);
        $this->assertSame('gmail', $events[2]->via);
        $this->assertSame('127.0.0.0', $events[0]->ip_prefix, 'only a shortened address is kept');
        $email->refresh();
        $this->assertSame(2, $email->opens);
        $this->assertNotNull($email->clicked_at);
        $this->assertSame('interested', $p->fresh()->stage);
        $s = EmailEngagement::summary($p->id);
        $this->assertSame([2, 1], [$s['opens'], $s['clicks']]);
        $this->assertStringContainsString('Android phone', $s['device']);

        // starting an application from that link ties it to the prospect
        $this->post(route('finance.store', 'trade'), ['p' => $email->track, 'contact_name' => 'Jane Wambui', 'email' => 'jane@wambuihardware.co.ke',
            'phone' => '0722000000', 'legal_name' => 'Wambui Hardware Ltd', 'contact_ok' => 1])->assertSessionHasNoErrors();
        $app = FinanceApplication::firstOrFail();
        $this->assertSame($p->id, $app->prospect_id);
        $this->assertSame('applied', $p->fresh()->stage);
        $this->assertSame($app->id, $p->fresh()->application_id);

        // a bad token records nothing
        $this->get(route('funding.open', 'nope'))->assertOk();
        $this->assertSame(3, DB::table('funding_email_events')->count());
    }

    public function test_opting_out_suppresses_the_address_for_good(): void
    {
        $p = $this->prospect();
        $email = app(Outreach::class)->send($p, $this->officer, 'Float', 'Hello');
        $this->get(route('funding.optout', $email->track))->assertOk()->assertSee('Stop these emails?');
        // the mail app's one-click POST (RFC 8058), no CSRF token
        $this->post(route('funding.optout.store', $email->track), ['List-Unsubscribe' => 'One-Click'])->assertNoContent();
        $this->assertTrue(Suppression::has('FINANCE@wambuihardware.co.ke'));
        $this->assertNotNull($p->fresh()->opted_out_at);

        $this->actingAs($this->officer)->post(route('staff.funding.email', $p), ['subject' => 'Again', 'body' => 'Hello'])->assertSessionHasErrors('email');
        $this->assertSame(1, FundingEmail::count());
        // a new prospect with that address is born opted out
        $this->actingAs($this->officer)->post(route('staff.funding.store'), ['name' => 'Wambui Hardware (Ruiru)', 'segment' => 'distributor', 'source' => 'business_directory', 'email' => 'other@wambuihardware.co.ke']);
        $this->actingAs($this->officer)->post(route('staff.funding.suppressions.store'), ['email' => 'other@wambuihardware.co.ke', 'reason' => 'manual']);
        $this->assertNotNull(FundingProspect::where('email', 'other@wambuihardware.co.ke')->first()->opted_out_at);
    }

    public function test_a_campaign_emails_only_eligible_prospects_in_batches(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $ok1 = $this->prospect(['name' => 'A Ltd', 'email' => 'a@a.co.ke']);
        $ok2 = $this->prospect(['name' => 'B Ltd', 'email' => 'b@b.co.ke']);
        $this->prospect(['name' => 'No Email Ltd', 'email' => null]);
        $this->prospect(['name' => 'Opted Ltd', 'email' => 'c@c.co.ke']);
        Suppression::add('c@c.co.ke', 'opt_out');
        $this->prospect(['name' => 'Fuel Ltd', 'email' => 'd@d.co.ke', 'segment' => 'fuel']);
        $this->prospect(['name' => 'Lost Ltd', 'email' => 'e@e.co.ke', 'stage' => 'lost']);

        $this->actingAs($manager)->post(route('staff.funding.campaigns.store'), ['name' => 'Distributors Dec', 'segment' => 'distributor', 'subject' => 'Float for {company}', 'body' => 'Hello {company}']);
        $c = FundingCampaign::firstOrFail();
        $this->assertSame('AIM-C-001', $c->reference);
        $this->assertSame(2, Outreach::eligible('distributor')->count());

        $this->actingAs($this->officer)->post(route('staff.funding.campaigns.send', $c))->assertForbidden();
        config(['funding_crm.batch_limit' => 1]);
        $this->actingAs($manager)->post(route('staff.funding.campaigns.send', $c))->assertSessionHasNoErrors();
        $this->assertSame(1, FundingEmail::count());
        $this->actingAs($manager)->post(route('staff.funding.campaigns.send', $c));
        $this->assertEqualsCanonicalizing([$ok1->id, $ok2->id], FundingEmail::pluck('prospect_id')->all());
        $this->assertSame('done', $c->fresh()->status);
        $this->assertSame(2, $c->fresh()->sent_count);
        // a third run sends nothing more: each prospect is emailed once
        $this->actingAs($manager)->post(route('staff.funding.campaigns.send', $c));
        $this->assertSame(2, FundingEmail::count());
        $this->actingAs($manager)->get(route('staff.funding.campaigns'))->assertOk()->assertSee('AIM-C-001');
    }

    public function test_import_accepts_only_lawful_sources_and_skips_duplicates(): void
    {
        $this->prospect();
        $csv = "name,segment,source,email,town\n"
            . "Kisumu Fresh Fish Co,agribusiness,business_directory,info@kisumufish.co.ke,Kisumu\n"
            . "Bought Ltd,retail,purchased_list,x@bought.co.ke,Nairobi\n"
            . "Wambui Hardware Ltd,distributor,company_website,finance@wambuihardware.co.ke,Thika\n"
            . "Someone,individual,referral,,Nairobi\n";
        $file = UploadedFile::fake()->createWithContent('prospects.csv', $csv);
        $this->actingAs($this->officer)->post(route('staff.funding.import'), ['csv' => $file])->assertSessionHas('success', fn ($m) => str_starts_with($m, 'Added 1 prospects; skipped 3'));
        $this->assertSame(2, FundingProspect::count());
        $this->assertSame('working_capital', FundingProspect::where('name', 'Kisumu Fresh Fish Co')->value('need'));
    }

    public function test_only_permitted_staff_open_the_crm_and_officers_see_their_own(): void
    {
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.funding.index'))->assertForbidden();

        $other = User::factory()->create();
        $other->assignRole('loan-officer');
        $theirs = $this->prospect(['owner_id' => $other->id]);
        $this->actingAs($this->officer)->get(route('staff.funding.show', $theirs))->assertForbidden();
        $this->actingAs($this->officer)->get(route('staff.funding.index'))->assertDontSee('Wambui Hardware Ltd');
        $this->actingAs($other)->get(route('staff.funding.show', $theirs))->assertOk();
        foreach (['staff.funding.follow-ups', 'staff.funding.playbook', 'staff.funding.suppressions'] as $r) {
            $this->actingAs($other)->get(route($r))->assertOk();
        }
    }
}
