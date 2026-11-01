<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Site analytics (owner, 1 Nov 2026): reads page_views (TrackPageView) for the Site analytics page. A "visit" is
 * one session; a "visitor" is a signed-in person (by user id) or, for guests, one session, because Laravel gives
 * a new session id at sign-in. Suggestions are plain rules over the same numbers, nothing clever.
 */
class SiteAnalytics
{
    /** sign-up to loan, by the GET pages people open; the last step is counted from the loans table */
    public const FUNNEL = [
        ['label' => 'Home page', 'routes' => ['home'], 'fix' => 'Make the first screen say what we lend, how fast and what it costs, with one clear "Apply" button.'],
        ['label' => 'Sign-up page', 'routes' => ['register'], 'fix' => 'Shorten the sign-up form (fewer fields, clear errors) and repeat what the visitor gets after signing up.'],
        ['label' => 'KYC started', 'routes' => ['kyc.personal'], 'fix' => 'After sign-up, send people straight into KYC and tell them how long it takes (about 5 minutes).'],
        ['label' => 'KYC review', 'routes' => ['kyc.review'], 'fix' => 'Check the KYC documents and income steps on a phone: photo uploads and long forms are where people stop.'],
        ['label' => 'KYC sent', 'routes' => ['kyc.submitted'], 'fix' => 'Make "Submit" obvious on the review step and say what happens next and when.'],
        ['label' => 'Borrow page', 'routes' => ['account.borrow'], 'fix' => 'Once KYC is approved, tell the borrower by SMS/email with a direct link to Borrow.'],
        ['label' => 'Loan applied', 'routes' => null, 'fix' => 'On the Borrow page, show the limit, fees and due date up front so people are confident to apply.'],
    ];

    public function enabled(): bool
    {
        return Schema::hasTable('page_views');
    }

    private function base(Carbon $from, Carbon $to): Builder
    {
        return DB::table('page_views')->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    /** signed-in people by id, guests by session */
    private const VISITOR = "COALESCE(CONCAT('u', user_id), session_hash)";

    public function report(Carbon $from, Carbon $to): array
    {
        $ok = fn () => $this->base($from, $to)->where('status', '<', 400);

        $t = $ok()->selectRaw('COUNT(*) views, COUNT(DISTINCT session_hash) visits, COUNT(DISTINCT ' . self::VISITOR . ') visitors,
            COUNT(DISTINCT user_id) users, COUNT(DISTINCT CASE WHEN device = ? THEN session_hash END) mobile_visits', ['mobile'])->first();
        $views = (int) $t->views;
        $visits = (int) $t->visits;
        $single = (int) DB::query()->fromSub($ok()->whereNotNull('session_hash')->groupBy('session_hash')
            ->selectRaw('session_hash, COUNT(*) c'), 's')->where('c', 1)->count();
        $totals = [
            'views' => $views, 'visits' => $visits, 'visitors' => (int) $t->visitors, 'users' => (int) $t->users,
            'per_visit' => $visits ? round($views / $visits, 1) : null,
            'mobile' => $visits ? round($t->mobile_visits / $visits * 100, 1) : null,
            'bounce' => $visits ? round($single / $visits * 100, 1) : null,
        ];

        $daily = $ok()->selectRaw('DATE(occurred_at) d, COUNT(*) views, COUNT(DISTINCT ' . self::VISITOR . ') visitors')
            ->groupByRaw('DATE(occurred_at)')->orderBy('d')->get()->keyBy(fn ($r) => (string) $r->d);
        $days = [];
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            $r = $daily[$d->toDateString()] ?? null;
            $days[] = ['date' => $d->toDateString(), 'views' => (int) ($r->views ?? 0), 'visitors' => (int) ($r->visitors ?? 0)];
        }

        $pages = $ok()->selectRaw('COALESCE(route_name, path) page, MIN(path) example, COUNT(*) views,
            COUNT(DISTINCT ' . self::VISITOR . ') visitors, ROUND(AVG(response_ms)) avg_ms')
            ->groupByRaw('COALESCE(route_name, path)')->orderByDesc('views')->limit(15)->get();
        $slow = $ok()->selectRaw('COALESCE(route_name, path) page, COUNT(*) views, ROUND(AVG(response_ms)) avg_ms')
            ->groupByRaw('COALESCE(route_name, path)')->havingRaw('COUNT(*) >= 10 AND AVG(response_ms) > ?', [(int) config('lending.analytics.slow_ms', 1500)])
            ->orderByDesc('avg_ms')->limit(5)->get();
        $referrers = $ok()->whereNotNull('referrer_host')->selectRaw('referrer_host host, COUNT(DISTINCT session_hash) visits')
            ->groupBy('referrer_host')->orderByDesc('visits')->limit(10)->get();
        $campaigns = $ok()->whereNotNull('utm_source')->selectRaw('utm_source source, utm_medium medium, utm_campaign campaign, COUNT(DISTINCT session_hash) visits')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')->orderByDesc('visits')->limit(10)->get();
        $devices = $ok()->selectRaw('device, COUNT(DISTINCT session_hash) visits')->groupBy('device')->orderByDesc('visits')->pluck('visits', 'device');
        $roles = $ok()->selectRaw('role, COUNT(*) views, COUNT(DISTINCT ' . self::VISITOR . ') visitors')->groupBy('role')->orderByDesc('views')->get();
        $missing = $this->base($from, $to)->where('status', 404)->selectRaw('path, COUNT(*) hits, MAX(referrer_host) referrer')
            ->groupBy('path')->orderByDesc('hits')->limit(10)->get();

        $funnel = $this->funnel($from, $to);
        $suggestions = $this->suggestions($totals, $funnel, $slow, $missing, $referrers, $campaigns);

        return compact('totals', 'days', 'pages', 'slow', 'referrers', 'campaigns', 'devices', 'roles', 'missing', 'funnel', 'suggestions');
    }

    public function funnel(Carbon $from, Carbon $to): array
    {
        $steps = [];
        $prev = null;
        foreach (self::FUNNEL as $step) {
            $n = $step['routes'] === null
                ? (int) DB::table('loans')->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->distinct()->count('user_id')
                : (int) $this->base($from, $to)->where('status', '<', 400)->whereIn('route_name', $step['routes'])
                    ->selectRaw('COUNT(DISTINCT ' . self::VISITOR . ') n')->value('n');
            $kept = $prev ? round($n / $prev * 100, 1) : null;
            $steps[] = $step + ['n' => $n, 'kept' => $kept];
            $prev = $n;
        }

        return $steps;
    }

    /** plain-language "how to improve" list from simple rules over the report */
    public function suggestions(array $totals, array $funnel, Collection $slow, Collection $missing, Collection $referrers, Collection $campaigns): array
    {
        $out = [];
        if ($totals['visits'] < 20) {
            $out[] = ['tone' => 'info', 'title' => 'Not enough visits yet', 'text' => 'Fewer than 20 visits in these dates. Pick a longer range, or check again in a week, before acting on these numbers.'];
        }
        $leaks = 0;
        for ($i = 1; $i < count($funnel); $i++) {
            $a = $funnel[$i - 1];
            $b = $funnel[$i];
            if ($a['n'] >= 10 && $b['kept'] !== null && $b['kept'] < 50) {
                $leaks++;
                $out[] = ['tone' => 'down', 'title' => "Most people stop between \"{$a['label']}\" and \"{$b['label']}\"",
                    'text' => "Only {$b['kept']}% ({$b['n']} of {$a['n']}) went on. " . $b['fix']];
            }
        }
        if ($leaks && ($totals['mobile'] ?? 0) >= 60) {
            $out[] = ['tone' => 'down', 'title' => "{$totals['mobile']}% of visits are on a phone",
                'text' => 'People are dropping out of the steps above and most of them are on phones. Walk the sign-up, KYC and Borrow steps on a cheap Android phone: big buttons, short pages, photos that upload on slow data.'];
        }
        foreach ($slow as $p) {
            $out[] = ['tone' => 'down', 'title' => "Slow page: {$p->page}",
                'text' => 'Takes ' . number_format($p->avg_ms / 1000, 1) . " s on average over {$p->views} views. Pages slower than about 1.5 s lose people; ask the developer to look at this page's queries."];
        }
        if ($missing->sum('hits') >= 3) {
            $top = $missing->first();
            $out[] = ['tone' => 'down', 'title' => 'People are landing on "page not found"',
                'text' => "{$missing->sum('hits')} times, most often {$top->path}" . ($top->referrer ? " (from {$top->referrer})" : '') . '. Fix the link where it comes from, or redirect the old address.'];
        }
        if (($totals['bounce'] ?? 0) >= 60 && $totals['visits'] >= 20) {
            $out[] = ['tone' => 'down', 'title' => "{$totals['bounce']}% of visits see one page and leave",
                'text' => 'Give the first page one clear next step (Apply, Check my limit, Sign in) and make it fast on phones.'];
        }
        if ($totals['visits'] >= 20 && $campaigns->isEmpty()) {
            $out[] = ['tone' => 'info', 'title' => 'You cannot tell which promotion brings people',
                'text' => 'Add ?utm_source=facebook&utm_campaign=november (or sms, whatsapp…) to links you share, and this page will show which ones bring visitors.'];
        }
        if ($totals['visits'] >= 20 && $referrers->isEmpty() && $campaigns->isEmpty()) {
            $out[] = ['tone' => 'info', 'title' => 'Almost nobody arrives from another site',
                'text' => 'Visitors type the address or use a bookmark. A Google Business profile and links from social pages would bring new people.'];
        }
        if (! $out) {
            $out[] = ['tone' => 'up', 'title' => 'Nothing stands out', 'text' => 'No step loses more than half of the people, no page is slow and no missing pages are being hit. Keep watching the funnel week by week.'];
        }

        return $out;
    }

    /** people seen in the dates, newest first, for the journey picker */
    public function people(Carbon $from, Carbon $to): Collection
    {
        return $this->base($from, $to)->whereNotNull('page_views.user_id')
            ->join('users as u', 'u.id', '=', 'page_views.user_id')
            ->groupBy('page_views.user_id', 'u.name', 'u.phone')
            ->selectRaw('page_views.user_id id, u.name, u.phone, COUNT(*) views, MAX(page_views.occurred_at) last_seen')
            ->orderByDesc('last_seen')->limit(300)->get();
    }

    /** one person's recent visits (newest first), each with its pages in order */
    public function journey(int $userId, int $limit = 300): Collection
    {
        return DB::table('page_views')->where('user_id', $userId)->orderByDesc('occurred_at')->orderByDesc('id')->limit($limit)
            ->get(['occurred_at', 'session_hash', 'path', 'route_name', 'status', 'device', 'response_ms', 'referrer_host', 'utm_source'])
            ->groupBy(fn ($r) => $r->session_hash ?? 'none')
            ->map(function ($rows) {
                $rows = $rows->sortBy('occurred_at')->values();

                return (object) ['start' => Carbon::parse($rows->first()->occurred_at), 'end' => Carbon::parse($rows->last()->occurred_at),
                    'device' => $rows->first()->device, 'from' => $rows->first()->referrer_host ?? $rows->first()->utm_source, 'pages' => $rows];
            })->sortByDesc('start')->values();
    }
}
