<?php

namespace App\Services\Funding;

use App\Models\Funding\FundingEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every open and click of a funding cold email: when, roughly where (town) and on what device or mail app, so the
 * account manager calls at the time and on the channel the prospect actually reads. Ported from scharge
 * (app/Modules/CRM/Support/EmailEngagement.php).
 *
 * Town comes from an offline IP database when the server has one (App\Services\Analytics\GeoIp with
 * storage/app/geo/dbip-city-lite.mmdb, as in scharge); without it, only the country from a CDN header is kept.
 * Mail-app image proxies hide the reader: Gmail fetches the image when the email is opened (time real, place
 * Google's); Apple Mail Privacy Protection may fetch it in the background. Those rows are marked in `via` and carry
 * no place. Privacy: only a shortened IP is stored, town at most, and rows older than 12 months are pruned.
 */
class EmailEngagement
{
    public const KEEP_MONTHS = 12;

    public const TZ = 'Africa/Nairobi';

    /** Record one open or click and mark the email (never throws: tracking must not break the image or the link). */
    public static function record(?FundingEmail $email, string $kind, Request $request): void
    {
        if (! $email || ! in_array($kind, ['open', 'click'], true)) {
            return;
        }
        try {
            $ip = (string) $request->ip();
            $agent = self::agent((string) $request->userAgent(), $ip);
            $place = $agent['via'] === 'direct' ? self::locate($ip, $request->header('CF-IPCountry')) : [];
            DB::table('funding_email_events')->insert([
                'email_id' => $email->id, 'prospect_id' => $email->prospect_id, 'kind' => $kind, 'occurred_at' => now(),
                'via' => $agent['via'], 'ip_prefix' => self::prefix($ip),
                'country' => $place['country_code'] ?? null, 'region' => self::cut($place['region'] ?? null), 'city' => self::cut($place['city'] ?? null),
                'device' => $agent['device'], 'os' => $agent['os'], 'client' => $agent['client'], 'created_at' => now(),
            ]);
            if ($kind === 'open') {
                $email->forceFill(['opens' => $email->opens + 1, 'opened_at' => $email->opened_at ?? now()])->saveQuietly();
            } else {
                // a click means it was opened too, even if images were blocked
                $email->forceFill(['clicked_at' => $email->clicked_at ?? now(), 'opened_at' => $email->opened_at ?? now()])->saveQuietly();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{country_code: ?string, region: ?string, city: ?string} */
    public static function locate(string $ip, ?string $cdnCountry = null): array
    {
        $geo = 'App\\Services\\Analytics\\GeoIp';
        if (class_exists($geo) && class_exists('MaxMind\\Db\\Reader')) {
            return app($geo)->locate($ip, $cdnCountry);
        }
        $cdn = strtoupper((string) $cdnCountry);

        return ['country_code' => preg_match('/^[A-Z]{2}$/', $cdn) && $cdn !== 'XX' ? $cdn : null, 'region' => null, 'city' => null];
    }

    /** @return array{via: string, device: ?string, os: ?string, client: ?string} */
    public static function agent(string $ua, string $ip = ''): array
    {
        if (stripos($ua, 'GoogleImageProxy') !== false || stripos($ua, 'ggpht.com') !== false) {
            return ['via' => 'gmail', 'device' => null, 'os' => null, 'client' => 'Gmail'];
        }
        if (stripos($ua, 'YahooMailProxy') !== false) {
            return ['via' => 'yahoo', 'device' => null, 'os' => null, 'client' => 'Yahoo Mail'];
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && str_starts_with($ip, '17.')) {
            return ['via' => 'apple', 'device' => null, 'os' => null, 'client' => 'Apple Mail'];
        }
        $os = match (true) {
            (bool) preg_match('/iPad/i', $ua) => 'iPadOS',
            (bool) preg_match('/iPhone|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Windows/i', $ua) => 'Windows',
            (bool) preg_match('/CrOS/i', $ua) => 'ChromeOS',
            (bool) preg_match('/Macintosh|Mac OS X/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => null,
        };
        $device = match (true) {
            $os === 'iPadOS', $os === 'Android' && (bool) preg_match('/Tablet|Android(?!.*Mobile)/i', $ua) => 'tablet',
            in_array($os, ['iOS', 'Android'], true) => 'phone',
            $os !== null => 'computer',
            default => null,
        };
        $client = match (true) {
            (bool) preg_match('/Microsoft Outlook|ms-office|MSOffice|Outlook-iOS|Outlook-Android/i', $ua) => 'Outlook',
            (bool) preg_match('/Thunderbird/i', $ua) => 'Thunderbird',
            (bool) preg_match('/Edg\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/Firefox\//i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome\/|CriOS\//i', $ua) => 'Chrome',
            (bool) preg_match('/Version\/[\d.]+.*Safari\//i', $ua) => 'Safari',
            (bool) preg_match('/AppleWebKit/i', $ua) && in_array($os, ['macOS', 'iOS', 'iPadOS'], true) => 'Apple Mail',
            default => null,
        };

        return ['via' => 'direct', 'device' => $device, 'os' => $os, 'client' => $client];
    }

    /** IPv4 a.b.c.0, IPv6 the first 48 bits. Never the full address. */
    public static function prefix(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $bin = inet_pton($ip);

            return $bin === false ? null : inet_ntop(substr($bin, 0, 6) . str_repeat("\0", 10));
        }

        return null;
    }

    public static function events(int $prospectId, int $limit = 50): Collection
    {
        return DB::table('funding_email_events')->where('prospect_id', $prospectId)->orderByDesc('occurred_at')->limit($limit)->get()
            ->map(function ($e) {
                $e->at = Carbon::parse($e->occurred_at, config('app.timezone'))->tz(self::TZ);

                return $e;
            });
    }

    /**
     * One line an account manager can act on: "3 opens · 1 click · last Tue 8:12am · Nairobi · Android phone, Gmail".
     *
     * @return array{opens: int, clicks: int, last: Carbon, place: ?string, device: ?string, best: ?string, hidden: bool}|null
     */
    public static function summary(int $prospectId): ?array
    {
        $all = self::events($prospectId, 200);
        if ($all->isEmpty()) {
            return null;
        }
        $real = $all->where('via', '!=', 'apple');
        $located = $all->where('via', 'direct')->first(fn ($e) => $e->city || $e->country);
        $deviced = $all->where('via', 'direct')->first(fn ($e) => $e->device || $e->os);
        $client = $all->first(fn ($e) => $e->client)?->client;

        return [
            'opens' => $all->where('kind', 'open')->count(),
            'clicks' => $all->where('kind', 'click')->count(),
            'last' => $all->first()->at,
            'place' => $located ? self::place($located) : null,
            'device' => self::deviceLabel($deviced, $client),
            'best' => self::bestTime($real),
            'hidden' => $located === null && $all->whereIn('via', ['gmail', 'apple', 'yahoo'])->isNotEmpty(),
        ];
    }

    public static function place(object $e): ?string
    {
        $parts = array_values(array_unique(array_filter([$e->city ?? null, $e->region ?? null])));
        if (($e->country ?? null) && $e->country !== 'KE') {
            $parts[] = $e->country;
        }

        return $parts ? implode(', ', $parts) : (($e->country ?? null) === 'KE' ? 'Kenya' : null);
    }

    public static function deviceLabel(?object $e, ?string $client = null): ?string
    {
        $device = $e ? trim(($e->os ?? '') . ' ' . ($e->device ?? '')) : '';
        $parts = array_filter([$device ?: null, $client ?? $e?->client]);

        return $parts ? implode(', ', array_unique($parts)) : null;
    }

    /** When they usually read: "Weekdays around 8am". */
    public static function bestTime(Collection $events): ?string
    {
        if ($events->isEmpty()) {
            return null;
        }
        if ($events->count() < 2) {
            return 'Read ' . $events->first()->at->format('D') . ' around ' . $events->first()->at->format('ga');
        }
        $hour = $events->groupBy(fn ($e) => (int) $e->at->format('G'))->sortByDesc(fn ($g) => $g->count())->keys()->first();
        $weekend = $events->filter(fn ($e) => $e->at->isWeekend())->count();
        $days = $weekend > $events->count() / 2 ? 'Weekends' : ($weekend === 0 ? 'Weekdays' : 'Usually');

        return $days . ' around ' . Carbon::createFromTime($hour, 0, 0, self::TZ)->format('ga');
    }

    public static function prune(): int
    {
        return DB::table('funding_email_events')->where('occurred_at', '<', now()->subMonths(self::KEEP_MONTHS))->delete();
    }

    private static function cut(?string $v): ?string
    {
        return $v === null ? null : mb_substr($v, 0, 80);
    }
}
