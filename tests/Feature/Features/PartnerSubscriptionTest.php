<?php

namespace Tests\Feature\Features;

use App\Models\Business;
use App\Models\Investment;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Partner KYC and online subscription (owner, 23 Oct 2026): verify, sign, send, staff confirm, line opens.
 */
class PartnerSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private User $user;

    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00');
        Storage::fake('local');
        config(['lending.partner_subscriptions.enabled' => true, 'lending.requests.enabled' => true, 'lending.approvals.enabled' => false,
            'lending.ledger.enabled' => false, 'lending.aml.enabled' => false]);
        $this->seed(RoleSeeder::class);
        $this->staff = User::factory()->create();
        $this->staff->assignRole('admin');
        $this->staff->givePermissionTo(['edit partners', 'view partners', 'edit users']);

        $img = imagecreatetruecolor(120, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        ob_start();
        imagepng($img);
        Storage::disk('local')->put('signatures/sig.png', ob_get_clean());

        $this->user = User::factory()->create(['kyc_status' => 'approved', 'signature' => 'signatures/sig.png', 'id_number' => '12345678']);
        $this->user->assignRole('partner');
        $this->partner = Partner::create(['name' => 'Wanjiku Kamau', 'email' => 'wk@example.test', 'type' => 'individual',
            'account_type' => 'principal', 'user_id' => $this->user->id, 'profit_share_rate' => 8]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sign(array $extra = [])
    {
        return $this->actingAs($this->user)->post(route('partner.subscribe.store'), $extra + [
            'amount' => '50,000', 'term' => 'long_term', 'months' => 12, 'agree' => 1,
        ]);
    }

    public function test_a_partner_must_say_where_the_money_comes_from_before_subscribing(): void
    {
        $this->sign()->assertSessionHasErrors('amount');
        $this->assertSame(0, DB::table('partner_requests')->count());

        $this->actingAs($this->user)->post(route('partner.verify.save'), ['source_of_funds' => 'salary_savings'])->assertRedirect();
        $this->assertSame('salary_savings', $this->partner->fresh()->source_of_funds);
        $this->sign()->assertRedirect();
        $this->assertSame(1, DB::table('partner_requests')->count());
    }

    public function test_signing_sending_and_confirming_opens_the_line(): void
    {
        $this->partner->update(['source_of_funds' => 'salary_savings']);
        $this->sign()->assertRedirect();
        $r = DB::table('partner_requests')->first();
        $p = json_decode($r->payload, true);
        $this->assertSame('subscription', $p['kind']);
        $this->assertSame('long_term', $p['term']);
        $this->assertSame('2027-10-24', $p['maturity']);
        $this->assertSame(64, strlen($p['agreement_sha256']));
        $this->assertSame($p['agreement_sha256'], hash('sha256', Storage::disk('local')->get($p['agreement_path'])));
        $this->assertSame(50000.0, (float) $r->amount);

        // staff can't confirm before the partner says the money went
        $this->actingAs($this->staff)->post(route('staff.requests.partner', $r->id), ['decision' => 'yes'])->assertSessionHas('error');
        $this->assertSame('pending', DB::table('partner_requests')->value('status'));

        $this->actingAs($this->user)->post(route('partner.subscriptions.sent', $r->id), ['method' => 'mpesa', 'transaction_code' => 'tjk12abc9', 'sent_on' => '2026-10-24'])
            ->assertSessionHasNoErrors();
        $this->assertSame('TJK12ABC9', DB::table('partner_requests')->value('reference'));

        $this->actingAs($this->staff)->post(route('staff.requests.partner', $r->id), ['decision' => 'yes'])->assertSessionHas('success');
        $r = DB::table('partner_requests')->first();
        $this->assertSame('done', $r->status);
        $line = Investment::findOrFail($r->investment_id);
        $this->assertSame('long_term', $line->facility_type);
        $this->assertSame('2027-10-24', Carbon::parse($line->maturity_date)->toDateString());
        $this->assertSame(50000.0, (float) $line->initial_amount);
        $journal = PartnerTransaction::findOrFail($r->partner_transaction_id);
        $this->assertSame('contribution', $journal->type);
        $this->assertSame($line->id, (int) $journal->investment_id);
        $this->assertSame(50000.0, (float) $journal->amount);

        $this->actingAs($this->user)->get(route('partner.subscriptions.show', $r->id))->assertOk()->assertSee('Your line is open');
        $this->actingAs($this->user)->get(route('partner.subscriptions.file', $r->id))->assertOk();
        $this->actingAs($this->staff)->get(route('partner.subscriptions.staff-file', $r->id))->assertOk();
    }

    public function test_a_code_already_used_is_refused_and_only_an_unpaid_subscription_can_be_cancelled(): void
    {
        $this->partner->update(['source_of_funds' => 'salary_savings']);
        app(\App\Services\Partners\PartnerLines::class)->open($this->partner, 1000, 'short_term', null, 'USED1', '2026-10-20');
        $this->sign(['term' => 'short_term'])->assertSessionHasNoErrors();
        $id = (int) DB::table('partner_requests')->value('id');
        $this->actingAs($this->user)->post(route('partner.subscriptions.sent', $id), ['method' => 'mpesa', 'transaction_code' => 'used1', 'sent_on' => '2026-10-24'])
            ->assertSessionHasErrors('transaction_code');

        // one open subscription at a time
        $this->sign(['term' => 'short_term'])->assertSessionHasErrors('amount');

        $this->actingAs($this->user)->post(route('partner.subscriptions.cancel', $id))->assertRedirect(route('partner.subscribe'));
        $this->assertSame('declined', DB::table('partner_requests')->where('id', $id)->value('status'));
    }

    public function test_a_business_partner_needs_its_verified_business(): void
    {
        $this->partner->update(['type' => 'business', 'source_of_funds' => 'business_income']);
        $this->sign()->assertSessionHasErrors('amount');

        $b = Business::create(['owner_user_id' => $this->user->id, 'legal_name' => 'Kamau Traders Ltd', 'registration_type' => 'limited_company', 'status' => Business::STATUS_SUBMITTED]);
        $this->actingAs($this->user)->post(route('partner.verify.save'), ['source_of_funds' => 'business_income', 'business_id' => $b->id]);
        $this->assertSame($b->id, $this->partner->fresh()->business_id);
        $this->sign()->assertSessionHasErrors('amount');

        $b->update(['status' => Business::STATUS_APPROVED]);
        $this->sign()->assertRedirect();

        $other = Business::create(['owner_user_id' => $this->staff->id, 'legal_name' => 'Not Theirs Ltd', 'status' => Business::STATUS_APPROVED]);
        $this->actingAs($this->user)->post(route('partner.verify.save'), ['source_of_funds' => 'business_income', 'business_id' => $other->id])
            ->assertSessionHasErrors('source_of_funds');
    }

    public function test_switched_off_nothing_can_be_signed(): void
    {
        config(['lending.partner_subscriptions.enabled' => false]);
        $this->partner->update(['source_of_funds' => 'salary_savings']);
        $this->actingAs($this->user)->get(route('partner.subscribe'))->assertOk()->assertSee('Opening soon');
        $this->sign()->assertSessionHasErrors('amount');
    }

    public function test_a_partner_kyc_is_approved_without_next_of_kin_but_needs_aml_when_on(): void
    {
        $u = User::factory()->create(['kyc_status' => 'submitted', 'name' => 'Otieno Partner']);
        $u->assignRole('partner');
        config(['lending.aml.enabled' => true]);
        $this->actingAs($this->staff)->post(route('kyc-reviews.approve', $u), ['checked_identity' => 1])->assertSessionHasErrors('aml');

        $screening = app(\App\Services\Aml\Screening::class);
        $screening->screen($u, $this->staff);
        $screening->decide($u->fresh(), array_keys(\App\Services\Aml\Screening::CHECKS), null, $this->staff);
        $this->actingAs($this->staff)->post(route('kyc-reviews.approve', $u), ['checked_identity' => 1])->assertSessionHasNoErrors();
        $u->refresh();
        $this->assertSame('approved', $u->kyc_status);
        $this->assertTrue($u->kyc_checks['identity']);
        $this->assertTrue($u->kyc_checks['aml']['clear']);
    }
}
