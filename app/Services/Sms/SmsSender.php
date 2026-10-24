<?php

namespace App\Services\Sms;

use App\Services\Mpesa\Daraja;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one SMS (Stage 4 Phase 6). Drivers (lending.notifications.sms.driver):
 *   log            writes the message to the log only (default until the owner picks a provider)
 *   africastalking Africa's Talking bulk SMS (username "sandbox" = their sandbox)
 *   kenyasms       KenyaSMS (owner, 24 Oct 2026); its URL, key placement and field names are settings
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

        if ($driver === 'kenyasms') {
            return $this->kenyaSms(substr($to, 1), $message);
        }

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

    /** KenyaSMS: one message to one number. $msisdn is 2547XXXXXXXX. */
    private function kenyaSms(string $msisdn, string $message): bool
    {
        $c = (array) config('lending.notifications.sms.kenyasms');
        if (empty($c['url']) || empty($c['api_key'])) {
            Log::warning('SMS not sent: KENYASMS_URL and KENYASMS_API_KEY are not set');

            return false;
        }
        $phone = match ((string) ($c['phone_format'] ?? '254')) {
            '+254' => '+' . $msisdn,
            '0' => '0' . substr($msisdn, 3),
            default => $msisdn,
        };
        $body = array_filter([
            $c['field_to'] ?? 'phone' => $phone,
            $c['field_message'] ?? 'message' => $message,
            $c['field_sender'] ?? 'sender_id' => $c['sender_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $request = Http::acceptJson()->timeout(20);
        $auth = (string) ($c['auth'] ?? 'bearer');
        if (str_starts_with($auth, 'header:')) {
            $request = $request->withHeaders([substr($auth, 7) => (string) $c['api_key']]);
        } elseif (str_starts_with($auth, 'field:')) {
            $body[substr($auth, 6)] = (string) $c['api_key'];
        } else {
            $request = $request->withToken((string) $c['api_key']);
        }

        try {
            $r = ($c['format'] ?? 'json') === 'form' ? $request->asForm()->post($c['url'], $body) : $request->post($c['url'], $body);
        } catch (\Throwable $e) {
            Log::warning('SMS to KenyaSMS failed', ['error' => mb_substr($e->getMessage(), 0, 200)]);

            return false;
        }
        $json = (array) ($r->json() ?? []);
        // a 2xx reply that does not say it failed counts as accepted
        $failed = ($json['success'] ?? null) === false || in_array(strtolower((string) ($json['status'] ?? '')), ['error', 'failed', 'fail'], true)
            || (! empty($json['error']) && empty($json['message_id']));
        $ok = $r->successful() && ! $failed;
        if (! $ok) {
            Log::warning('SMS rejected by KenyaSMS', ['status' => $r->status(), 'body' => mb_substr($r->body(), 0, 300)]);
        }

        return $ok;
    }
}
