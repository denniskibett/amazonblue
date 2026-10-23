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
        $list = $lines->openLines()->map(fn ($o) => ['id' => $o->line->id, 'label' => $label($o->line, $o->partner, $o->room)])->values();
        // editing: the line the loan is on now is always offered, even when it has no money free
        $current = (int) \App\Models\Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->whereNotNull('investment_id')->orderBy('id')->value('investment_id');
        if ($current && $current !== (int) $house?->id && ! $list->contains('id', $current) && ($line = Investment::find($current))) {
            $p = $line->fundings()->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->first()?->partner;
            $list->prepend(['id' => $line->id, 'label' => $label($line, $p, $line->remainingCommitted()) . ' (now)']);
        }

        return response()->json([
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
        ], ['maturity.required_if' => 'A long-term line needs the date it is to be paid back.']);
        try {
            $line = $lines->open($partner, (float) $data['amount'], $data['term'], $data['maturity'] ?? null, $data['code'], $data['date'] ?? null, $request->user(), $data['note'] ?? null);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Money recorded',
            'message' => 'KES ' . number_format((float) $data['amount'], 2) . ' from ' . $partner->name . ' is line #' . $line->id . '. Choose it under "Funded by" when you disburse.']);
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
