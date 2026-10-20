<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\MpesaTransaction;
use App\Models\PaymentClaim;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Stage 4 Phase 4: Daraja callbacks and the single recording path.
 */
class MpesaCallbackTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private const SECRET = 'test-callback-secret-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.mpesa.enabled' => true, 'lending.mpesa.callback_secret' => self::SECRET,
            'lending.ledger.enabled' => false, 'lending.split.enabled' => false]);
        $this->loanTypes();
        Carbon::setTestNow('2026-10-21 10:00');
        $this->loanRow(901, 1, 5000, 5000, '2026-10-15', '2026-10-25', 'disbursed', 1, 30);
        DB::table('users')->where('id', DB::table('loans')->where('id', 901)->value('user_id'))->update(['phone' => '+254 712 345678']);
        $this->cycleRow(901, 901, 1, 0, 1000, 6000, 20, '2026-10-15', '2026-10-25', 'active', 'Initial');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function c2b(array $over = [])
    {
        return $this->postJson('/mpesa/' . self::SECRET . '/c2b/confirmation', $over + [
            'TransactionType' => 'Pay Bill', 'TransID' => 'UJL1ABCDE1', 'TransTime' => '20261021093000', 'TransAmount' => '2500.00',
            'BusinessShortCode' => '174379', 'BillRefNumber' => '901', 'MSISDN' => '254712345678', 'FirstName' => 'JANE',
        ]);
    }

    public function test_a_paybill_payment_to_an_open_loan_from_the_borrowers_phone_is_recorded(): void
    {
        $this->c2b()->assertOk()->assertJson(['ResultCode' => 0]);

        $t = MpesaTransaction::firstOrFail();
        $this->assertSame('matched', $t->status);
        $r = Repayment::findOrFail($t->repayment_id);
        $this->assertSame('UJL1ABCDE1', $r->transaction);
        $this->assertSame('mpesa', $r->mode);
        $this->assertSame('cash', $r->kind);
        $this->assertEquals(2500, (float) $r->amount);
        $this->assertSame('2026-10-21', $r->repayment_date->toDateString());
        $this->assertStringNotContainsString('712345678', json_encode($t->payload), 'phone masked in the stored payload');
    }

    public function test_a_repeated_callback_records_once(): void
    {
        $this->c2b()->assertOk();
        $this->c2b()->assertOk();
        $this->assertSame(1, MpesaTransaction::count());
        $this->assertSame(1, Repayment::count());
    }

    public function test_a_wrong_account_number_or_another_phone_waits_for_staff(): void
    {
        $this->c2b(['TransID' => 'UJL1ABCDE2', 'BillRefNumber' => 'JANE'])->assertOk();
        $this->c2b(['TransID' => 'UJL1ABCDE3', 'MSISDN' => '254799999999'])->assertOk();
        $this->assertSame(['unmatched', 'unmatched'], MpesaTransaction::orderBy('id')->pluck('status')->all());
        $this->assertSame(0, Repayment::count());

        $staff = User::factory()->create();
        app(\App\Services\Mpesa\PaymentRecorder::class)->assign(MpesaTransaction::where('trans_id', 'UJL1ABCDE3')->first(), Loan::findOrFail(901), $staff);
        $this->assertSame($staff->id, (int) Repayment::firstOrFail()->created_by);
    }

    public function test_a_hidden_phone_is_accepted_only_up_to_what_is_owed(): void
    {
        $this->c2b(['TransID' => 'UJL1ABCDE4', 'MSISDN' => 'a3f9c2...hashed', 'TransAmount' => '6000.00'])->assertOk();
        $this->c2b(['TransID' => 'UJL1ABCDE5', 'MSISDN' => 'a3f9c2...hashed', 'TransAmount' => '100.00'])->assertOk();
        $this->assertSame('matched', MpesaTransaction::where('trans_id', 'UJL1ABCDE4')->value('status'));
        $this->assertSame('unmatched', MpesaTransaction::where('trans_id', 'UJL1ABCDE5')->value('status'), 'nothing owed any more');
        $this->assertSame('repaid', Loan::findOrFail(901)->status);
    }

    public function test_a_receipt_typed_by_hand_is_never_recorded_twice(): void
    {
        $this->c2b()->assertOk();
        $this->expectException(ValidationException::class);
        Repayment::create(['loan_id' => 901, 'loan_cycle_id' => 901, 'amount' => 2500, 'repayment_date' => '2026-10-21', 'transaction' => 'UJL1ABCDE1', 'kind' => 'cash']);
    }

    public function test_a_receipt_typed_before_the_callback_is_linked_not_doubled_and_the_claim_reads_received(): void
    {
        $user = User::findOrFail(Loan::findOrFail(901)->user_id);
        $claim = PaymentClaim::create(['user_id' => $user->id, 'loan_id' => 901, 'code' => 'UJL1ABCDE1', 'amount' => 2500, 'status' => 'checking']);
        Repayment::create(['loan_id' => 901, 'loan_cycle_id' => 901, 'amount' => 2500, 'repayment_date' => '2026-10-21', 'transaction' => 'UJL1ABCDE1', 'kind' => 'cash']);
        $this->assertSame('received', $claim->fresh()->status);

        $this->c2b()->assertOk();
        $this->assertSame(1, Repayment::count());
        $this->assertSame('matched', MpesaTransaction::firstOrFail()->status);
    }

    public function test_stk_success_records_and_cancel_does_not(): void
    {
        MpesaTransaction::create(['type' => 'stk', 'checkout_request_id' => 'ws_CO_1', 'loan_id' => 901, 'amount' => 3000, 'status' => 'pending']);
        MpesaTransaction::create(['type' => 'stk', 'checkout_request_id' => 'ws_CO_2', 'loan_id' => 901, 'amount' => 3000, 'status' => 'pending']);
        $ok = ['Body' => ['stkCallback' => ['CheckoutRequestID' => 'ws_CO_1', 'ResultCode' => 0, 'ResultDesc' => 'Processed',
            'CallbackMetadata' => ['Item' => [['Name' => 'Amount', 'Value' => 3000], ['Name' => 'MpesaReceiptNumber', 'Value' => 'UJL1STK001'],
                ['Name' => 'TransactionDate', 'Value' => 20261021094500], ['Name' => 'PhoneNumber', 'Value' => 254712345678]]]]]];
        $cancel = ['Body' => ['stkCallback' => ['CheckoutRequestID' => 'ws_CO_2', 'ResultCode' => 1032, 'ResultDesc' => 'Request cancelled by user']]];

        $this->postJson('/mpesa/' . self::SECRET . '/stk', $ok)->assertOk();
        $this->postJson('/mpesa/' . self::SECRET . '/stk', $ok)->assertOk();     // repeated
        $this->postJson('/mpesa/' . self::SECRET . '/stk', $cancel)->assertOk();

        $this->assertSame(1, Repayment::where('transaction', 'UJL1STK001')->count());
        $this->assertSame('failed', MpesaTransaction::where('checkout_request_id', 'ws_CO_2')->value('status'));
    }

    public function test_with_automatic_recording_off_the_payment_is_kept_for_staff(): void
    {
        config(['lending.mpesa.enabled' => false]);
        $this->c2b()->assertOk();
        $this->assertSame('unmatched', MpesaTransaction::firstOrFail()->status);
        $this->assertSame(0, Repayment::count());
    }

    public function test_a_wrong_secret_or_ip_is_refused(): void
    {
        $this->postJson('/mpesa/wrong/c2b/confirmation', [])->assertNotFound();
        config(['lending.mpesa.allowed_ips' => ['196.201.214.200']]);
        $this->c2b()->assertForbidden();
        $this->assertSame(0, MpesaTransaction::count());
    }

    public function test_production_with_the_sandbox_shortcode_is_refused(): void
    {
        config(['lending.mpesa.env' => 'production', 'lending.mpesa.consumer_key' => 'k', 'lending.mpesa.consumer_secret' => 's', 'lending.mpesa.shortcode' => '174379']);
        $this->assertStringContainsString('sandbox shortcode', app(\App\Services\Mpesa\Daraja::class)->problem());
        $this->assertSame('254712345678', \App\Services\Mpesa\Daraja::msisdn('+254 712 345678'));
        $this->assertSame('254712345678', \App\Services\Mpesa\Daraja::msisdn('0712345678'));
    }
}
