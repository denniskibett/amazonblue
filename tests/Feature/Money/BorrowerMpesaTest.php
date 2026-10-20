<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\PaymentClaim;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Stage 4 Phase 4: the borrower's "I've paid" claim and "Pay with M-Pesa". */
class BorrowerMpesaTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $borrower;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['lending.engine' => 'v2', 'lending.mpesa.claims_enabled' => true, 'lending.mpesa.stk_enabled' => true,
            'lending.mpesa.consumer_key' => 'key', 'lending.mpesa.consumer_secret' => 'secret',
            'lending.mpesa.callback_secret' => 'test-callback-secret-1234567890']);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');
        $this->loanRow(902, 1, 5000, 5000, '2026-10-15', '2026-10-25', 'disbursed', 1, 30);
        $this->borrower = User::findOrFail(Loan::findOrFail(902)->user_id);
        $this->borrower->forceFill(['phone' => '+254 712 345678'])->save();
        $this->borrower->assignRole('borrower');
        $this->cycleRow(902, 902, 1, 0, 1000, 6000, 20, '2026-10-15', '2026-10-25', 'active', 'Initial');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_claim_is_stored_and_reads_received_once_the_payment_arrives(): void
    {
        $this->actingAs($this->borrower)->post(route('account.repay.confirm', 902), ['code' => 'UJL1CLAIM1', 'amount' => 2000])->assertRedirect();
        $claim = PaymentClaim::firstOrFail();
        $this->assertSame('checking', $claim->status);

        config(['lending.mpesa.enabled' => true]);
        app(\App\Services\Mpesa\PaymentRecorder::class)->receiveC2b(['TransID' => 'UJL1CLAIM1', 'TransAmount' => '2000', 'BillRefNumber' => '902',
            'MSISDN' => '254712345678', 'TransTime' => '20261021100500']);
        $this->assertSame('received', $claim->fresh()->status);
        $this->actingAs($this->borrower)->get(route('account.repay', 902))->assertOk()->assertSee('UJL1CLAIM1')->assertSee('Received');
    }

    public function test_a_claim_for_a_payment_that_went_to_the_queue_settles_it(): void
    {
        MpesaTransaction::create(['type' => 'c2b', 'trans_id' => 'UJL1CLAIM2', 'amount' => 1500, 'bill_ref' => 'wrong', 'status' => 'unmatched']);
        $this->actingAs($this->borrower)->post(route('account.repay.confirm', 902), ['code' => 'UJL1CLAIM2', 'amount' => 1500])->assertRedirect();
        $this->assertSame('received', PaymentClaim::firstOrFail()->status);
        $this->assertSame('matched', MpesaTransaction::firstOrFail()->status);
    }

    public function test_pay_with_mpesa_sends_a_prompt_and_reports_the_callback(): void
    {
        Http::fake([
            '*/oauth/v1/generate*' => Http::response(['access_token' => 'tok', 'expires_in' => 3599]),
            '*/mpesa/stkpush/v1/processrequest' => Http::response(['CheckoutRequestID' => 'ws_CO_902', 'ResponseCode' => '0', 'CustomerMessage' => 'Success. Request accepted for processing']),
        ]);
        $this->actingAs($this->borrower)->post(route('account.repay.stk', 902), ['amount' => 2500])->assertRedirect()->assertSessionHas('stk_request');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'stkpush') && $r['PhoneNumber'] === '254712345678' && $r['Amount'] === 2500 && $r['AccountReference'] === '902');
        $t = MpesaTransaction::firstOrFail();
        $this->actingAs($this->borrower)->getJson(route('account.repay.stk.status', [902, $t->id]))->assertJson(['status' => 'pending']);

        $this->postJson('/mpesa/test-callback-secret-1234567890/stk', ['Body' => ['stkCallback' => ['CheckoutRequestID' => 'ws_CO_902', 'ResultCode' => 0,
            'CallbackMetadata' => ['Item' => [['Name' => 'Amount', 'Value' => 2500], ['Name' => 'MpesaReceiptNumber', 'Value' => 'UJL1STK902'],
                ['Name' => 'TransactionDate', 'Value' => 20261021100600], ['Name' => 'PhoneNumber', 'Value' => 254712345678]]]]]])->assertOk();
        $this->actingAs($this->borrower)->getJson(route('account.repay.stk.status', [902, $t->id]))->assertJson(['status' => 'matched', 'receipt' => 'UJL1STK902']);
    }

    public function test_another_borrower_cannot_read_the_prompt_status(): void
    {
        $t = MpesaTransaction::create(['type' => 'stk', 'checkout_request_id' => 'ws_CO_X', 'loan_id' => 902, 'amount' => 100, 'status' => 'pending']);
        $other = User::factory()->create();
        $other->assignRole('borrower');
        $this->actingAs($other)->getJson(route('account.repay.stk.status', [902, $t->id]))->assertNotFound();
    }
}
