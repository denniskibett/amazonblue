<?php

namespace Tests\Feature\Features;

use App\Models\Disbursement;
use App\Models\Integration;
use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\Partner;
use App\Models\Repayment;
use App\Models\SharetPaymentRequest;
use App\Models\SharetPayout;
use App\Models\User;
use App\Support\Integrations;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/**
 * Sharet Pay, the second rail beside the own paybill (9 Nov 2026). Sharet is faked (Http::fake) and webhooks carry real
 * HMAC signatures; nothing leaves the test. The Sharet tables come from database/sql/2026_11_09_sharet_pay.sql
 * (there is no migration), loaded before each test's transaction.
 */
class SharetPayTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private const WHSEC = 'whsec_test_0123456789abcdef';

    private User $borrower;

    private User $admin;

    private User $checker;

    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->migrateDatabases();
            $this->app[Kernel::class]->setArtisan(null);
            RefreshDatabaseState::$migrated = true;
        }
        // CREATE TABLE IF NOT EXISTS commits on MySQL, so it runs before the test's transaction opens
        $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents(base_path('database/sql/2026_11_09_sharet_pay.sql')));
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if (! str_starts_with(strtoupper($statement), 'SELECT')) {
                DB::unprepared($statement);
            }
        }
        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.ledger.enabled' => false, 'lending.split.enabled' => false, 'lending.mpesa.enabled' => true,
            'lending.mpesa.callback_secret' => 'test-callback-secret-1234567890', 'lending.requests.enabled' => true,
            'sharet.enabled' => true, 'sharet.api_key' => 'sk_test_abc', 'sharet.webhook_secret' => self::WHSEC,
            'sharet.base_url' => 'https://sharet.test/api/v1', 'sharet.payouts_enabled' => true]);
        Integrations::forget();
        $this->loanTypes();
        Carbon::setTestNow('2026-11-09 10:00');
        $this->loanRow(901, 1, 5000, 5000, '2026-11-05', '2026-11-15', 'disbursed', 1, 30);
        $this->cycleRow(901, 901, 1, 0, 1000, 6000, 20, '2026-11-05', '2026-11-15', 'active', 'Initial');
        DB::table('loans')->update(['consent_date' => '2026-11-05']); // the loan page prints it when consent is given
        $this->borrower = User::findOrFail(Loan::findOrFail(901)->user_id);
        $this->borrower->forceFill(['phone' => '+254 712 345678'])->save();
        $this->borrower->assignRole('borrower');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo(['disburse loans', 'view disbursements', 'create repayments']);
        $this->checker = User::factory()->create();
        $this->checker->assignRole('admin');
        $this->checker->givePermissionTo(['disburse loans']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fakeSharet(array $payment = ['id' => 'pay_test_1', 'status' => 'paid', 'transaction_id' => 'SHP0A1B2C3']): void
    {
        Http::fake([
            'sharet.test/api/v1/payment-requests/*' => Http::response($this->linkObject('paid')),
            'sharet.test/api/v1/payment-requests' => Http::response($this->linkObject('open'), 201),
            'sharet.test/api/v1/payments/*' => Http::response($payment),
            'sharet.test/api/v1/payments' => Http::response($payment, 201),
            'sharet.test/api/v1/accounts/me' => Http::response(['sharet_id' => 'SH-AIMZONE', 'name' => 'Aimzone Credit']),
            'sharet.test/api/v1/webhooks' => Http::response(['id' => 7, 'secret' => 'whsec_registered_secret_xyz'], 201),
        ]);
    }

    private function linkObject(string $status, string $id = 'pr_test_1', string $amount = '2500.00'): array
    {
        return ['id' => $id, 'object' => 'payment_request', 'environment' => 'test', 'status' => $status, 'amount' => $amount,
            'pay_url' => 'https://sharet.test/r/tok123', 'paid_via' => $status === 'paid' ? 'mpesa' : null,
            'transaction_id' => $status === 'paid' ? 'SHD1CCFAF6' : null, 'paid_at' => $status === 'paid' ? '2026-11-09T07:30:00+00:00' : null,
            'expires_at' => '2026-11-12T10:00:00+00:00', 'metadata' => ['loan_id' => '901']];
    }

    private function webhook(array $event, ?string $secret = self::WHSEC)
    {
        $body = json_encode($event);
        $t = time();
        $sig = 't=' . $t . ',v1=' . hash_hmac('sha256', $t . '.' . $body, (string) $secret);

        return $this->call('POST', '/webhooks/sharet', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_SHARET_SIGNATURE' => $sig], $body);
    }

    private function makeLink(): SharetPaymentRequest
    {
        $this->actingAs($this->borrower)->post(route('account.repay.sharet', 901), ['amount' => 2500])->assertRedirect('https://sharet.test/r/tok123');

        return SharetPaymentRequest::firstOrFail();
    }

    // ---------------------------------------------------------------- repayments in

    public function test_a_borrower_pay_link_is_made_with_an_idempotency_key_and_reused_for_the_same_amount(): void
    {
        $this->fakeSharet();
        $this->actingAs($this->borrower)->get(route('account.repay', 901))->assertOk()->assertSee('Pay with Sharet Pay');
        $link = $this->makeLink();
        $this->assertSame('open', $link->status);
        $this->assertEquals(2500, (float) $link->amount);
        $this->assertSame(901, (int) $link->loan_id);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/payment-requests') && $r->hasHeader('Idempotency-Key')
            && $r->hasHeader('Authorization', 'Bearer sk_test_abc') && $r['amount'] === '2500.00' && $r['metadata']['loan_id'] === '901');

        $this->actingAs($this->borrower)->post(route('account.repay.sharet', 901), ['amount' => 2500])->assertRedirect('https://sharet.test/r/tok123');
        $this->assertSame(1, SharetPaymentRequest::count());
        Http::assertSentCount(1);
    }

    public function test_a_borrower_cannot_pay_someone_elses_loan_and_nothing_shows_while_sharet_is_off(): void
    {
        $this->fakeSharet();
        $other = User::factory()->create();
        $other->assignRole('borrower');
        $this->actingAs($other)->post(route('account.repay.sharet', 901), ['amount' => 100])->assertNotFound();

        config(['sharet.enabled' => false]);
        $this->actingAs($this->borrower)->get(route('account.repay', 901))->assertOk()->assertDontSee('Pay with Sharet Pay');
        $this->actingAs($this->borrower)->post(route('account.repay.sharet', 901), ['amount' => 100])->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_a_webhook_with_a_bad_or_missing_signature_is_refused(): void
    {
        $this->fakeSharet();
        $this->makeLink();
        $event = ['id' => 'evt_1', 'type' => 'payment_request.paid', 'data' => $this->linkObject('paid')];
        $this->webhook($event, 'whsec_wrong')->assertStatus(400);
        $this->call('POST', '/webhooks/sharet', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($event))->assertStatus(400);
        $this->assertSame(0, Repayment::count());
        $this->assertSame(0, DB::table('sharet_webhook_events')->count());
    }

    public function test_a_signed_paid_event_records_one_repayment_through_the_mpesa_path_and_repeats_or_polls_add_nothing(): void
    {
        $this->fakeSharet();
        $link = $this->makeLink();
        $event = ['id' => 'evt_1', 'type' => 'payment_request.paid', 'data' => $this->linkObject('paid')];

        $this->webhook($event)->assertOk()->assertJson(['received' => true]);
        $r = Repayment::sole();
        $this->assertSame('sharet', $r->mode);
        $this->assertSame('cash', $r->kind);
        $this->assertSame('SHD1CCFAF6', $r->transaction);
        $this->assertNull($r->mpesa_ref, 'a Sharet ID is not an M-Pesa receipt');
        $this->assertEquals(2500, (float) $r->amount);
        $this->assertSame(901, (int) $r->loan_cycle_id);
        $this->assertSame('2026-11-09', $r->repayment_date->toDateString());
        $this->assertSame($r->id, (int) $link->fresh()->repayment_id);
        $this->assertSame('paid', $link->fresh()->status);

        $this->webhook($event)->assertOk()->assertJson(['duplicate' => true]);                       // same event again
        $this->webhook(['id' => 'evt_2'] + $event)->assertOk();                                       // same payment, new event id
        $this->artisan('sharet:sync')->assertSuccessful();                                            // and the poll
        $this->assertSame(1, Repayment::count());
    }

    public function test_the_poll_records_a_payment_whose_webhook_never_came(): void
    {
        $this->fakeSharet();
        $this->makeLink();
        $this->artisan('sharet:sync')->assertSuccessful();
        $this->assertSame('SHD1CCFAF6', Repayment::sole()->transaction);
        $this->artisan('sharet:sync')->assertSuccessful();
        $this->assertSame(1, Repayment::count());
    }

    public function test_paying_a_loan_that_no_longer_takes_payments_is_held_for_staff_not_recorded(): void
    {
        $this->fakeSharet();
        $link = $this->makeLink();
        DB::table('loans')->where('id', 901)->update(['status' => 'repaid']);
        $this->webhook(['id' => 'evt_1', 'type' => 'payment_request.paid', 'data' => $this->linkObject('paid')])->assertOk();
        $this->assertSame(0, Repayment::count());
        $this->assertSame('paid', $link->fresh()->status);
        $this->assertStringContainsString('not recorded', (string) $link->fresh()->note);
    }

    public function test_staff_send_a_pay_link_from_the_loan(): void
    {
        $this->fakeSharet();
        $this->actingAs($this->admin)->get(route('users.loans.show', [$this->borrower->id, 901]))->assertOk()->assertSee('Send Sharet pay link');
        $this->actingAs($this->admin)->post(route('sharet.pay-link', 901), ['amount' => 2500])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'https://sharet.test/r/tok123'));
        $this->assertSame($this->admin->id, (int) SharetPaymentRequest::sole()->created_by);
    }

    public function test_the_own_paybill_rail_still_records_mpesa_payments_with_sharet_on(): void
    {
        $this->fakeSharet();
        $this->postJson('/mpesa/test-callback-secret-1234567890/c2b/confirmation', ['TransactionType' => 'Pay Bill', 'TransID' => 'UJL1ABCDE1',
            'TransTime' => '20261109093000', 'TransAmount' => '2500.00', 'BusinessShortCode' => '174379', 'BillRefNumber' => '901', 'MSISDN' => '254712345678'])
            ->assertOk()->assertJson(['ResultCode' => 0]);
        $this->assertSame('matched', MpesaTransaction::sole()->status);
        $r = Repayment::sole();
        $this->assertSame('mpesa', $r->mode);
        $this->assertSame('UJL1ABCDE1', $r->mpesa_ref);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- money out

    private function approvedLoan(): void
    {
        $this->loanRow(700, 1, 10000, 10000, '2026-11-09', '2026-11-19', 'approved', 1, 30);
        $this->cycleRow(700, 700, 1, 0, 2000, 12000, 20, '2026-11-09', '2026-11-19', 'active', 'Initial');
        DB::table('loans')->where('id', 700)->update(['consent_date' => '2026-11-09']);
    }

    public function test_a_disbursement_goes_only_after_its_sign_off_and_once_per_slot(): void
    {
        config(['lending.approvals.enabled' => true]);
        $this->approvedLoan();
        $this->fakeSharet();
        $this->actingAs($this->admin)->get(route('users.loans.show', [Loan::findOrFail(700)->user_id, 700]))->assertOk()->assertSee('Send through Sharet');
        $this->actingAs($this->admin)->post(route('sharet.disburse', 700), ['phone' => '0712 345 678', 'amount' => 10000])->assertSessionHas('success');
        $row = SharetPayout::sole();
        $this->assertSame('awaiting_approval', $row->status);

        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T08'])->assertSuccessful();
        Http::assertNothingSent();                                                                    // not approved yet

        $this->actingAs($this->checker)->post(route('staff.sign-offs.decide', DB::table('approvals')->where('action', 'sharet_payout')->value('id')), ['decision' => 'approve'])
            ->assertSessionHas('success');
        $this->assertSame('queued', $row->fresh()->status);
        Http::assertNothingSent();                                                                    // approving sends nothing by itself

        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T12'])->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/payments') && $r->hasHeader('Idempotency-Key', 'aimzone-payout-' . $row->id . '-1')
            && $r['amount'] === '10000.00' && $r['to'] === ['type' => 'mpesa', 'phone' => '254712345678']);
        $row->refresh();
        $this->assertSame('paid', $row->status);
        $d = Disbursement::findOrFail($row->disbursement_id);
        $this->assertSame('sharet', $d->mode);
        $this->assertSame('SHP0A1B2C3', $d->transaction);
        $this->assertSame('disbursed', Loan::findOrFail(700)->status);

        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T12'])->assertSuccessful();
        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T16'])->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame(1, Disbursement::where('loan_id', 700)->count());
        $this->actingAs($this->admin)->get(route('sharet.index'))->assertOk()->assertSee('Loan #700 · disbursement')->assertSee('SHP0A1B2C3');
    }

    public function test_a_payout_still_paying_is_sent_once_per_slot_and_recorded_once_from_the_webhook(): void
    {
        $this->approvedLoan();
        $this->fakeSharet(['id' => 'pay_test_9', 'status' => 'paying']);
        $this->actingAs($this->admin)->post(route('sharet.disburse', 700), ['phone' => '0712345678', 'amount' => 10000])->assertSessionHas('success');
        $this->assertSame('queued', SharetPayout::sole()->status, 'sign-offs off: the ask is the approval, as for a hand disbursement');

        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T08'])->assertSuccessful();
        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T08'])->assertSuccessful();
        Http::assertSentCount(1);
        $row = SharetPayout::sole();
        $this->assertSame('paying', $row->status);
        $this->assertSame('pay_test_9', $row->sharet_payment_id);
        $this->assertSame(0, Disbursement::count());

        $paid = ['id' => 'pay_test_9', 'object' => 'payment', 'status' => 'paid', 'transaction_id' => 'SHP9Z9Z9Z9', 'amount' => '10000.00'];
        $this->webhook(['id' => 'evt_p1', 'type' => 'payment.paid', 'data' => $paid])->assertOk();
        $this->webhook(['id' => 'evt_p2', 'type' => 'payment.paid', 'data' => $paid])->assertOk();
        $this->assertSame('paid', $row->fresh()->status);
        $this->assertSame(1, Disbursement::count());
        $this->assertSame('SHP9Z9Z9Z9', Disbursement::sole()->transaction);
    }

    public function test_a_loan_that_is_not_approved_cannot_be_paid_out(): void
    {
        $this->fakeSharet();
        $this->actingAs($this->admin)->post(route('sharet.disburse', 901), ['phone' => '0712345678', 'amount' => 1000])
            ->assertSessionHas('error', 'Only an approved loan can be paid out.');
        $this->assertSame(0, SharetPayout::count());
    }

    public function test_a_partner_payout_goes_only_once_its_withdrawal_is_approved_and_once_per_slot(): void
    {
        config(['lending.approvals.enabled' => true]);
        $this->fakeSharet();
        $partner = Partner::create(['name' => 'IMAGINE-NATION', 'email' => 'imagine@example.test', 'type' => 'corporate', 'account_type' => 'principal', 'phone' => '0722000111']);
        $req = app(\App\Services\Requests::class);
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $fund = $req->partner('funding', $partner, 100000, null, 'QBANK123', null, null);
        $this->actingAs($manager)->post(route('staff.requests.partner', $fund), ['decision' => 'yes'])->assertSessionHas('success');
        $out = $req->partner('withdrawal', $partner, 30000, 'principal', null, null, null);
        $this->actingAs($manager)->get(route('staff.requests'))->assertOk()->assertSee('Pay through Sharet');
        $this->actingAs($manager)->post(route('staff.requests.partner', $out), ['decision' => 'yes', 'via_sharet' => 1, 'sharet_phone' => '0722000111'])
            ->assertSessionHas('success');
        $this->assertSame('prepared', DB::table('partner_requests')->where('id', $out)->value('status'));
        $row = SharetPayout::sole();
        $this->assertSame('partner_payout', $row->kind);

        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T08'])->assertSuccessful();
        Http::assertNothingSent();                                                                    // the withdrawal is not approved yet

        $this->actingAs($this->admin)->post(route('staff.sign-offs.decide', DB::table('approvals')->where('action', 'partner_withdrawal')->value('id')), ['decision' => 'approve'])
            ->assertSessionHas('success');
        $this->assertSame('done', DB::table('partner_requests')->where('id', $out)->value('status'));

        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T12'])->assertSuccessful();
        $this->artisan('payments:sweep', ['--slot' => '2026-11-09T12'])->assertSuccessful();
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $r) => $r['amount'] === '30000.00' && $r['to'] === ['type' => 'mpesa', 'phone' => '254722000111']);
        $this->assertSame('paid', $row->fresh()->status);
        $this->assertSame('SHP0A1B2C3', DB::table('partner_requests')->where('id', $out)->value('bank_reference'));
        $this->assertEqualsWithDelta(70000, (float) $partner->getWallet('principal')->refresh()->balanceFloat, 0.001, 'posted once, by the approval');
    }

    public function test_a_declined_withdrawal_cancels_its_sharet_payout_and_nothing_goes_while_payouts_are_off(): void
    {
        $this->fakeSharet();
        $partner = Partner::create(['name' => 'P', 'email' => 'p@example.test', 'type' => 'corporate', 'account_type' => 'principal']);
        $out = app(\App\Services\Requests::class)->partner('withdrawal', $partner, 1000, 'principal', null, null, null);
        app(\App\Services\Sharet\SharetPayouts::class)->queuePartnerPayout($out, '0722000111', $this->admin);
        DB::table('partner_requests')->where('id', $out)->update(['status' => 'declined']);

        config(['sharet.payouts_enabled' => false]);
        $this->artisan('payments:sweep')->assertSuccessful();
        $this->assertSame('awaiting_approval', SharetPayout::sole()->status);

        config(['sharet.payouts_enabled' => true]);
        $this->artisan('payments:sweep')->assertSuccessful();
        $this->assertSame('cancelled', SharetPayout::sole()->status);
        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------- settings

    public function test_only_staff_with_the_permission_reach_the_settings_and_secrets_never_reach_the_browser(): void
    {
        Integration::create(['slug' => 'sharet', 'name' => 'Sharet Pay', 'category' => 'Payments', 'enabled' => true,
            'settings' => ['SHARET_API_KEY' => 'sk_test_supersecretkey9876', 'SHARET_ENABLED' => '1']]);
        Integrations::forget();
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        Http::fake();
        foreach ([$this->borrower, $partner] as $u) {
            // the staff area sends customers back to their account (EnsureStaff)
            $this->actingAs($u)->get(route('staff.integrations'))->assertRedirect(route('account.home'));
            $this->actingAs($u)->post(route('staff.integrations.update', 'sharet'), ['fields' => ['SHARET_API_KEY' => 'sk_live_x']])->assertRedirect(route('account.home'));
            $this->actingAs($u)->post(route('sharet.test'))->assertRedirect(route('account.home'));
            $this->actingAs($u)->get(route('sharet.index'))->assertRedirect(route('account.home'));
        }
        $this->assertSame('sk_test_supersecretkey9876', Integration::where('slug', 'sharet')->first()->settings['SHARET_API_KEY']);
        Http::assertNothingSent();
        // staff without the integrations permission are refused too
        $teller = User::factory()->create();
        $teller->assignRole('teller');
        $this->actingAs($teller)->get(route('staff.integrations'))->assertForbidden();
        $this->actingAs($teller)->post(route('sharet.test'))->assertForbidden();
        $super = User::factory()->create();
        $super->assignRole('super-admin');
        $this->actingAs($super)->get(route('staff.integrations'))->assertOk()->assertSee('Sharet Pay')->assertSee('Test connection')
            ->assertDontSee('sk_test_supersecretkey9876');
        $this->actingAs($super)->get(route('sharet.index'))->assertOk();
    }

    public function test_test_connection_registers_the_webhook_and_keeps_its_secret_encrypted(): void
    {
        config(['sharet.webhook_secret' => null]);
        $this->fakeSharet();
        $super = User::factory()->create();
        $super->assignRole('super-admin');
        $this->actingAs($super)->post(route('sharet.test'))->assertSessionHas('success', fn ($m) => str_contains($m, 'Webhook registered'));
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/webhooks') && $r['url'] === route('sharet.webhook'));
        $this->assertSame('whsec_registered_secret_xyz', Integration::where('slug', 'sharet')->first()->settings['SHARET_WEBHOOK_SECRET']);
        $this->assertStringNotContainsString('whsec_registered', (string) DB::table('integrations')->where('slug', 'sharet')->value('settings'));
        $this->actingAs($super)->get(route('staff.integrations'))->assertDontSee('whsec_registered_secret_xyz');

        // a new key (test -> live) drops the old secret, so Test connection registers the new key's webhook
        $this->actingAs($super)->post(route('staff.integrations.update', 'sharet'), ['fields' => ['SHARET_API_KEY' => 'sk_live_newkey']])->assertSessionHas('success');
        $this->assertArrayNotHasKey('SHARET_WEBHOOK_SECRET', Integration::where('slug', 'sharet')->first()->settings);
    }
}
