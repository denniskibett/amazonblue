<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Services\Partners\PartnerLines;
use App\Services\Partners\PartnerLinks;
use App\Support\MpesaSms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Partner money and partner links (owner, 23 Oct 2026):
 *  - read an M-Pesa SMS (JSON, for the disburse and partner forms);
 *  - record a partner's money as a short- or long-term line from the partner page;
 *  - per loan, set who brought it in and whose money funded it, with a dry run of how every share moves before
 *    anything is saved (the backfill for past loans).
 */
class PartnerFundingController extends Controller
{
    public function parseSms(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['create disbursements', 'create repayments', 'edit partners']), 403);
        $sms = MpesaSms::parse((string) $request->input('sms'));
        if (! $sms) {
            return response()->json(['ok' => false, 'message' => 'Could not find an M-Pesa code and amount in that message. Type them in instead.']);
        }

        // already on the books? (any of its references, on any loan) so staff don't enter the same money twice
        $refs = array_values(array_filter([$sms['code'], $sms['mpesa_ref'] ?? null, $sms['bank_ref'] ?? null]));
        $seen = [];
        foreach (['disbursements' => 'Disbursement', 'repayments' => 'Payment'] as $table => $label) {
            $hasCols = \Illuminate\Support\Facades\Schema::hasColumn($table, 'mpesa_ref');
            $row = \Illuminate\Support\Facades\DB::table($table)->whereNull('deleted_at')
                ->where(fn ($q) => $q->whereIn('transaction', $refs)->when($hasCols, fn ($w) => $w->orWhereIn('bank_ref', $refs)->orWhereIn('mpesa_ref', $refs)))
                ->first(['id', 'loan_id']);
            if ($row) {
                $seen[] = $label . ' #' . $row->id . ' on loan #' . $row->loan_id;
            }
        }

        return response()->json(['ok' => true, 'sms' => $sms, 'used' => app(PartnerLines::class)->codeUsed($sms['code']), 'seen' => $seen,
            'mode_label' => \App\Support\PaymentModes::label($sms['mode'] ?? null)]);
    }

    /** What the disbursement modal offers for a loan: brokers, partner lines with money free, defaults (JSON). */
    public function options(Request $request, Loan $loan, PartnerLinks $links, PartnerLines $lines): JsonResponse
    {
        abort_unless($request->user()->can('create disbursements'), 403);
        $own = $links->ownPartner($loan);
        $house = Investment::where('status', 'active')->get()->first(fn ($i) => $i->isHouseLine());
        $label = fn (Investment $line, $partner, float $room) => ($partner?->name ?? 'Partner') . ' · ' . (PartnerLines::TERMS[$line->facility_type] ?? 'Line')
            . ' #' . $line->id . ' · KES ' . number_format($room, 2) . ' free' . ($line->maturity_date ? ' · back by ' . $line->maturity_date->format('j M Y') : '');
        // partner_id / name / room (owner, 17 Nov 2026): the modal shows whose money each source is and marks the broker's row
        $list = $lines->openLines()->map(fn ($o) => ['id' => $o->line->id, 'label' => $label($o->line, $o->partner, $o->room),
            'partner_id' => $o->partner?->id, 'name' => $o->partner?->name ?? 'Partner', 'room' => round((float) $o->room, 2)])->values();
        // editing: the line the loan is on now is always offered, even when it has no money free
        $current = (int) \App\Models\Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->whereNotNull('investment_id')->orderBy('id')->value('investment_id');
        if ($current && $current !== (int) $house?->id && ! $list->contains('id', $current) && ($line = Investment::find($current))) {
            $p = $line->fundings()->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->first()?->partner;
            $list->prepend(['id' => $line->id, 'label' => $label($line, $p, $line->remainingCommitted()) . ' (now)', 'partner_id' => $p?->id, 'name' => $p?->name ?? 'Partner', 'room' => round((float) $line->remainingCommitted(), 2)]);
        }

        // who is behind the loan now (owner, 17 Nov 2026): one row per loan_fundings slice, or the one source a cash
        // disbursement names; the repayment modal shows these as "who it goes back to"
        $brokerId = $loan->broker_status && $loan->partner_id ? (int) $loan->partner_id : 0;
        $names = Partner::pluck('name', 'id');
        $slices = app(\App\Services\Loans\LoanFundings::class)->rows($loan->id);
        $total = (float) $slices->sum('amount') ?: (float) \App\Models\Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->sum('amount');
        $behind = $slices->isNotEmpty()
            ? $slices->map(fn ($r) => ['name' => $r->is_house || ! $r->partner_id ? 'Our money (house)' : ($names[$r->partner_id] ?? 'Partner'), 'amount' => round((float) $r->amount, 2),
                'share' => $total > 0 ? round((float) $r->amount / $total * 100, 2) : 0, 'broker' => $brokerId && (int) $r->partner_id === $brokerId])->values()
            : collect($current && $current !== (int) $house?->id && ($line = Investment::find($current))
                ? [['name' => $names[\App\Services\Loans\LoanFundings::partnerOf($line)] ?? 'Partner', 'amount' => round($total, 2), 'share' => 100, 'broker' => $brokerId && (int) \App\Services\Loans\LoanFundings::partnerOf($line) === $brokerId]]
                : [['name' => 'Our money (house)', 'amount' => round($total, 2), 'share' => 100, 'broker' => false]]);

        return response()->json([
            'behind' => $behind,
            'broker_name' => $brokerId ? ($names[$brokerId] ?? null) : null,
            'partners' => Partner::where('is_house_account', false)->orderBy('name')->get(['id', 'name']),
            'lines' => $list->values(),
            'current_broker' => $loan->broker_status && $loan->partner_id ? (int) $loan->partner_id : 0,
            'current_line' => $current ?: null,
            'has_repayments' => \App\Models\Repayment::where('loan_id', $loan->id)->where('kind', 'cash')->exists(),
            'links_url' => route('staff.loans.partners', $loan),
            'broker' => $loan->broker_status && $loan->partner_id ? (int) $loan->partner_id : ($own?->id ?? 0),
            'own' => $own ? ['id' => $own->id, 'name' => $own->name] : null,
            'house' => $house?->id,
            'maturity' => self::dueDate($loan),
            'modes' => \App\Support\PaymentModes::MODES,
        ]);
    }

    /** Record money a partner sent as a new line (partner page). */
    public function storeLine(Request $request, Partner $partner, PartnerLines $lines): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'code' => ['required', 'string', 'max:40'],
            'date' => ['nullable', 'date'],
            'term' => ['required', Rule::in(array_keys(PartnerLines::TERMS))],
            'maturity' => ['nullable', 'required_if:term,long_term', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:500'],
            // their % of the interest and of the penalty on what this money funds (owner, 3 Nov 2026)
            'share_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penalty_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // a fixed return (owner, 14 Nov 2026): a set % for a set number of days, whatever the loans earn
            'kind' => ['nullable', Rule::in(['loan_share', 'fixed_return'])],
            'fixed_rate' => ['nullable', 'required_if:kind,fixed_return', 'numeric', 'gt:0', 'max:100'],
            'fixed_days' => ['nullable', 'required_if:kind,fixed_return', 'integer', 'min:1', 'max:3650'],
            'fixed_share' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], ['maturity.required_if' => 'A long-term line needs the date it is to be paid back.',
            'fixed_rate.required_if' => 'A fixed return needs its %.', 'fixed_days.required_if' => 'A fixed return needs its number of days.']);
        $fixed = ($data['kind'] ?? null) === 'fixed_return' ? ['rate' => (float) $data['fixed_rate'], 'days' => (int) $data['fixed_days'], 'share' => (float) ($data['fixed_share'] ?? 0)] : null;
        if ($fixed) {
            $data['maturity'] = null;   // worked out from the days
        }
        try {
            $line = $lines->open($partner, (float) $data['amount'], $data['term'], $data['maturity'] ?? null, $data['code'], $data['date'] ?? null, $request->user(), $data['note'] ?? null,
                isset($data['share_rate']) ? (float) $data['share_rate'] : null, isset($data['penalty_rate']) ? (float) $data['penalty_rate'] : null, $fixed);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Money recorded',
            'message' => 'KES ' . number_format((float) $data['amount'], 2) . ' from ' . $partner->name . ' is line #' . $line->id . '. Choose it under "Funded by" when you disburse.']);
    }

    /** A fixed-return line paid back (owner, 14 Nov 2026): capital and return out of the partner's wallets, line closed. */
    public function payBackLine(Request $request, Partner $partner, Investment $line, PartnerLines $lines): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        abort_unless($line->fundings()->where('partner_id', $partner->id)->exists(), 404);
        $data = $request->validate([
            'paid' => ['required', 'numeric', 'min:1'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'code' => ['required', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:300'],
            'from_line' => ['nullable', 'integer', 'exists:investments,id'],
        ]);
        try {
            $r = $lines->payBack($line, (float) $data['paid'], $data['date'], $data['code'], $request->user(), $data['note'] ?? null, $data['from_line'] ?? null);
        } catch (\DomainException|\RuntimeException $e) {
            return back()->withInput()->withErrors(['paid' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Paid back',
            'message' => 'Line #' . $line->id . ' is closed: KES ' . number_format($r['principal'], 2) . ' capital and KES ' . number_format($r['return'], 2) . ' return to ' . $partner->name . '.']);
    }

    /** Whose money paid a fixed line back (owner, 15 Nov 2026): another partner's line, or ours. */
    public function payoutSource(Request $request, Partner $partner, Investment $line, PartnerLines $lines): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        abort_unless($line->fundings()->where('partner_id', $partner->id)->exists(), 404);
        $data = $request->validate(['from_line' => ['nullable', 'integer', 'exists:investments,id']]);
        try {
            $amount = $lines->setPayoutSource($line, $data['from_line'] ?? null, $request->user());
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['from_line' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Payout placed',
            'message' => 'KES ' . number_format($amount, 2) . ' paid to ' . $partner->name . ' now comes out of ' . (($data['from_line'] ?? null) ? 'line #' . $data['from_line'] : 'our own money') . '.']);
    }

    /** A fixed line's terms: rate on the money, their share of it, days (owner, 15 Nov 2026). */
    public function fixedTerms(Request $request, Partner $partner, Investment $line, PartnerLines $lines): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        abort_unless($line->fundings()->where('partner_id', $partner->id)->exists(), 404);
        $data = $request->validate(['gross' => ['required', 'numeric', 'gt:0', 'max:1000'], 'share' => ['required', 'numeric', 'gt:0', 'max:100'], 'days' => ['required', 'integer', 'min:1', 'max:3650']]);
        try {
            $line = $lines->updateFixedTerms($line, (float) $data['gross'], (float) $data['share'], (int) $data['days'], $request->user());
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['terms' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Terms changed', 'message' => 'Line #' . $line->id . ': ' . PartnerLines::fixedLabel((float) $line->interest_rate, (float) $line->partner_share_rate, (int) $line->duration_days) . '.']);
    }

    /** One loan's partner links: current state, the form, and (with ?preview) the dry run. */
    public function show(Request $request, Loan $loan, PartnerLinks $links, PartnerLines $lines): View
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $loan->load(['user:id,name,phone', 'loanType:id,name,period,unit']);
        $disbursements = Disbursement::where('loan_id', $loan->id)->orderBy('id')->get();
        $cash = $disbursements->filter(fn ($d) => $d->kind === 'cash');
        $currentLine = $cash->pluck('investment_id')->filter()->first();
        $own = $links->ownPartner($loan);
        $partners = Partner::where('is_house_account', false)->orderBy('name')->pluck('name', 'id');
        $lineOptions = Investment::where('status', 'active')->orderBy('id')->get()
            ->filter(fn ($i) => $i->id === (int) $currentLine || $i->isHouseLine() || $i->remainingCommitted() > 0)
            ->mapWithKeys(fn ($i) => [$i->id => $i->name . ($i->isHouseLine() ? ' (our money)' : ' · KES ' . number_format($i->remainingCommitted(), 2) . ' free')]);

        $preview = null;
        $input = ['broker' => $request->query('broker'), 'line' => $request->query('line'), 'mode' => $request->query('mode', 'keep')];
        if ($request->boolean('preview')) {
            $preview = $this->runPreview($request, $loan, $links);
        }

        return view('staff.partners.loan-links', compact('loan', 'disbursements', 'cash', 'currentLine', 'own', 'partners', 'lineOptions', 'preview', 'input')
            + ['journal' => $links->journal($loan->id), 'defaultMaturity' => $this->dueDate($loan)]);
    }

    public function apply(Request $request, Loan $loan, PartnerLinks $links, PartnerLines $lines): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Tick the box to confirm you checked the preview.']);
        [$brokerId, $lineId, $new] = $this->choices($request);
        try {
            if ($new) {
                $lineId = $lines->open(Partner::findOrFail($new['partner']), $new['amount'], $new['term'], $new['maturity'], $new['code'], $new['date'], $request->user(),
                    'Recorded for loan #' . $loan->id)->id;
            }
            $changes = $links->apply($loan, $brokerId, $lineId, $request->user(), 'partner_links');
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['line' => $e->getMessage()]);
        }

        return redirect()->route('staff.loans.partners', $loan)->with('alert', ['type' => 'success', 'title' => $changes ? 'Saved' : 'Nothing to change',
            'message' => $changes ? ucfirst(implode('; ', $changes)) . '. Every repayment on this loan was re-split.' : 'The loan already had these links.']);
    }

    private function runPreview(Request $request, Loan $loan, PartnerLinks $links): array
    {
        [$brokerId, $lineId, $new] = $this->choices($request);
        $prepare = $new ? fn () => app(PartnerLines::class)->open(Partner::findOrFail($new['partner']), $new['amount'], $new['term'], $new['maturity'],
            $new['code'], $new['date'], $request->user())->id : null;

        return $links->preview($loan, $brokerId, $lineId, $prepare);
    }

    /** [brokerId|null (no change)|0 (nobody), lineId|null (no change), new-line data|null] from the form. */
    private function choices(Request $request): array
    {
        $data = $request->validate([
            'broker' => ['nullable', 'string'],
            'mode' => ['nullable', Rule::in(['keep', 'line', 'new'])],
            'line' => ['nullable', 'integer', 'exists:investments,id'],
            'new_partner' => ['nullable', 'required_if:mode,new', 'integer', 'exists:partners,id'],
            'new_amount' => ['nullable', 'required_if:mode,new', 'numeric', 'min:1'],
            'new_code' => ['nullable', 'required_if:mode,new', 'string', 'max:40'],
            'new_date' => ['nullable', 'date'],
            'new_term' => ['nullable', 'required_if:mode,new', Rule::in(array_keys(PartnerLines::TERMS))],
            'new_maturity' => ['nullable', 'date'],
        ]);
        $broker = ($data['broker'] ?? 'keep') === 'keep' || ($data['broker'] ?? '') === '' ? null : (int) $data['broker'];
        $mode = $data['mode'] ?? 'keep';
        $new = $mode === 'new' ? ['partner' => (int) $data['new_partner'], 'amount' => (float) $data['new_amount'], 'code' => $data['new_code'],
            'date' => $data['new_date'] ?? null, 'term' => $data['new_term'], 'maturity' => $data['new_maturity'] ?? null] : null;

        return [$broker, $mode === 'line' ? (int) ($data['line'] ?? 0) ?: null : null, $new];
    }

    /** When a short-term line on this loan should be back: the current cycle's due date, else one product period. */
    public static function dueDate(Loan $loan): string
    {
        $due = $loan->cycles()->orderByDesc('cycle_number')->value('due_date');
        if ($due && Carbon::parse($due)->isFuture()) {
            return Carbon::parse($due)->toDateString();
        }
        $type = $loan->loanType;
        $n = (int) ($type->period ?? 0) ?: 30;

        $at = match ($type->unit ?? 'days') {
            'weeks' => now()->addWeeks($n), 'months' => now()->addMonths($n), 'year', 'years' => now()->addYears($n), default => now()->addDays($n),
        };

        return $at->toDateString();
    }
}
