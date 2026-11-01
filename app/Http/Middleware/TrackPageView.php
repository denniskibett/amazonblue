<?php

namespace App\Http\Middleware;

use App\Support\Preview;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site analytics (owner, 1 Nov 2026): one page_views row per HTML page someone opened, written in terminate()
 * (after the page has gone out) so nobody waits for it, and never allowed to throw. Kept: who (user + first role,
 * or "visitor"), a sha256 of the session id (never the id itself), the page, where they came from (host + utm_*),
 * phone / tablet / computer and how long the page took. No IP address. Skipped: JSON/AJAX, files, health checks,
 * M-Pesa callbacks, bots, and a sysadmin's "view as" preview. Off with ANALYTICS_ENABLED=false.
 */
class TrackPageView
{
    private const SKIP = ['up', 'mpesa/*', 'preview', 'preview/*', 'assets/*', 'build/*', 'storage/*', 'vendor/*',
        'telescope*', 'pulse*', '_debugbar/*', 'livewire/*', 'favicon.ico', 'robots.txt', 'sitemap.xml'];

    private const BOTS = '/bot|crawl|spider|slurp|curl|wget|python|httpclient|okhttp|java\/|go-http|headless|lighthouse|pingdom|uptime|monitor|facebookexternalhit|whatsapp|preview|scan/i';

    public function handle(Request $request, Closure $next): Response
    {
        $t0 = microtime(true);
        $response = $next($request);
        $request->attributes->set('az.page_ms', (int) round((microtime(true) - $t0) * 1000));

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->counts($request, $response)) {
                return;
            }
            $user = $request->user();
            $ua = (string) $request->userAgent();
            $sid = $request->hasSession() ? $request->session()->getId() : null;
            $ref = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
            $ref = $ref ? preg_replace('/^www\./', '', strtolower($ref)) : null;
            if ($ref && $ref === preg_replace('/^www\./', '', strtolower($request->getHost()))) {
                $ref = null; // moving around our own site is not a referrer
            }
            $ms = $request->attributes->get('az.page_ms')
                ?? (defined('LARAVEL_START') ? (int) round((microtime(true) - LARAVEL_START) * 1000) : null);
            $utm = fn (string $k) => ($v = trim((string) $request->query($k))) === '' ? null : mb_substr($v, 0, 100);

            DB::table('page_views')->insert([
                'occurred_at' => now(),
                'user_id' => $user?->id,
                'role' => $user ? mb_substr((string) ($user->getRoleNames()->first() ?? 'user'), 0, 40) : 'visitor',
                'session_hash' => $sid ? hash('sha256', $sid) : null,
                'path' => mb_substr('/' . ltrim($request->path(), '/'), 0, 255),
                'route_name' => $request->route()?->getName() ? mb_substr($request->route()->getName(), 0, 120) : null,
                'status' => $response->getStatusCode(),
                'referrer_host' => $ref ? mb_substr($ref, 0, 120) : null,
                'utm_source' => $utm('utm_source'),
                'utm_medium' => $utm('utm_medium'),
                'utm_campaign' => $utm('utm_campaign'),
                'device' => self::device($ua),
                'response_ms' => $ms !== null ? max(0, min($ms, 4294967295)) : null,
            ]);
        } catch (\Throwable $e) {
            // analytics must never break a page; a missing table or a busy database just loses this one view
        }
    }

    private function counts(Request $request, Response $response): bool
    {
        if (! config('lending.analytics.enabled', true) || ! $request->isMethod('GET')) {
            return false;
        }
        $status = $response->getStatusCode();
        if (! ($status >= 200 && $status < 300) && $status !== 404) {
            return false;
        }
        if ($request->expectsJson() || $request->ajax() || $request->headers->has('X-Livewire')
            || in_array(strtolower((string) $request->headers->get('Purpose', $request->headers->get('Sec-Purpose'))), ['prefetch'], true)) {
            return false;
        }
        if (! str_contains(strtolower((string) $response->headers->get('Content-Type', 'text/html')), 'text/html')) {
            return false; // PDFs, CSV downloads, images
        }
        if ($request->is(...self::SKIP)) {
            return false;
        }
        $ua = (string) $request->userAgent();
        if ($ua === '' || preg_match(self::BOTS, $ua)) {
            return false;
        }
        // a sysadmin's "view as" preview is not a real visit
        if (app()->bound('az.preview') || ($request->hasSession() && Preview::active())) {
            return false;
        }

        return true;
    }

    public static function device(string $ua): string
    {
        if (preg_match('/ipad|tablet|kindle|silk|playbook|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }

        return preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $ua) ? 'mobile' : 'desktop';
    }
}
