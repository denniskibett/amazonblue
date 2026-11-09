<?php

namespace App\Services\Sharet;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Sharet API v1 (same as scharge's). Auth: "Authorization: Bearer sk_…".
 * Calls that move money send an Idempotency-Key: Sharet replays the first answer for a repeated key and
 * forgets keys whose call was refused (4xx/5xx), so the same key is safe to send again.
 */
class SharetClient
{
    public const WEBHOOK_EVENTS = [
        'payment_request.paid', 'payment_request.expired', 'payment_request.cancelled',
        'payment.paid', 'payment.failed', 'payment.refunded',
    ];

    public string $environment;

    public function __construct(private string $apiKey, private string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->environment = str_starts_with($apiKey, 'sk_live_') ? 'live' : 'test';
    }

    /** The client for the key saved on Integrations (or .env). */
    public static function make(): self
    {
        return new self((string) config('sharet.api_key'), (string) (config('sharet.base_url') ?: 'https://sharet.africa/api/v1'));
    }

    public function get(string $path, array $query = []): Response
    {
        return $this->http()->get($this->baseUrl . $path, $query);
    }

    public function post(string $path, array $body = [], ?string $idempotencyKey = null): Response
    {
        $http = $this->http();
        if ($idempotencyKey) {
            $http = $http->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        return $http->post($this->baseUrl . $path, $body);
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->apiKey)->acceptJson()->asJson()->timeout(20);
    }

    /**
     * Verify a webhook: header "Sharet-Signature: t=<unix>,v1=<hex>" where
     * v1 = HMAC-SHA256(secret, "<t>.<raw body>"), within $tolerance seconds.
     */
    public static function verifySignature(?string $header, string $payload, ?string $secret, int $tolerance = 300): bool
    {
        if (! $header || ! $secret) {
            return false;
        }
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, null);
            if ($k !== null && $v !== null) {
                $parts[$k][] = $v;
            }
        }
        $t = (int) ($parts['t'][0] ?? 0);
        if (! $t || abs(time() - $t) > $tolerance) {
            return false;
        }
        $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
        foreach ($parts['v1'] ?? [] as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
