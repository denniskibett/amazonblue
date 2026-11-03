<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Services\Loans\LoanFundings;
use App\Services\Partners\PartnerSlices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Partner page (owner, 3 Nov 2026): record which part of a loan a partner line paid for, and the house settling a
 * partner's slice. Each form has Preview (the journal rows it would post, nothing saved) and Save.
 */
class PartnerSlicesController extends Controller
{
    public function __construct(private PartnerSlices $slices, private LoanFundings $fundings) {}

    /** A line paid for $amount of disbursement $d on a loan: that part comes out of the house's part of the loan. */
    public function fund(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $data = $request->validate(['line' => 'required|integer', 'loan' => 'required|integer', 'disbursement' => 'required|integer', 'amount' => 'required|numeric|min:1',
            'mode' => 'nullable|in:preview,save']);
        $line = Investment::findOrFail($data['line']);
        abort_unless((int) LoanFundings::partnerOf($line) === $partner->id, 404);
        $loan = Loan::findOrFail($data['loan']);
        $d = Disbursement::where('id', $data['disbursement'])->where('loan_id', $loan->id)->first();
        if (! $d) {
            return back()->with('error', 'Disbursement #' . $data['disbursement'] . ' is not on loan #' . $loan->id . '.');
        }
        $rows = $this->fundings->rows($loan->id);
        $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
        $amount = round((float) $data['amount'], 2);
        $total = $rows->isEmpty() ? (float) Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->sum('amount') : (float) $rows->sum('amount');
        $house = $rows->filter(fn ($r) => $r->is_house)->sum('amount') ?: ($rows->isEmpty() ? $total : 0);
        if ($amount - $house > 0.009) {
            return back()->with('error', 'Only KES ' . number_format($house, 2) . ' of loan #' . $loan->id . ' is the house\'s to give.');
        }
        $plan = $rows->reject(fn ($r) => $r->is_house)->map(fn ($r) => ['line' => $r->investment_id, 'amount' => (float) $r->amount, 'disbursement' => $r->disbursement_id])->values()->all();
        $plan[] = ['line' => $line->id, 'amount' => $amount, 'disbursement' => $d->id];
        if ($house - $amount > 0.009) {
            $plan[] = ['line' => $houseLine, 'amount' => round($house - $amount, 2), 'disbursement' => null];
        }

        return $this->run($request, fn () => $this->slices->slices($loan, $plan, $request->user(), 'partner page'),
            'KES ' . number_format($amount, 2) . ' of loan #' . $loan->id . ' is now from ' . $line->name . '.');
    }

    /** The house paid the partner out of a slice before the borrower repaid it. */
    public function settle(Request $request, Partner $partner, int $slice): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $data = $request->validate(['date' => 'required|date|before_or_equal:today', 'interest' => 'nullable|numeric|min:0', 'note' => 'nullable|string|max:300',
            'mode' => 'nullable|in:preview,save']);
        $row = \Illuminate\Support\Facades\DB::table('loan_fundings')->where('id', $slice)->where('partner_id', $partner->id)->first() ?? abort(404);

        return $this->run($request, fn () => $this->slices->settleByHouse($row->id, $data['date'], isset($data['interest']) ? (float) $data['interest'] : null, $request->user(), $data['note'] ?? null),
            'The house settled ' . $partner->name . '\'s KES ' . number_format((float) $row->amount, 2) . ' of loan #' . $row->loan_id . '.');
    }

    private function run(Request $request, callable $fn, string $done): RedirectResponse
    {
        try {
            if ($request->input('mode') === 'preview') {
                $out = $this->slices->preview($fn);

                return back()->withInput()->with('slice_preview', collect($out['journal'])->map(fn ($r) => [$r->name, $r->wallet_slug, $r->type, (float) $r->amount,
                    $r->loan_id, substr((string) $r->transaction_date, 0, 10), $r->reference])->all());
            }
            $fn();
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $done);
    }
}
