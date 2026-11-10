<?php

namespace App\Services\Growth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * This site's numbers from the Sharet growth hub (GET /api/v1/growth/summary, "growth.summary" v1), read with the
 * Sharet API key already saved on /staff/integrations › Sharet Pay (scope read). The hub answers for this site only.
 * Never throws: every failure becomes a state the Growth page can explain. Good answers are cached 5 minutes.
 */
class GrowthHub
{
    public const SITE = 'loans';

    public const PERIODS = [7, 30, 90];

    /**
     * @return array{state: string, message: ?string, site: ?array, from: string, to: string, generated_at: ?string}
     *   state: ok | not_connected | unauthorized | forbidden | unavailable | timeout | error
     */
    public function summary(int $days): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : 30;
        $to = Carbon::today('Africa/Nairobi');
        $from = $to->copy()->subDays($days - 1);
        $base = ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'site' => null, 'message' => null, 'generated_at' => null];

        // the key saved on the Sharet Pay card (read even while the card's switch is off), else the applied config
        $saved = rescue(fn () => \App\Support\Integrations::saved('sharet'), [], false);
        $key = (string) (($saved['SHARET_API_KEY'] ?? '') ?: config('sharet.api_key'));
        if ($key === '') {
            return ['state' => 'not_connected'] + $base;
        }

        $cacheKey = 'growth:summary:' . substr(hash('sha256', $key), 0, 16) . ":{$base['from']}:{$base['to']}";
        if (is_array($hit = Cache::get($cacheKey))) {
            return $hit;
        }

        $url = rtrim((string) (($saved['SHARET_BASE_URL'] ?? '') ?: config('sharet.base_url') ?: 'https://sharet.africa/api/v1'), '/') . '/growth/summary';
        try {
            $response = Http::withToken($key)->acceptJson()->timeout(8)->connectTimeout(4)
                ->get($url, ['from' => $base['from'], 'to' => $base['to']]);
        } catch (ConnectionException) {
            return ['state' => 'timeout'] + $base;
        } catch (\Throwable $e) {
            Log::warning('Growth hub request failed: ' . $e->getMessage());

            return ['state' => 'error'] + $base;
        }

        $problem = fn () => (string) ($response->json('detail') ?? $response->json('title') ?? '');
        $state = match (true) {
            $response->status() === 401 => 'unauthorized',
            $response->status() === 403 => 'forbidden',
            in_array($response->status(), [502, 503, 504], true) => 'unavailable',
            ! $response->successful() => 'error',
            default => 'ok',
        };
        if ($state !== 'ok') {
            return ['state' => $state, 'message' => $problem() ?: null] + $base;
        }

        $sites = $response->json('sites');
        if (! is_array($sites)) {
            return ['state' => 'error', 'message' => 'The hub sent an answer this page does not understand.'] + $base;
        }
        $site = collect($sites)->firstWhere('key', self::SITE) ?? ($sites[0] ?? null);

        $result = [
            'state' => 'ok',
            'site' => is_array($site) ? $this->normalise($site) : null,
            'from' => (string) ($response->json('from') ?? $base['from']),
            'to' => (string) ($response->json('to') ?? $base['to']),
            'generated_at' => $response->json('generated_at'),
            'message' => null,
        ];
        Cache::put($cacheKey, $result, now()->addMinutes(5));

        return $result;
    }

    /** Fill every part the page reads, so a partial answer never breaks it. */
    private function normalise(array $site): array
    {
        $totals = (array) ($site['totals'] ?? []);

        return [
            'name' => (string) ($site['name'] ?? 'Sharet Loans'),
            'url' => (string) ($site['url'] ?? url('/')),
            'last_data_at' => $site['last_data_at'] ?? null,
            'totals' => [
                'visits' => (int) ($totals['visits'] ?? 0),
                'page_views' => (int) ($totals['page_views'] ?? 0),
                'conversions' => (int) ($totals['conversions'] ?? 0),
                'conversion_rate' => isset($totals['conversion_rate']) ? (float) $totals['conversion_rate'] : null,
                'organic_share' => isset($totals['organic_share']) ? (float) $totals['organic_share'] : null,
                'paid_share' => isset($totals['paid_share']) ? (float) $totals['paid_share'] : null,
            ],
            'daily' => array_values(array_filter((array) ($site['daily'] ?? []), 'is_array')),
            'conversions' => array_values(array_filter((array) ($site['conversions'] ?? []), 'is_array')),
            'channels' => array_values(array_filter((array) ($site['channels'] ?? []), 'is_array')),
            'sources' => array_values(array_filter((array) ($site['sources'] ?? []), 'is_array')),
            'landing_pages' => array_values(array_filter((array) ($site['landing_pages'] ?? []), 'is_array')),
            'seo' => [
                'checked_at' => $site['seo']['checked_at'] ?? null,
                'sitemap_urls' => $site['seo']['sitemap_urls'] ?? null,
                'counts' => (array) ($site['seo']['counts'] ?? []),
                'fix_first' => array_values(array_filter((array) ($site['seo']['fix_first'] ?? []), 'is_array')),
            ],
        ];
    }
}
