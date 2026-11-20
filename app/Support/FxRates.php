<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sharet's FX reference rates: the one rate source for every converted figure (ported from scharge's FxRates).
 * Sharet publishes Safaricom's M-PESA rate for the day as units of X per 1 KES. We cache them and keep the last
 * good set on disk; if Sharet can't be reached the last known set is used and marked stale; with none at all,
 * figures stay in their own currency rather than being guessed. Display only.
 */
class FxRates
{
    public const CACHE_KEY = 'fx.rates.v1';

    /** ['base' => 'KES', 'date' => 'Y-m-d', 'rates' => ['USD' => 0.0077, ...], 'stale' => bool] or null. */
    public static function snapshot(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        return is_array($cached) ? $cached['snapshot'] : self::refresh();
    }

    public static function refresh(): ?array
    {
        $snap = self::fetch();
        if ($snap) {
            self::store($snap);
            Cache::put(self::CACHE_KEY, ['snapshot' => $snap], now()->addMinutes((int) config('currency.rates.cache_minutes', 360)));

            return $snap;
        }
        $last = self::lastKnown();
        if ($last) {
            $last['stale'] = true;
        }
        Cache::put(self::CACHE_KEY, ['snapshot' => $last], now()->addMinutes((int) config('currency.rates.retry_minutes', 15)));

        return $last;
    }

    /** Units of $code per 1 book currency, or null without a rate. */
    public static function perBook(string $code): ?float
    {
        $code = strtoupper($code);
        if ($code === Currency::book()) {
            return 1.0;
        }
        $r = self::snapshot()['rates'][$code] ?? null;

        return is_numeric($r) && (float) $r > 0 ? (float) $r : null;
    }

    /** Units of $to per 1 $from (crossed through the book currency), or null. */
    public static function rate(string $from, string $to): ?float
    {
        if (strtoupper($from) === strtoupper($to)) {
            return 1.0;
        }
        $a = self::perBook($from);
        $b = self::perBook($to);

        return $a !== null && $b !== null ? $b / $a : null;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function fetch(): ?array
    {
        $url = (string) config('currency.rates.url');
        if ($url === '') {
            return null;
        }
        try {
            $http = Http::acceptJson()->timeout((int) config('currency.rates.timeout', 5));
            if (filled(config('sharet.api_key'))) {
                $http = $http->withToken((string) config('sharet.api_key'));
            }
            $res = $http->get($url);
            if (! $res->successful()) {
                Log::info('fx: Sharet rates unavailable', ['status' => $res->status()]);

                return null;
            }

            return self::parse((array) $res->json());
        } catch (\Throwable $e) {
            Log::info('fx: Sharet rates unreachable', ['error' => mb_substr($e->getMessage(), 0, 200)]);

            return null;
        }
    }

    /** Accepts {"base":"KES","rates":{..}} or {"pairs":{"KES/USD":..}} (also wrapped in "data"); X/KES pairs are inverted. */
    public static function parse(array $body): ?array
    {
        $body = isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
        $book = Currency::book();
        if (strtoupper((string) ($body['base'] ?? $book)) !== $book) {
            return null;
        }
        $rates = [];
        foreach ((array) ($body['rates'] ?? []) as $code => $r) {
            $rates[strtoupper((string) $code)] = $r;
        }
        foreach ((array) ($body['pairs'] ?? []) as $pair => $r) {
            $r = is_array($r) ? ($r['rate'] ?? null) : $r;
            [$b, $q] = array_pad(explode('/', strtoupper((string) $pair)), 2, '');
            if (! is_numeric($r) || (float) $r <= 0) {
                continue;
            }
            if ($b === $book) {
                $rates[$q] = (float) $r;
            } elseif ($q === $book) {
                $rates[$b] = 1 / (float) $r;
            }
        }
        $rates = array_filter($rates, fn ($r, $c) => Currency::supported($c) && $c !== $book && is_numeric($r) && (float) $r > 0, ARRAY_FILTER_USE_BOTH);
        if (! $rates) {
            return null;
        }
        $date = (string) ($body['date'] ?? $body['rate_date'] ?? now('Africa/Nairobi')->toDateString());

        return ['base' => $book, 'date' => preg_match('/^\d{4}-\d{2}-\d{2}/', $date) ? substr($date, 0, 10) : now()->toDateString(),
            'rates' => array_map('floatval', $rates), 'source' => (string) ($body['source'] ?? 'sharet'), 'fetched_at' => now()->toIso8601String(), 'stale' => false];
    }

    public static function lastKnown(): ?array
    {
        $path = (string) config('currency.rates.store');
        $data = $path !== '' && is_file($path) ? json_decode((string) @file_get_contents($path), true) : null;

        return is_array($data) && isset($data['base'], $data['rates']) && $data['base'] === Currency::book() ? $data : null;
    }

    protected static function store(array $snap): void
    {
        $path = (string) config('currency.rates.store');
        if ($path !== '') {
            rescue(function () use ($path, $snap) {
                @mkdir(dirname($path), 0755, true);
                file_put_contents($path . '.tmp', json_encode($snap, JSON_PRETTY_PRINT));
                rename($path . '.tmp', $path);
            }, null, false);
        }
    }
}
