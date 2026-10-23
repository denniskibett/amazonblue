<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\BorrowerAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lending analytics (owner, 23 Oct 2026): which amounts are asked for, sent and paid back comfortably, the same
 * by weekday, time of month and repeat borrowing, and every borrower's behaviour profile. Read-only.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request, BorrowerAnalytics $analytics)
    {
        abort_unless($request->user()->can('view reports'), 403);
        $heat = $analytics->heatmaps();
        $profiles = Schema::hasTable('borrower_profiles')
            ? DB::table('borrower_profiles as p')->join('users as u', 'u.id', '=', 'p.user_id')->whereNull('u.deleted_at')
                ->orderByDesc('p.loans')->get(['p.*', 'u.name', 'u.phone'])->map(function ($p) {
                    $p->flags = json_decode((string) $p->flags, true) ?: [];

                    return $p;
                })
            : collect();
        $computedAt = $profiles->max('computed_at');

        return view('staff.analytics.index', compact('heat', 'profiles', 'computedAt'));
    }
}
