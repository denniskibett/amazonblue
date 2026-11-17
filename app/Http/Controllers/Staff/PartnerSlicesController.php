<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Partner;
use App\Services\Loans\LoanFundings;
use App\Services\Partners\PartnerLoanMoney;
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

    /**
     * Their money paid for part of a loan: from one of their lines, or new money (a short-term line opened for it).
     * When they already took their part from the borrower's repayment, its payouts are recorded as made (owner, 17 Nov 2026).
     */
    public function fund(Request $request, Partner $partner, PartnerLoanMoney $money): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $data = $request->validate(['source' => 'nullable|in:line,new', 'line' => 'required_unless:source,new|nullable|integer', 'loan' => 'required|integer',
            'disbursement' => 'nullable|integer', 'amount' => 'required|numeric|min:1',
            'code' => 'required_if:source,new|nullable|string|max:60', 'date' => 'nullable|date|before_or_equal:today',
            'share' => 'nullable|numeric|min:0|max:100', 'penalty' => 'nullable|numeric|min:0|max:100',
            'paid' => 'nullable|boolean', 'paid_on' => 'required_if:paid,1|nullable|date|before_or_equal:today', 'paid_ref' => 'required_if:paid,1|nullable|string|max:60',
            'mode' => 'nullable|in:preview,save']);
        $new = ($data['source'] ?? 'line') === 'new';
        $loan = Loan::findOrFail($data['loan']);
        $paid = $new && ! empty($data['paid']);
        $o = ['line' => $new ? null : $data['line'], 'disbursement' => $data['disbursement'] ?? null,
            'code' => $new ? $data['code'] : null, 'date' => $new ? ($data['date'] ?? null) : null,
            'share' => $new ? ($data['share'] ?? null) : null, 'penalty' => $new ? ($data['penalty'] ?? null) : null,
            'paid_on' => $paid ? $data['paid_on'] : null, 'paid_ref' => $paid ? $data['paid_ref'] : null];
        $skipped = null;

        $response = $this->run($request, function () use ($money, $partner, $loan, $data, $o, $request, &$skipped) {
            $r = $money->fund($partner, $loan, (float) $data['amount'], $o, $request->user(), 'partner page');
            $skipped = $r['skipped'];

            return $r;
        }, 'KES ' . number_format((float) $data['amount'], 2) . ' of loan #' . $loan->id . ' is now ' . $partner->name . '\'s' . ($paid ? ', paid back to them on ' . $o['paid_on'] : '') . '.');

        return $skipped ? back()->withInput()->with('error', $skipped . ' Nothing saved.') : $response;
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
