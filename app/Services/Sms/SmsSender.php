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
 *
 * Sender IDs (owner, 20 Nov 2026), as in scharge's Integrations::smsSender():
 *   the platform sender, Sharet Ent (SHARETENT): Integrations › Sharet Ent SMS (sysadmin), else .env
 *   the lender's own sender: the KenyaSMS card's sender ID (and, optionally, its own key). It is used only for the
 *     lender's own messages; under the white-label edition each lender's saved card is applied in that lender's
 *     context only (TenantContextBootstrapper), scheduled jobs included, so one client's name never reaches another's.
 *   one-time codes (sign-in, verification, password reset) always go out under the platform sender: otp().
 * KenyaSMS's Cloudflare blocks 185.113.249.146 until they whitelist it; sends fail and are logged meanwhile.
 */
class SmsSender
{
    /**
     * Who a message goes out as. $purpose 'otp' = always the platform; anything else = the lender's own sender ID
     * when it has one, else the platform.
     *
     * @return array{sender_id: string, api_key: ?string, platform: bool}
     */
    public static function sender(string $purpose = 'notice'): array
    {
        $c = (array) config('lending.notifications.sms');
        $platform = ['sender_id' => (string) (($c['platform']['sender_id'] ?? null) ?: 'SHARETENT'),
            'api_key' => ($c['platform']['api_key'] ?? null) ?: ($c['kenyasms']['api_key'] ?? null), 'platform' => true];
        $own = trim((string) ($c['kenyasms']['sender_id'] ?? ''));
        if ($purpose === 'otp' || $own === '' || strcasecmp($own, $platform['sender_id']) === 0) {
            return $platform;
        }

        return ['sender_id' => $own, 'api_key' => ($c['kenyasms']['api_key'] ?? null) ?: $platform['api_key'], 'platform' => false];
    }

    /** A one-time code: always under the platform sender (SHARETENT), never a lender's sender ID. */
    public function otp(string $phone, string $message): bool
    {
        return $this->send($phone, $message, 'otp');
    }

    public function send(string $phone, string $message, string $purpose = 'notice'): bool
    {
        $to = Daraja::msisdn($phone);
        if (! $to) {
            Log::warning('SMS not sent: no valid Kenyan number');

            return false;
        }
        $to = '+' . $to;
        $driver = (string) config('lending.notifications.sms.driver', 'log');

        if ($driver === 'kenyasms') {
            return $this->kenyaSms(substr($to, 1), $message, $purpose);
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

        Log::info('SMS (log driver)', ['to' => substr($to, 0, 6) . '•••' . substr($to, -3), 'from' => self::sender($purpose)['sender_id'],
            'message' => $purpose === 'otp' ? '(one-time code hidden)' : $message]);

        return true;
    }

    /** KenyaSMS: one message to one number. $msisdn is 2547XXXXXXXX. */
    private function kenyaSms(string $msisdn, string $message, string $purpose = 'notice'): bool
    {
        $c = (array) config('lending.notifications.sms.kenyasms');
        $from = self::sender($purpose);
        $c['sender_id'] = $from['sender_id'];
        $c['api_key'] = $from['api_key'];
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
