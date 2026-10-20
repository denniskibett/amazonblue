<?php

namespace App\Services\Sms;

use App\Services\Mpesa\Daraja;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one SMS (Stage 4 Phase 6). Drivers (lending.notifications.sms.driver):
 *   log            writes the message to the log only (default until the owner picks a provider)
 *   africastalking Africa's Talking bulk SMS (username "sandbox" = their sandbox)
 * Returns true when the provider accepted it. Phone numbers are normalised to +2547XXXXXXXX.
 */
class SmsSender
{
    public function send(string $phone, string $message): bool
    {
        $to = Daraja::msisdn($phone);
        if (! $to) {
            Log::warning('SMS not sent: no valid Kenyan number');

            return false;
        }
        $to = '+' . $to;
        $driver = (string) config('lending.notifications.sms.driver', 'log');

        if ($driver === 'africastalking') {
            $c = config('lending.notifications.sms.africastalking');
            $base = ($c['username'] ?? '') === 'sandbox' ? 'https://api.sandbox.africastalking.com' : 'https://api.africastalking.com';
            $r = Http::asForm()->acceptJson()->withHeaders(['apiKey' => (string) ($c['api_key'] ?? '')])->timeout(20)
                ->post($base . '/version1/messaging', array_filter(['username' => $c['username'] ?? null, 'to' => $to, 'message' => $message, 'from' => $c['sender_id'] ?? null]));
            $ok = $r->successful() && collect($r->json('SMSMessageData.Recipients', []))->contains(fn ($x) => in_array((int) ($x['statusCode'] ?? 0), [100, 101, 102], true));
            if (! $ok) {
                Log::warning('SMS rejected by Africa\'s Talking', ['status' => $r->status(), 'body' => mb_substr($r->body(), 0, 300)]);
            }

            return $ok;
        }

        Log::info('SMS (log driver)', ['to' => substr($to, 0, 6) . '•••' . substr($to, -3), 'message' => $message]);

        return true;
    }
}
