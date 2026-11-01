<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SiteAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Site analytics (owner, 1 Nov 2026): who visits, which pages, where people stop on the way from the home page
 * to a loan, one person's journey, and a "How to improve" list. First-party data only (page_views). Read-only.
 */
class SiteAnalyticsController extends Controller
{
    public function index(Request $request, SiteAnalytics $analytics)
    {
        abort_unless($request->user()->can('view reports'), 403);
        $f = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'user' => ['nullable', 'integer'],
        ]);
        $to = isset($f['to']) ? Carbon::parse($f['to'])->startOfDay() : today();
        $from = isset($f['from']) ? Carbon::parse($f['from'])->startOfDay() : $to->copy()->subDays(29);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366); // a year at most, so the page stays quick
        }

        if (! $analytics->enabled()) {
            return view('staff.site-analytics.index', ['ready' => false, 'from' => $from, 'to' => $to]);
        }

        $report = $analytics->report($from, $to);
        $people = $analytics->people($from, $to);
        $person = isset($f['user']) ? User::withTrashed()->find($f['user']) : null;
        $journey = $person ? $analytics->journey($person->id) : collect();

        return view('staff.site-analytics.index', $report + [
            'ready' => true, 'from' => $from, 'to' => $to, 'people' => $people, 'person' => $person, 'journey' => $journey,
            'tracking' => (bool) config('lending.analytics.enabled'), 'keepDays' => (int) config('lending.analytics.keep_days'),
        ]);
    }
}
