<?php

namespace Tests\Feature;

use App\Mail\FinanceApplicationMail;
use App\Models\FinanceApplication;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The business finance journey (20 Nov 2026): every step saved, resumed from the private link, validated, documents
 * limited by kind, size and count, then reviewed by staff.
 */
class FinanceJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
    }

    private function begin(string $product = 'asset'): array
    {
        $res = $this->post(route('finance.store', $product), ['contact_name' => 'Grace Chebet', 'email' => 'Grace@RiftAgro.co.ke', 'phone' => '0712 345678',
            'legal_name' => 'Rift Agro Traders Ltd', 'contact_ok' => 1]);
        $res->assertSessionHasNoErrors();
        $app = FinanceApplication::latest('id')->firstOrFail();
        $token = null;
        Mail::assertSent(FinanceApplicationMail::class, function ($m) use (&$token, $app) {
            if ($m->kind === 'link' && $m->fa->is($app)) {
                $token = basename($m->link);
            }

            return true;
        });
        $res->assertRedirect(route('finance.step', [$token, 'business']));

        return [$app, $token];
    }

    private function business(): array
    {
        return ['legal_name' => 'Rift Agro Traders Ltd', 'registration_number' => 'PVT-AB12CD3', 'kra_pin' => 'p051234567x', 'sector' => 'logistics',
            'county' => 'Nairobi', 'years_trading' => 6, 'annual_turnover' => 48000000, 'directors' => 2];
    }

    private function asset(): array
    {
        return ['category' => 'truck', 'description' => 'Isuzu FRR 2022', 'condition' => 'new', 'supplier' => 'Isuzu East Africa', 'price' => 6500000, 'deposit' => 1300000];
    }

    private function pdf(string $name = 'doc.pdf', int $kb = 200): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function allDocs(string $product = 'asset'): array
    {
        $docs = ['kra_pin' => [$this->pdf('pin.pdf')], 'cr12' => [$this->pdf('cr12.pdf')], 'statements' => [$this->pdf('jan.pdf'), $this->pdf('feb.pdf')], 'director_id' => [UploadedFile::fake()->image('id.jpg')]];

        return $docs + ($product === 'asset' ? ['quote' => [$this->pdf('quote.pdf')]] : ['deal' => [$this->pdf('lpo.pdf')]]);
    }

    public function test_an_asset_application_is_saved_step_by_step_and_resumed_from_the_link(): void
    {
        [$app, $token] = $this->begin();
        $this->assertStringStartsWith('AIM-AF-' . now()->format('y') . '-', $app->reference);
        $this->assertSame('grace@riftagro.co.ke', $app->email);
        $this->assertNotSame($token, $app->getRawOriginal('token_hash'), 'only the hash is stored');
        $this->assertSame(hash('sha256', $token), $app->getRawOriginal('token_hash'));

        $this->post(route('finance.save', [$token, 'business']), $this->business() + ['exit' => 1])->assertRedirect(route('finance.show', $token));
        $app->refresh();
        $this->assertTrue($app->stepDone('business'));
        $this->assertSame('P051234567X', $app->business['kra_pin']);

        // a new browser (no session) comes back with the link and finds everything saved
        $this->flushSession();
        $this->get(route('finance.show', $token))->assertOk()->assertSee('Rift Agro Traders Ltd')->assertSee('Carry on')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get(route('finance.step', [$token, 'business']))->assertOk()->assertSee('PVT-AB12CD3');

        $this->post(route('finance.save', [$token, 'need']), $this->asset())->assertRedirect(route('finance.step', [$token, 'terms']));
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 5200000, 'term_months' => 36])->assertRedirect(route('finance.step', [$token, 'documents']));
        $app->refresh();
        $this->assertEqualsWithDelta(5200000 * 0.035 / (1 - 1.035 ** -36), $app->estimate['instalment'], 0.01);
        $this->assertSame('monthly', $app->estimate['basis']);

        $this->post(route('finance.save', [$token, 'documents']), ['docs' => $this->allDocs()])->assertRedirect(route('finance.step', [$token, 'review']));
        $this->assertSame(6, $app->documents()->count());
        $this->get(route('finance.step', [$token, 'review']))->assertOk()->assertSee('Isuzu FRR 2022')->assertSee('not a commitment');

        $this->post(route('finance.save', [$token, 'review']), ['confirm_true' => 1, 'consent_crb' => 1, 'consent_data' => 1])->assertRedirect(route('finance.show', $token));
        $app->refresh();
        $this->assertSame('submitted', $app->status);
        $this->assertNotNull($app->consent_at);
        Mail::assertSent(FinanceApplicationMail::class, fn ($m) => $m->kind === 'submitted');

        // after submitting, the steps are read-only: the link shows the status
        $this->get(route('finance.step', [$token, 'need']))->assertRedirect(route('finance.show', $token));
        $this->post(route('finance.save', [$token, 'need']), $this->asset())->assertSessionHasErrors('form');
        $this->get(route('finance.show', $token))->assertOk()->assertSee('What happens now')->assertSee('Sharet Pay');
    }

    public function test_each_step_is_validated(): void
    {
        [$app, $token] = $this->begin();
        $this->post(route('finance.save', [$token, 'business']), ['kra_pin' => '12345'] + $this->business())->assertSessionHasErrors('kra_pin');
        $this->post(route('finance.save', [$token, 'business']), ['sector' => 'individual'] + $this->business())->assertSessionHasErrors('sector');
        $this->assertFalse($app->fresh()->stepDone('business'));

        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 500000, 'term_months' => 12])->assertSessionHasErrors('form');
        $this->post(route('finance.save', [$token, 'need']), ['deposit' => 7000000] + $this->asset())->assertSessionHasErrors('deposit');
        $this->post(route('finance.save', [$token, 'need']), ['category' => 'house'] + $this->asset())->assertSessionHasErrors('category');
        $this->post(route('finance.save', [$token, 'need']), $this->asset());
        // more than the price less the deposit, and a term we do not offer
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 6000000, 'term_months' => 36])->assertSessionHasErrors('amount');
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 5000000, 'term_months' => 7])->assertSessionHasErrors('term_months');

        // submitting before the steps and documents are done, or without consent
        $this->post(route('finance.save', [$token, 'review']), ['confirm_true' => 1, 'consent_crb' => 1, 'consent_data' => 1])->assertSessionHasErrors('form');
        $this->post(route('finance.save', [$token, 'review']), [])->assertSessionHasErrors(['confirm_true', 'consent_crb', 'consent_data']);
        $this->assertSame('draft', $app->fresh()->status);

        // the start form wants a Kenyan mobile and consent to be contacted
        $this->post(route('finance.store', 'asset'), ['contact_name' => 'A', 'email' => 'a@b.co.ke', 'phone' => '12345', 'legal_name' => 'X Ltd'])
            ->assertSessionHasErrors(['phone', 'contact_ok']);
    }

    public function test_changing_the_asset_asks_for_the_amount_again(): void
    {
        [$app, $token] = $this->begin();
        $this->post(route('finance.save', [$token, 'need']), $this->asset());
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 5000000, 'term_months' => 24]);
        $this->assertTrue($app->fresh()->stepDone('terms'));
        $this->post(route('finance.save', [$token, 'need']), ['price' => 3000000, 'deposit' => 500000] + $this->asset());
        $this->assertFalse($app->fresh()->stepDone('terms'));
    }

    public function test_document_uploads_are_limited_by_kind_size_and_count(): void
    {
        [$app, $token] = $this->begin();
        $maxKb = config('finance_journey.uploads.max_kb');

        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['cr12' => [$this->pdf('big.pdf', $maxKb + 1)]]])->assertSessionHasErrors('docs.cr12.0');
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['cr12' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]]])->assertSessionHasErrors('docs.cr12.0');
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['cr12' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')]]])->assertSessionHasErrors('docs.cr12.0');
        $this->assertSame(0, $app->documents()->count());

        // one KRA PIN certificate only; statements up to 6
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['kra_pin' => [$this->pdf('a.pdf'), $this->pdf('b.pdf')]]])->assertSessionHasErrors('docs.kra_pin');
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['statements' => array_map(fn ($i) => $this->pdf("m$i.pdf"), range(1, 6))]])->assertSessionHasNoErrors();
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['statements' => [$this->pdf('m7.pdf')]]])->assertSessionHasErrors('docs.statements');
        $this->assertSame(6, $app->documents()->count());

        // the total per application
        config(['finance_journey.uploads.max_files' => 7]);
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['cr12' => [$this->pdf()], 'kra_pin' => [$this->pdf()]]])->assertSessionHasErrors('docs');

        // the step is done only when every required document is in; removing one undoes it
        config(['finance_journey.uploads.max_files' => 20]);
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['kra_pin' => [$this->pdf()], 'cr12' => [$this->pdf()], 'director_id' => [$this->pdf()]]]);
        $this->assertFalse($app->fresh()->stepDone('documents'), 'the supplier quote is still missing');
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => ['quote' => [$this->pdf()]]]);
        $this->assertTrue($app->fresh()->stepDone('documents'));
        $doc = $app->documents()->where('type', 'quote')->first();
        Storage::disk('local')->assertExists($doc->path);
        $this->post(route('finance.documents.remove', [$token, $doc->id]))->assertRedirect();
        Storage::disk('local')->assertMissing($doc->path);
        $this->assertFalse($app->fresh()->stepDone('documents'));
    }

    public function test_a_wrong_or_lapsed_link_shows_nothing_and_a_new_link_replaces_the_old(): void
    {
        [$app, $token] = $this->begin();
        $this->get(route('finance.show', str_repeat('a', 48)))->assertStatus(410)->assertDontSee('Rift Agro');
        $this->get(route('finance.show', 'short'))->assertStatus(410);

        $app->forceFill(['last_seen_at' => now()->subDays(config('finance_journey.link_days') + 1)])->saveQuietly();
        $this->get(route('finance.show', $token))->assertStatus(410)->assertSee('new link');

        $this->post(route('finance.resume.send'), ['email' => 'grace@riftagro.co.ke'])->assertSessionHas('status');
        $fresh = null;
        Mail::assertSent(FinanceApplicationMail::class, function ($m) use (&$fresh) {
            if ($m->kind === 'link') {
                $fresh = basename($m->link);
            }

            return true;
        });
        $this->assertNotSame($token, $fresh);
        $this->get(route('finance.show', $fresh))->assertOk()->assertSee('Rift Agro Traders Ltd');
        $this->get(route('finance.show', $token))->assertStatus(410);
        // an unknown address gets the same answer and no email
        Mail::fake();
        $this->post(route('finance.resume.send'), ['email' => 'nobody@example.com'])->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_a_trade_application_estimates_one_repayment_at_the_end(): void
    {
        [$app, $token] = $this->begin('trade');
        $this->assertStringStartsWith('AIM-TF-', $app->reference);
        $this->post(route('finance.save', [$token, 'need']), ['kind' => 'lpo', 'supplier' => 'Simba Cement', 'supplier_country' => 'Kenya', 'goods' => 'Cement', 'currency' => 'KES', 'order_value' => 2400000])
            ->assertSessionHasErrors('buyer');
        $this->post(route('finance.save', [$token, 'need']), ['kind' => 'lpo', 'supplier' => 'Simba Cement', 'supplier_country' => 'Kenya', 'goods' => 'Cement', 'buyer' => 'Kiambu County',
            'currency' => 'KES', 'order_value' => 2400000])->assertSessionHasNoErrors();
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 2500000, 'term_months' => 2])->assertSessionHasErrors('amount');
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 1800000, 'term_months' => 2])->assertSessionHasNoErrors();
        $e = $app->fresh()->estimate;
        $this->assertSame('end', $e['basis']);
        $this->assertEquals(1800000 + 1800000 * 0.03 * 2, $e['total']);
    }

    public function test_staff_review_asks_for_more_then_approves_in_principle(): void
    {
        $this->seed(RoleSeeder::class);
        [$app, $token] = $this->begin();
        $this->post(route('finance.save', [$token, 'business']), $this->business());
        $this->post(route('finance.save', [$token, 'need']), $this->asset());
        $this->post(route('finance.save', [$token, 'terms']), ['amount' => 5200000, 'term_months' => 36]);
        $this->post(route('finance.save', [$token, 'documents']), ['docs' => $this->allDocs()]);
        $this->post(route('finance.save', [$token, 'review']), ['confirm_true' => 1, 'consent_crb' => 1, 'consent_data' => 1]);

        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.finance.index'))->assertForbidden();

        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $this->actingAs($manager)->get(route('staff.finance.index'))->assertOk()->assertSee($app->reference);
        $this->actingAs($manager)->get(route('staff.finance.show', $app))->assertOk()->assertSee('Isuzu FRR 2022')->assertSee('P051234567X');
        $doc = $app->documents()->first();
        $this->actingAs($manager)->get(route('staff.finance.document', [$app, $doc->id]))->assertOk();

        $this->actingAs($manager)->post(route('staff.finance.decide', $app), ['decision' => 'more_info'])->assertSessionHasErrors('decision');
        $this->actingAs($manager)->post(route('staff.finance.decide', $app), ['decision' => 'more_info', 'note' => 'Please add the logbook of your current truck.', 'back_to' => 'documents'])
            ->assertSessionHasNoErrors();
        $app->refresh();
        $this->assertSame('more_info', $app->status);
        $this->assertFalse($app->stepDone('review'));
        $new = null;
        Mail::assertSent(FinanceApplicationMail::class, function ($m) use (&$new) {
            if ($m->kind === 'more_info') {
                $new = basename($m->link);
            }

            return true;
        });

        // the applicant comes back with the new link, adds the file and sends it back
        auth()->logout();
        $this->get(route('finance.show', $new))->assertOk()->assertSee('Please add the logbook');
        $this->post(route('finance.save', [$new, 'documents']), ['docs' => ['other' => [$this->pdf('logbook.pdf')]]]);
        $this->post(route('finance.save', [$new, 'review']), ['confirm_true' => 1, 'consent_crb' => 1, 'consent_data' => 1])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $app->fresh()->status);

        $this->actingAs($manager)->post(route('staff.finance.decide', $app), ['decision' => 'approved', 'note' => 'Subject to a valuation.'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $app->fresh()->status);
        Mail::assertSent(FinanceApplicationMail::class, fn ($m) => $m->kind === 'approved');
        // a final decision cannot be changed again
        $this->actingAs($manager)->post(route('staff.finance.decide', $app), ['decision' => 'declined', 'note' => 'x'])->assertSessionHasErrors('decision');
    }

    public function test_the_copy_makes_no_approval_promises(): void
    {
        foreach ([route('finance.choose'), route('finance.start', 'asset'), route('finance.start', 'trade')] as $url) {
            $html = strtolower($this->get($url)->assertOk()->getContent());
            foreach (['guarantee', 'guaranteed', 'instant approval', 'pre-approved', 'you qualify'] as $phrase) {
                $this->assertStringNotContainsString($phrase, $html, "$url says \"$phrase\"");
            }
            $this->assertStringNotContainsString('cloud9', $html);
        }
    }
}
