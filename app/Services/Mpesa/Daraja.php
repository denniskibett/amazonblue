<?php

namespace App\Services\Mpesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Safaricom Daraja API (Stage 4 Phase 4): OAuth token, Lipa na M-Pesa online (STK push) and C2B URL
 * registration. Sandbox until the owner sets MPESA_ENV=production with the production keys, shortcode and
 * passkey. Credentials come from config (.env) only.
 */
class Daraja
{
    public const SANDBOX_SHORTCODE = '174379';

    public function env(): string
    {
        return config('lending.mpesa.env') === 'production' ? 'production' : 'sandbox';
    }

    public function baseUrl(): string
    {
        return rtrim(config('lending.mpesa.base_url.' . $this->env()), '/');
    }

    /**
     * Why the integration can't run, or null when it can. Refuses production with the sandbox shortcode or
     * passkey, and any environment without keys or a callback secret.
     */
    public function problem(): ?string
    {
        if (! config('lending.mpesa.consumer_key') || ! config('lending.mpesa.consumer_secret')) {
            return 'MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET are not set.';
        }
        if (! config('lending.mpesa.callback_secret') || strlen((string) config('lending.mpesa.callback_secret')) < 16) {
            return 'MPESA_CALLBACK_SECRET must be set (16+ random characters).';
        }
        if ($this->env() === 'production' && (string) config('lending.mpesa.shortcode') === self::SANDBOX_SHORTCODE) {
            return 'MPESA_ENV=production with the sandbox shortcode 174379: set the live paybill (MPESA_SHORTCODE) and passkey.';
        }

        return null;
    }

    public function token(): string
    {
        return Cache::remember('mpesa-token-' . $this->env(), 3300, function () {
            $r = Http::withBasicAuth((string) config('lending.mpesa.consumer_key'), (string) config('lending.mpesa.consumer_secret'))
                ->timeout(20)->get($this->baseUrl() . '/oauth/v1/generate', ['grant_type' => 'client_credentials'])->throw();

            return (string) $r->json('access_token');
        });
    }

    /** The callback URL for one hook, with the secret path segment. */
    public function callbackUrl(string $hook): string
    {
        $base = rtrim((string) (config('lending.mpesa.callback_base') ?: config('app.url')), '/');

        return $base . '/mpesa/' . config('lending.mpesa.callback_secret') . '/' . $hook;
    }

    /**
     * STK push: asks the phone's owner to approve a payment to the shortcode. Returns Daraja's reply
     * (CheckoutRequestID, ResponseCode, CustomerMessage...). The amount here is only a request: only the
     * callback that confirms it counts.
     */
    public function stkPush(string $phone, int $amount, string $accountRef, string $description): array
    {
        $shortcode = (string) config('lending.mpesa.shortcode');
        $timestamp = now('Africa/Nairobi')->format('YmdHis');

        return Http::withToken($this->token())->timeout(30)->post($this->baseUrl() . '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode . config('lending.mpesa.passkey') . $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => config('lending.mpesa.transaction_type', 'CustomerPayBillOnline'),
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl('stk'),
            'AccountReference' => mb_substr($accountRef, 0, 12),
            'TransactionDesc' => mb_substr($description, 0, 13),
        ])->throw()->json();
    }

    /** Registers the C2B validation and confirmation URLs for the shortcode. */
    public function registerC2bUrls(): array
    {
        $version = $this->env() === 'production' ? 'v2' : 'v1';

        return Http::withToken($this->token())->timeout(30)->post($this->baseUrl() . "/mpesa/c2b/{$version}/registerurl", [
            'ShortCode' => (string) config('lending.mpesa.shortcode'),
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $this->callbackUrl('c2b/confirmation'),
            'ValidationURL' => $this->callbackUrl('c2b/validation'),
        ])->throw()->json();
    }

    /** Why B2C payouts can't run, or null when they can (owner, 24 Oct 2026). */
    public function b2cProblem(): ?string
    {
        if ($p = $this->problem()) {
            return $p;
        }
        foreach (['b2c_shortcode' => 'MPESA_B2C_SHORTCODE', 'b2c_initiator' => 'MPESA_B2C_INITIATOR', 'b2c_security_credential' => 'MPESA_B2C_SECURITY_CREDENTIAL'] as $key => $env) {
            if (! config('lending.mpesa.' . $key)) {
                return $env . ' is not set.';
            }
        }

        return null;
    }

    /**
     * B2C payment request: sends money from the B2C shortcode to a phone. Returns Daraja's reply
     * (ConversationID, OriginatorConversationID, ResponseCode). Only the result callback says whether it was paid.
     */
    public function b2c(string $phone, int $amount, string $remarks, string $occasion): array
    {
        return Http::withToken($this->token())->timeout(30)->post($this->baseUrl() . '/mpesa/b2c/v1/paymentrequest', [
            'InitiatorName' => (string) config('lending.mpesa.b2c_initiator'),
            'SecurityCredential' => (string) config('lending.mpesa.b2c_security_credential'),
            'CommandID' => (string) config('lending.mpesa.b2c_command', 'BusinessPayment'),
            'Amount' => $amount,
            'PartyA' => (string) config('lending.mpesa.b2c_shortcode'),
            'PartyB' => $phone,
            'Remarks' => mb_substr($remarks, 0, 100),
            'QueueTimeOutURL' => $this->callbackUrl('b2c/timeout'),
            'ResultURL' => $this->callbackUrl('b2c/result'),
            'Occasion' => mb_substr($occasion, 0, 100),
        ])->throw()->json();
    }

    /** Kenyan phone to Daraja's 2547XXXXXXXX form, or null. */
    public static function msisdn(?string $phone): ?string
    {
        $d = preg_replace('/\D/', '', (string) $phone);
        if (preg_match('/^(?:254|0)?([17]\d{8})$/', $d, $m)) {
            return '254' . $m[1];
        }

        return null;
    }
}
