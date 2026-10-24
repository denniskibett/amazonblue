<?php

namespace Tests\Feature\Features;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\User;
use App\Services\Approvals;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Money\AuditLoanFixtures;
use Tests\TestCase;

/**
 * M-Pesa B2C payouts (owner, 23 Oct 2026). Safaricom is faked: nothing leaves the test.
 */
class MpesaB2cTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $maker;

    private User $checker;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-24 10:00');
        config(['lending.mpesa.b2c_enabled' => true, 'lending.approvals.enabled' => true, 'lending.engine' => 'v2', 'lending.ledger.enabled' => false,
            'lending.split.enabled' => false, 'lending.mpesa.env' => 'sandbox', 'lending.mpesa.consumer_key' => 'k', 'lending.mpesa.consumer_secret' => 's',
            'lending.mpesa.callback_secret' => 'test-secret-0123456789', 'lending.mpesa.b2c_shortcode' => '600000',
            'lending.mpesa.b2c_initiator' => 'testapi', 'lending.mpesa.b2c_security_credential' => 'cred']);
        $this->seed(RoleSeeder::class);
        $this->loanTypes();
        $this->maker = User::factory()->create();
        $this->maker->assignRole('admin');
        $this->maker->givePermissionTo(['disburse loans', 'view disbursements']);
        $this->checker = User::factory()->create();
        $this->checker->assignRole('admin');
        $this->checker->givePermissionTo(['disburse loans']);
        $this->loanRow(700, 1, 10000, 10000, '2026-10-24', '2026-11-03', 'approved', 1, 30);
        $this->cycleRow(700, 700, 1, 0, 2000, 12000, 20, '2026-10-24', '2026-11-03', 'active', 'Initial');
    }

    /** Safaricom accepts every request (faked; nothing leaves the test). */
    private function safaricomAccepts(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok', 'expires_in' => 3599]),
            '*/mpesa/b2c/v1/paymentrequest' => Http::response(['ConversationID' => 'AG_20261024_1', 'OriginatorConversationID' => 'ORIG-1', 'ResponseCode' => '0', 'ResponseDescription' => 'Accept the service request successfully.']),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function safaricomSays(int $code, string $receipt = 'TJO1ABC234'): void
    {
        $this->postJson('/mpesa/test-secret-0123456789/b2c/result', ['Result' => [
            'ResultType' => 0, 'ResultCode' => $code, 'ResultDesc' => $code ? 'The balance is insufficient for the transaction.' : 'The service request is processed successfully.',
            'OriginatorConversationID' => 'ORIG-1', 'ConversationID' => 'AG_20261024_1', 'TransactionID' => $receipt,
            'ResultParameters' => ['ResultParameter' => [['Key' => 'TransactionAmount', 'Value' => 10000], ['Key' => 'TransactionReceipt', 'Value' => $receipt]]],
        ]])->assertOk()->assertJson(['ResultCode' => 0]);
    }

    public function test_ask_approve_send_and_the_result_records_the_disbursement(): void
    {
        $this->safaricomAccepts();
        $this->actingAs($this->maker)->post(route('mpesa.payouts.store', 700), ['phone' => '0712 345 678', 'amount' => 10000])->assertSessionHas('success');
        $row = DB::table('mpesa_transactions')->first();
        $this->assertSame('b2c', $row->type);
        $this->assertSame('254712345678', $row->msisdn);
        $this->assertNull($row->conversation_id);
        Http::assertNothingSent();

        $a = DB::table('approvals')->where('action', 'mpesa_b2c')->first();
        try {
            app(Approvals::class)->decide($a->id, $this->maker, true);
            $this->fail('the person who asked cannot approve');
        } catch (\DomainException) {
        }
        app(Approvals::class)->decide($a->id, $this->checker, true);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/mpesa/b2c/v1/paymentrequest') && $r['PartyB'] === '254712345678' && $r['Amount'] === 10000
            && str_ends_with($r['ResultURL'], '/mpesa/test-secret-0123456789/b2c/result'));
        $this->assertSame('AG_20261024_1', DB::table('mpesa_transactions')->value('conversation_id'));
        $this->assertSame('approved', Loan::find(700)->status);

        $this->safaricomSays(0);
        $row = DB::table('mpesa_transactions')->first();
        $this->assertSame('matched', $row->status);
        $this->assertSame('TJO1ABC234', $row->trans_id);
        $d = Disbursement::findOrFail($row->disbursement_id);
        $this->assertSame(10000.0, (float) $d->amount);
        $this->assertSame('TJO1ABC234', $d->mpesa_ref);
        $this->assertSame('disbursed', Loan::find(700)->status);

        // Safaricom repeating the callback changes nothing
        $this->safaricomSays(0);
        $this->assertSame(1, Disbursement::count());
    }

    public function test_a_failed_payout_records_nothing_and_a_wrong_secret_is_refused(): void
    {
        $this->safaricomAccepts();
        $this->actingAs($this->maker)->post(route('mpesa.payouts.store', 700), ['phone' => '0712345678', 'amount' => 10000]);
        app(Approvals::class)->decide(DB::table('approvals')->value('id'), $this->checker, true);
        $this->postJson('/mpesa/wrong-secret/b2c/result', ['Result' => ['ConversationID' => 'AG_20261024_1', 'ResultCode' => 0]])->assertNotFound();

        $this->safaricomSays(2001);
        $row = DB::table('mpesa_transactions')->first();
        $this->assertSame('failed', $row->status);
        $this->assertStringContainsString('insufficient', $row->note);
        $this->assertSame(0, Disbursement::count());
        $this->assertSame('approved', Loan::find(700)->status);
    }

    public function test_it_refuses_when_switched_off_twice_asked_or_not_approved_yet(): void
    {
        config(['lending.approvals.enabled' => false]);
        $this->actingAs($this->maker)->post(route('mpesa.payouts.store', 700), ['phone' => '0712345678', 'amount' => 10000])->assertSessionHas('error');
        config(['lending.approvals.enabled' => true]);

        $this->actingAs($this->maker)->post(route('mpesa.payouts.store', 700), ['phone' => '0712345678', 'amount' => 10000])->assertSessionHas('success');
        $this->actingAs($this->maker)->post(route('mpesa.payouts.store', 700), ['phone' => '0712345678', 'amount' => 10000])->assertSessionHas('error');
        $this->actingAs($this->maker)->post(route('mpesa.payouts.store', 700), ['phone' => '12', 'amount' => 10000])->assertSessionHas('error');

        // Safaricom refusing the request: the approval stays pending and nothing is marked sent
        Http::fake(['*/oauth/v1/generate*' => Http::response(['access_token' => 'tok']), '*/mpesa/b2c/v1/paymentrequest' => Http::response(['errorMessage' => 'Bad'], 400)]);
        try {
            app(Approvals::class)->decide(DB::table('approvals')->value('id'), $this->checker, true);
            $this->fail('should have refused');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('Nothing was sent', $e->getMessage());
        }
        $this->assertSame('pending', DB::table('approvals')->value('status'));
        $this->assertNull(DB::table('mpesa_transactions')->value('conversation_id'));
    }
}
