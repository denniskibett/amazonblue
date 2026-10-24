<?php

namespace Tests\Feature\Money;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use App\Notifications\CustomerEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Stage 4 Phase 6: customer messages. */
class CustomerEventsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.notifications.enabled' => true, 'lending.mpesa.paybill' => '600100']);
        $this->loanTypes();
        $this->loanRow(1301, 1, 5000, 5000, '2026-10-10', '2026-10-20', 'disbursed', 1, 30);
        DB::table('users')->where('id', Loan::findOrFail(1301)->user_id)->update(['phone' => '+254 712 345678']);
        $this->cycleRow(1301, 1301, 1, 0, 1000, 6000, 20, '2026-10-10', '2026-10-20', 'active', 'Initial');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_cash_payment_tells_the_borrower_what_is_still_due(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-10-15 10:00');
        DB::transaction(fn () => Repayment::create(['loan_id' => 1301, 'loan_cycle_id' => 1301, 'amount' => 2000, 'repayment_date' => '2026-10-15', 'transaction' => 'UJLNOTIFY1']));
        DB::transaction(fn () => Repayment::create(['loan_id' => 1301, 'loan_cycle_id' => 1301, 'amount' => 500, 'repayment_date' => '2026-10-15', 'transaction' => 'CREDIT DISCOUNT']));

        Notification::assertSentTo(User::findOrFail(Loan::findOrFail(1301)->user_id), CustomerEvent::class,
            fn ($n, $ch) => $n->event === 'payment_received' && str_contains($n->sms, 'KES 2,000.00') && str_contains($n->sms, 'Still due: KES 4,000.00')
                && in_array(\App\Notifications\Channels\SmsChannel::class, $ch, true));
        Notification::assertSentTimes(CustomerEvent::class, 1); // the discount is a book entry, no message
    }

    public function test_reminders_go_three_days_before_on_the_day_and_when_late_never_at_night(): void
    {
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-17 09:00', 'Africa/Nairobi'));
        $this->artisan('notifications:reminders')->assertSuccessful();
        Notification::assertSentTimes(CustomerEvent::class, 1);
        Carbon::setTestNow(Carbon::parse('2026-10-21 09:00', 'Africa/Nairobi'));           // 1 day late
        $this->artisan('notifications:reminders')->assertSuccessful();
        Notification::assertSentTo(User::findOrFail(Loan::findOrFail(1301)->user_id), CustomerEvent::class, fn ($n) => $n->event === 'overdue' && str_contains($n->sms, 'Paybill 600100'));
        Carbon::setTestNow(Carbon::parse('2026-10-27 22:00', 'Africa/Nairobi'));
        $this->artisan('notifications:reminders')->expectsOutputToContain('Quiet hours')->assertSuccessful();
    }

    public function test_the_sms_goes_to_africas_talking_when_chosen(): void
    {
        config(['lending.notifications.sms.driver' => 'africastalking', 'lending.notifications.sms.africastalking.api_key' => 'k']);
        Http::fake(['*africastalking.com/*' => Http::response(['SMSMessageData' => ['Recipients' => [['statusCode' => 101, 'number' => '+254712345678']]]])]);
        $this->assertTrue(app(\App\Services\Sms\SmsSender::class)->send('0712 345 678', 'Hello'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.sandbox.africastalking.com/version1/messaging') && $r['to'] === '+254712345678');
    }

    public function test_the_sms_goes_to_kenyasms_with_the_configured_shape(): void
    {
        config(['lending.notifications.sms.driver' => 'kenyasms', 'lending.notifications.sms.kenyasms' => [
            'url' => 'https://api.kenyasms.test/v1/sms/send', 'api_key' => 'k', 'sender_id' => 'AIMZONE', 'auth' => 'bearer',
            'format' => 'json', 'field_to' => 'phone', 'field_message' => 'message', 'field_sender' => 'sender_id', 'phone_format' => '254']]);
        Http::fake(['api.kenyasms.test/*' => Http::sequence()->push(['status' => 'success', 'message_id' => 'abc'])->push(['status' => 'error', 'error' => 'Insufficient credit'])]);
        $this->assertTrue(app(\App\Services\Sms\SmsSender::class)->send('0712 345 678', 'Hello'));
        Http::assertSent(fn ($r) => $r->url() === 'https://api.kenyasms.test/v1/sms/send' && ($r->data()['phone'] ?? null) === '254712345678'
            && ($r->data()['message'] ?? null) === 'Hello' && ($r->data()['sender_id'] ?? null) === 'AIMZONE' && $r->hasHeader('Authorization', 'Bearer k'));

        // the key in the body, form-encoded, numbers as 07..., and a refusal is reported as not sent
        config(['lending.notifications.sms.kenyasms.auth' => 'field:apikey', 'lending.notifications.sms.kenyasms.format' => 'form',
            'lending.notifications.sms.kenyasms.phone_format' => '0', 'lending.notifications.sms.kenyasms.field_to' => 'mobile']);
        $this->assertFalse(app(\App\Services\Sms\SmsSender::class)->send('+254712345678', 'Hi'));
        Http::assertSent(fn ($r) => ($r->data()['mobile'] ?? null) === '0712345678' && ($r->data()['apikey'] ?? null) === 'k' && $r->isForm());
    }

    public function test_off_sends_nothing(): void
    {
        Notification::fake();
        config(['lending.notifications.enabled' => false]);
        DB::transaction(fn () => Repayment::create(['loan_id' => 1301, 'loan_cycle_id' => 1301, 'amount' => 2000, 'repayment_date' => '2026-10-15', 'transaction' => 'UJLNOTIFY2']));
        Notification::assertNothingSent();
    }
}
