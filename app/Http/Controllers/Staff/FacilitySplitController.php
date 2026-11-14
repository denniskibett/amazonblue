<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\Loans\FacilitySplitter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A facility's own split, set on the loan's partner page (owner, 14 Nov 2026): who put in what, how many cycles of
 * interest count and at what rate, the total to collect, how the penalty is shared and what each side has had.
 * "Preview" shows the figures; "Save" replaces any earlier split and re-splits the payments from the start day.
 */
class FacilitySplitController extends Controller
{
    public function store(Request $request, Loan $loan, FacilitySplitter $splitter): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $data = $request->validate([
            'parties' => ['required', 'array', 'min:2', 'max:6'],
            'parties.*.partner_id' => ['nullable', 'integer'],
            'parties.*.principal' => ['nullable', 'numeric', 'min:0'],
            'parties.*.penalty_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'parties.*.received_before' => ['nullable', 'numeric', 'min:0'],
            'pi_target' => ['required', 'numeric', 'min:1'],
            'total_cap' => ['required', 'numeric', 'min:1'],
            'counted_from' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'drop_broker' => ['nullable', 'boolean'],
            'mode' => ['required', 'in:preview,save'],
        ]);
        $from = \Illuminate\Support\Carbon::parse($data['counted_from'])->toDateString();
        [$parties, $problems] = $splitter->prepare($data['parties'], (float) $data['pi_target'], (float) $data['total_cap'], $from);
        if ($problems) {
            return back()->withInput()->withErrors(['facility' => implode(' ', $problems)]);
        }
        if ($data['mode'] === 'preview') {
            $status = $splitter->status($splitter->draft($loan, $parties, (float) $data['pi_target'], (float) $data['total_cap'], $from));

            return back()->withInput()->with('facility_preview', $status);
        }
        $n = $splitter->save($loan, $parties, (float) $data['pi_target'], (float) $data['total_cap'], $from, $data['note'] ?? null,
            (bool) ($data['drop_broker'] ?? false), $request->user());

        return back()->with('alert', ['type' => 'success', 'title' => 'Facility split saved',
            'message' => 'Loan #' . $loan->id . ': payments from ' . \Illuminate\Support\Carbon::parse($from)->format('j M Y') . ' are split by it. ' . $n . ' journal row(s) posted.']);
    }

    public function destroy(Request $request, Loan $loan, FacilitySplitter $splitter): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $n = $splitter->remove($loan, $request->user());

        return back()->with('alert', ['type' => 'success', 'title' => 'Facility split removed',
            'message' => 'Loan #' . $loan->id . ' is back on the normal split. ' . $n . ' journal row(s) posted.']);
    }
}
