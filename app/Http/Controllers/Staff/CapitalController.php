<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Capital raising (owner, 4 Nov 2026). Investment managers keep their prospects from first call to money in and see
 * the capital their investors put in; admins and the owner see every manager side by side (they report to the
 * owner), and capital in against money lent. Permission "manage investor pipeline".
 */
class CapitalController extends Controller
{
    public const STAGES = ['first_call' => 'First call', 'meeting' => 'Meeting', 'due_diligence' => 'Due diligence', 'term_sheet' => 'Term sheet', 'won' => 'Money in', 'lost' => 'Lost'];

    public const KINDS = ['individual' => 'Individual', 'corporate' => 'Corporate', 'sacco' => 'Sacco', 'group' => 'Group or chama', 'institution' => 'Institution'];

    public function index(Request $request): View
    {
        $me = $request->user();
        $all = $me->hasAnyRole(['super-admin', 'admin']);
        $mine = fn ($q, string $col) => $all && ! $request->boolean('mine') ? $q : $q->where($col, $me->id);
        [$qFrom, $qTo] = [now()->startOfQuarter()->toDateString(), now()->toDateString()];
        $managers = User::role('investment-manager')->orderBy('name')->get(['id', 'name']);

        $prospects = $mine(DB::table('investor_prospects as p')->leftJoin('users as u', 'u.id', '=', 'p.manager_id'), 'p.manager_id')
            ->orderByRaw("field(p.stage, 'term_sheet', 'due_diligence', 'meeting', 'first_call', 'won', 'lost')")->orderBy('p.next_on')->get(['p.*', 'u.name as manager']);
        $open = $prospects->whereNotIn('stage', ['won', 'lost']);
        $partners = $mine(DB::table('partners'), 'investment_manager_id')->where('is_house_account', false)->get(['id', 'name', 'type', 'investment_manager_id']);
        $pids = $partners->pluck('id');
        $sumJ = fn (array $types, ?string $from = null) => (float) DB::table('partner_transactions')->whereIn('partner_id', $pids)->whereIn('type', $types)
            ->when($from, fn ($q) => $q->whereBetween('transaction_date', [$from, $qTo]))->sum('amount');
        $raisedQ = $sumJ(['contribution'], $qFrom);
        $target = (float) config('lending.analytics.capital_target_quarter', 0);

        // per manager: what their investors put in this quarter and in all, what they hold, what they earned
        $byManager = $all ? $managers->map(function ($m) use ($qFrom, $qTo) {
            $ids = DB::table('partners')->where('investment_manager_id', $m->id)->pluck('id');
            $j = fn () => DB::table('partner_transactions')->whereIn('partner_id', $ids);
            $in = (float) $j()->where('type', 'contribution')->sum('amount');
            $earned = (float) $j()->whereIn('type', ['profit_distribution', 'interest', 'penalty_share', 'broker_fee_earned'])->sum('amount');
            $held = $ids->filter(fn ($id) => (float) DB::table('partner_transactions')->where('partner_id', $id)->sum('amount') > 0.5)->count();

            return ['name' => $m->name, 'id' => $m->id, 'raised_q' => (float) $j()->where('type', 'contribution')->whereBetween('transaction_date', [$qFrom, $qTo])->sum('amount'),
                'raised' => $in, 'investors' => $ids->count(), 'kept' => $ids->count() ? round($held / $ids->count() * 100) : null,
                'investor_return' => $in > 0 ? round($earned / $in * 100, 1) : null,
                'pipeline' => (float) DB::table('investor_prospects')->where('manager_id', $m->id)->whereNotIn('stage', ['won', 'lost'])->sum(DB::raw('coalesce(ticket, 0) * coalesce(chance, 0) / 100'))];
        }) : collect();

        // capital in against money lent, by month (all partners, the owner's view)
        $months = collect(range(5, 0))->map(function ($back) {
            $s = now()->startOfMonth()->subMonths($back);
            $e = $s->copy()->endOfMonth();

            return ['m' => $s->format('M'),
                'in' => (float) DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('p.is_house_account', false)->where('t.type', 'contribution')->whereBetween('t.transaction_date', [$s, $e])->sum('t.amount'),
                'lent' => (float) \App\Services\CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at'))->whereBetween('disburse_date', [$s, $e])->sum(DB::raw(\App\Services\CustomerWallets::DISBURSED_CASH_SQL))];
        });

        return view('staff.capital', [
            'all' => $all && ! $request->boolean('mine'), 'canAll' => $all, 'prospects' => $prospects, 'open' => $open, 'partners' => $partners, 'managers' => $managers, 'byManager' => $byManager,
            'raisedQ' => $raisedQ, 'raisedAll' => $sumJ(['contribution']), 'target' => $target, 'months' => $months,
            'weighted' => round($open->sum(fn ($p) => (float) $p->ticket * (int) $p->chance / 100), 2), 'pipelineFull' => round($open->sum('ticket'), 2),
            'funnel' => collect(self::STAGES)->except(['lost'])->map(fn ($l, $k) => ['label' => $l, 'n' => $prospects->where('stage', $k)->count(), 'amount' => (float) $prospects->where('stage', $k)->sum('ticket')]),
            'paidOut' => abs($sumJ(['withdrawal', 'payout', 'broker_fee_paid'])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validated($request);
        $d['manager_id'] = $request->user()->hasAnyRole(['super-admin', 'admin']) && $request->filled('manager_id') ? (int) $request->manager_id : $request->user()->id;
        $id = DB::table('investor_prospects')->insertGetId($d + ['created_at' => now(), 'updated_at' => now()]);
        AuditTrail::record('investor_prospects', $id, 'create', null, ['name' => $d['name'], 'by' => $request->user()->id]);

        return back()->with('success', $d['name'] . ' added to the pipeline.');
    }

    public function update(Request $request, int $prospect): RedirectResponse
    {
        $row = DB::table('investor_prospects')->find($prospect) ?? abort(404);
        abort_unless($request->user()->hasAnyRole(['super-admin', 'admin']) || (int) $row->manager_id === (int) $request->user()->id, 403);
        $d = $request->validate(['stage' => 'required|in:' . implode(',', array_keys(self::STAGES)), 'chance' => 'nullable|integer|min:0|max:100',
            'next_on' => 'nullable|date', 'next_step' => 'nullable|string|max:255', 'partner_id' => 'nullable|integer|exists:partners,id']);
        DB::table('investor_prospects')->where('id', $prospect)->update($d + ['updated_at' => now()]);
        // money in: the partner they became is looked after by this manager
        if ($d['stage'] === 'won' && ! empty($d['partner_id'])) {
            DB::table('partners')->where('id', $d['partner_id'])->whereNull('investment_manager_id')->update(['investment_manager_id' => $row->manager_id]);
        }
        AuditTrail::record('investor_prospects', $prospect, 'update', ['stage' => $row->stage], $d + ['by' => $request->user()->id]);

        return back()->with('success', $row->name . ': ' . self::STAGES[$d['stage']] . '.');
    }

    /** Admin: which investment manager looks after a partner. */
    public function assign(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['super-admin', 'admin']), 403);
        $d = $request->validate(['partner_id' => 'required|integer|exists:partners,id', 'manager_id' => 'nullable|integer|exists:users,id']);
        DB::table('partners')->where('id', $d['partner_id'])->update(['investment_manager_id' => $d['manager_id'] ?? null]);
        AuditTrail::record('partners', $d['partner_id'], 'update', null, ['investment_manager_id' => $d['manager_id'] ?? null, 'by' => $request->user()->id]);

        return back()->with('success', 'Investment manager updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'kind' => 'required|in:' . implode(',', array_keys(self::KINDS)), 'contact' => 'nullable|string|max:255',
            'wants' => 'nullable|string|max:255', 'ticket' => 'nullable|numeric|min:0', 'stage' => 'required|in:' . implode(',', array_keys(self::STAGES)),
            'chance' => 'nullable|integer|min:0|max:100', 'next_on' => 'nullable|date', 'next_step' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000']);
    }
}
