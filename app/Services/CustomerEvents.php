<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\CustomerEvent;
use Illuminate\Support\Facades\Log;

/**
 * The customer messages (Stage 4 Phase 6, lending.notifications.enabled) and their copy, in one place.
 * Each is a service message about the customer's own loan or account.
 */
class CustomerEvents
{
    public static function enabled(): bool
    {
        return (bool) config('lending.notifications.enabled');
    }

    public function loan(string $event, Loan $loan, array $v = []): void
    {
        $user = User::find($loan->user_id);
        if (! $user || ! self::enabled()) {
            return;
        }
        $ref = 'loan ' . $loan->id;
        $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
        [$title, $body, $sms] = match ($event) {
            'approved' => ['Your loan is approved', "Your {$ref} for {$kes($loan->amount)} is approved. We'll send the money to your M-Pesa shortly.",
                "Aimzone: your {$ref} for {$kes($loan->amount)} is approved. We'll send it to your M-Pesa shortly."],
            'rejected' => ['Your application was not approved', "We couldn't approve your {$ref} this time. Your account shows the reason and what you can do next.",
                "Aimzone: we couldn't approve {$ref} this time. Log in to see why."],
            'money_sent' => ['Money sent to your M-Pesa', "We sent {$kes($v['amount'])} for your {$ref}. Repay by {$v['due']}.",
                "Aimzone: {$kes($v['amount'])} sent to your M-Pesa for {$ref}. Repay by {$v['due']}."],
            'payment_received' => ['Payment received', "We received {$kes($v['amount'])} for your {$ref} (ref {$v['ref']}). Still due: {$kes($v['owed'])}.",
                "Aimzone: received {$kes($v['amount'])} for {$ref}, ref {$v['ref']}. Still due: {$kes($v['owed'])}. Thank you."],
            'due_soon' => ['Repayment due in 3 days', "Your {$ref} has {$kes($v['owed'])} due on {$v['due']}.",
                "Aimzone: {$kes($v['owed'])} due on {$v['due']} for {$ref}. Pay to Paybill {$v['paybill']}, account {$loan->id}."],
            'due_today' => ['Repayment due today', "Your {$ref} has {$kes($v['owed'])} due today. Paying today avoids the late penalty.",
                "Aimzone: {$kes($v['owed'])} due TODAY for {$ref}. Paybill {$v['paybill']}, account {$loan->id}."],
            'overdue' => ['Your repayment is late', "Your {$ref} is {$v['days']} day(s) late; {$kes($v['owed'])} is due now and a penalty is added each day it stays unpaid.",
                "Aimzone: {$ref} is {$v['days']} day(s) late. {$kes($v['owed'])} due now; penalty is added daily. Paybill {$v['paybill']}, account {$loan->id}."],
            'request_decided' => [$v['approved'] ? 'Your request was agreed' : 'About your request', $v['text'], 'Aimzone: ' . $v['text']],
            default => [null, null, null],
        };
        if ($title) {
            $this->send($user, $event, $title, $body, $sms, route('account.loan', $loan));
        }
    }

    public function partner(string $event, ?User $user, float $amount): void
    {
        if (! $user || ! self::enabled()) {
            return;
        }
        $kes = 'KES ' . number_format($amount, 2);
        [$title, $body, $sms] = match ($event) {
            'funds_confirmed' => ['Your funds arrived', "We received {$kes} and added it to your capital.", "Aimzone: we received {$kes} and added it to your capital."],
            'withdrawal_paid' => ['Withdrawal paid', "We paid you {$kes}. It is on your statement.", "Aimzone: we paid you {$kes}. See your statement for details."],
            default => [null, null, null],
        };
        if ($title) {
            $this->send($user, $event, $title, $body, $sms, null);
        }
    }

    private function send(User $user, string $event, string $title, string $body, string $sms, ?string $url): void
    {
        try {
            $user->notify(new CustomerEvent($event, $title, $body, $sms, $url));
        } catch (\Throwable $e) {
            Log::warning('Customer notification not queued', ['user' => $user->id, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
