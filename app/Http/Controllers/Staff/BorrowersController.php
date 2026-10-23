<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Borrowers (owner, 23 Oct 2026): every borrower with what we know about them in one list: verification, income,
 * employer, pay schedule, what is still missing, and how they repay (score, on-time rate, comfortable amount,
 * suggested limit, flags). Read-only; each name opens the person panel.
 */
class BorrowersController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('view users'), 403);
        $hasCopy = Schema::hasColumn('borrowers', 'behaviour_score');
        $profiles = Schema::hasTable('borrower_profiles') ? DB::table('borrower_profiles')->get()->keyBy('user_id') : collect();
        $owed = DB::table('loans')->whereNull('deleted_at')->whereIn('status', ['disbursed', 'active', 'overdue', 'defaulted', 'recovery', 'forbearance'])
            ->groupBy('user_id')->selectRaw('user_id, count(*) as n')->pluck('n', 'user_id');

        $rows = User::role('borrower')->whereNull('deleted_at')->with('borrower')->orderBy('name')->get()
            ->reject(fn (User $u) => $u->isStaff())
            ->map(function (User $u) use ($profiles, $owed) {
                $b = $u->borrower;
                $p = $profiles[$u->id] ?? null;
                $flags = $p ? array_column(json_decode((string) $p->flags, true) ?: [], 'text') : ($b?->behaviour_flags ?? []);

                return (object) [
                    'user' => $u, 'b' => $b, 'missing' => $b ? $b->missing() : ['borrowers row', 'income type', 'net income', 'employer or business', 'pay schedule'],
                    'score' => $p->score ?? $b?->behaviour_score, 'on_time' => $p->on_time_rate ?? $b?->on_time_rate,
                    'comfort' => (float) ($p->comfort_amount ?? $b?->comfort_amount ?? 0), 'suggested' => (int) ($p->suggested_limit ?? $b?->suggested_limit ?? 0),
                    'loans' => (int) ($p->loans ?? 0), 'open' => (int) ($owed[$u->id] ?? 0), 'flags' => $flags,
                ];
            });

        $counts = [
            'all' => $rows->count(),
            'complete' => $rows->filter(fn ($r) => ! $r->missing)->count(),
            'no_income' => $rows->filter(fn ($r) => in_array('net income', $r->missing, true))->count(),
            'no_pay' => $rows->filter(fn ($r) => in_array('pay schedule', $r->missing, true))->count(),
            'no_row' => $rows->filter(fn ($r) => ! $r->b)->count(),
            'weekend' => $rows->filter(fn ($r) => collect($r->flags)->contains(fn ($f) => str_contains($f, 'weekend')))->count(),
        ];

        return view('staff.borrowers.index', compact('rows', 'counts', 'hasCopy'));
    }
}
