<?php

namespace Tests\Feature\Money;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\User;
use App\Notifications\BrandedEmail;
use App\Notifications\CustomerEvent;
use App\Notifications\LoanPaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Branded money emails (owner, 3 Nov 2026): money sent, each payment, paid in full; email only. */
class MoneyEmailsTest extends TestCase
{
    use RefreshDatabase, AuditLoanFixtures;

    private User $borrower;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lending.engine' => 'v2', 'lending.notifications.enabled' => false, 'lending.notifications.money_emails' => true]);
        $this->loanTypes();
        $this->loanRow(1401, 1, 5000, 5000, '2026-10-10', '2026-10-20', 'disbursed', 1, 30);
        $this->cycleRow(1401, 1401, 1, 0, 1000, 6000, 20, '2026-10-10', '2026-10-20', 'active', 'Initial');
        $this->borrower = User::findOrFail(Loan::findOrFail(1401)->user_id);
        Carbon::setTestNow('2026-10-15 10:00');
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_payment_sends_a_branded_receipt_with_what_is_still_due_and_no_sms(): void
    {
        DB::transaction(fn () => Repayment::create(['loan_id' => 1401, 'loan_cycle_id' => 1401, 'amount' => 2000, 'repayment_date' => '2026-10-15',
            'transaction' => 'UJLMONEY01', 'mode' => 'mpesa']));

        Notification::assertSentTo($this->borrower, BrandedEmail::class, function ($n) {
            $rows = collect($n->message['panel']['rows']);

            return $n->message['subject'] === 'Payment received — thank you' && $n->message['panel']['amount'] === 'KES 2,000.00'
                && $rows->contains(fn ($row) => $row[0] === 'Still due' && $row[1] === 'KES 4,000.00');
        });
        Notification::assertNotSentTo($this->borrower, CustomerEvent::class);
    }

    public function test_book_entries_and_old_catch_up_entries_send_nothing(): void
    {
        DB::transaction(fn () => Repayment::create(['loan_id' => 1401, 'loan_cycle_id' => 1401, 'amount' => 500, 'repayment_date' => '2026-10-15', 'transaction' => 'CREDIT DISCOUNT']));
        DB::transaction(fn () => Repayment::create(['loan_id' => 1401, 'loan_cycle_id' => 1401, 'amount' => 500, 'repayment_date' => '2026-09-01', 'transaction' => 'UJLOLD0001', 'mode' => 'cash']));

        Notification::assertNothingSent();
    }

    public function test_money_sent_is_emailed(): void
    {
        DB::transaction(fn () => Disbursement::create(['loan_id' => 1401, 'loan_cycle_id' => 1401, 'amount' => 5000, 'net_amount' => 5000, 'processing_fee' => 0,
            'disburse_date' => '2026-10-15', 'payment_date' => '2026-10-15', 'transaction' => 'UJLSENT001', 'mode' => 'bank_to_mpesa']));

        Notification::assertSentTo($this->borrower, BrandedEmail::class,
            fn ($n) => $n->message['subject'] === 'KES 5,000.00 is on its way' && str_contains($n->message['preheader'], 'M-Pesa'));
    }

    public function test_paying_in_full_sends_the_closing_email_once(): void
    {
        DB::transaction(fn () => Repayment::create(['loan_id' => 1401, 'loan_cycle_id' => 1401, 'amount' => 6000, 'repayment_date' => '2026-10-15',
            'transaction' => 'UJLFULL001', 'mode' => 'mpesa']));
        DB::table('loans')->where('id', 1401)->update(['status' => 'repaid']);
        // a second tiny payment after the loan closed does not send it again
        app(\App\Services\MoneyEmails::class)->paid(Repayment::where('transaction', 'UJLFULL001')->firstOrFail());
        app(\App\Services\MoneyEmails::class)->paid(Repayment::where('transaction', 'UJLFULL001')->firstOrFail());

        Notification::assertSentToTimes($this->borrower, LoanPaid::class, 1);
    }
}
