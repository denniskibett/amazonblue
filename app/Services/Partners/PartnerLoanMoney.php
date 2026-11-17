<?php

namespace App\Services\Partners;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\User;
use App\Services\Loans\LoanFundings;
use App\Services\Requests;
use Illuminate\Support\Facades\DB;

/**
 * A partner's money paid for part of one loan (owner, 17 Nov 2026; ISIRO's 35,000 in Qatamur Mamo's loan 547). One
 * place for the partner page, partners:fund-loan and, later, payment automation:
 *  - from one of their lines, or new money: a short-term line opened for it, dated the day it went in;
 *  - that part comes out of the house's part of the loan and every repayment of the loan is re-split;
 *  - a line whose loans are all repaid closes, and when the partner already took their part from the borrower's
 *    repayment (paid_on + paid_ref) its payouts are recorded as made, so nothing waits on Requests.
 * New money is idempotent: its code already in the journal, or a slice the partner already has, is skipped.
 */
class PartnerLoanMoney
{
    public function __construct(private PartnerLines $lines, private PartnerSlices $slices, private LoanFundings $fundings, private Requests $requests) {}

    /**
     * @param array{line?: ?int, code?: ?string, date?: ?string, disbursement?: ?int, share?: ?float, penalty?: ?float, paid_on?: ?string, paid_ref?: ?string} $o
     * @return array{skipped: ?string, line: ?int, name: ?string, status: ?string, changes: string[], figures: ?array, requests: \Illuminate\Support\Collection}
     */
    public function fund(Partner $partner, Loan $loan, float $amount, array $o, ?User $by = null, string $why = 'partner page'): array
    {
        $amount = round($amount, 2);
        $code = strtoupper(trim((string) ($o['code'] ?? '')));
        $paidOn = ($o['paid_on'] ?? null) ?: null;
        $paidRef = strtoupper(trim((string) ($o['paid_ref'] ?? '')));
        $existing = ! empty($o['line']) ? Investment::findOrFail($o['line']) : null;
        if ($amount <= 0) {
            throw new \DomainException('Give an amount above 0.');
        }
        if ($existing && (int) LoanFundings::partnerOf($existing) !== $partner->id) {
            throw new \DomainException('Line #' . $existing->id . ' is not ' . $partner->name . '\'s.');
        }
        if (! $existing && $code === '') {
            throw new \DomainException('Give the M-Pesa or bank reference of their money.');
        }
        if (($paidOn === null) !== ($paidRef === '')) {
            throw new \DomainException('Give the day and the reference of the payout together.');
        }
        if ($paidOn && ! $by) {
            throw new \DomainException('Say who records the payout.');
        }
        $d = ! empty($o['disbursement'])
            ? Disbursement::where('id', $o['disbursement'])->where('loan_id', $loan->id)->first()
            : Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->orderBy('id')->first();
        if (! $d) {
            throw new \DomainException(! empty($o['disbursement']) ? 'Disbursement #' . $o['disbursement'] . ' is not on loan #' . $loan->id . '.' : 'Loan #' . $loan->id . ' has no cash disbursement.');
        }

        $rows = $this->fundings->rows($loan->id);
        $skip = fn (string $why) => ['skipped' => $why, 'line' => null, 'name' => null, 'status' => null, 'changes' => [], 'figures' => null, 'requests' => collect()];
        if (! $existing && $this->lines->codeUsed($code)) {
            return $skip($code . ' is already recorded as partner money.');
        }
        if (! $existing && $rows->contains(fn ($r) => (int) $r->partner_id === $partner->id)) {
            return $skip($partner->name . ' already has a slice of loan #' . $loan->id . '.');
        }
        $total = $rows->isEmpty() ? round((float) Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->sum('amount'), 2) : round((float) $rows->sum('amount'), 2);
        $house = $rows->isEmpty() ? $total : round((float) $rows->filter(fn ($r) => $r->is_house)->sum('amount'), 2);
        if ($amount - $house > 0.009) {
            throw new \DomainException('Only KES ' . number_format($house, 2) . ' of loan #' . $loan->id . ' is the house\'s to give.');
        }
        $houseLine = Investment::where('return_type', 'house')->orderBy('id')->value('id');
        $plan = $rows->reject(fn ($r) => $r->is_house)->map(fn ($r) => ['line' => $r->investment_id, 'amount' => (float) $r->amount, 'disbursement' => $r->disbursement_id])->values()->all();
        $reqBefore = DB::table('partner_requests')->max('id') ?? 0;

        return DB::transaction(function () use ($partner, $loan, $amount, $o, $by, $why, $code, $paidOn, $paidRef, $existing, $d, $plan, $house, $houseLine, $reqBefore) {
            $line = $existing ?? $this->lines->open($partner, $amount, 'short_term', null, $code,
                (string) (($o['date'] ?? null) ?: substr((string) $d->disburse_date, 0, 10)), $by, 'Their money for loan #' . $loan->id,
                isset($o['share']) && $o['share'] !== '' ? (float) $o['share'] : null, isset($o['penalty']) && $o['penalty'] !== '' ? (float) $o['penalty'] : null);
            $plan[] = ['line' => $line->id, 'amount' => $amount, 'disbursement' => $d->id];
            if ($house - $amount > 0.009) {
                $plan[] = ['line' => $houseLine, 'amount' => round($house - $amount, 2), 'disbursement' => null];
            }
            $changes = $this->slices->slices($loan, $plan, $by, $why);
            $this->lines->closeIfDone($line->fresh());

            // the partner took their part from the borrower's repayment: the closed line's payouts are already made
            $new = fn () => DB::table('partner_requests')->where('id', '>', $reqBefore)->orderBy('id')->get(['id', 'type', 'wallet_slug', 'amount', 'status', 'partner_id']);
            if ($paidOn) {
                foreach ($new()->where('partner_id', $partner->id)->where('type', 'withdrawal')->where('status', 'pending') as $q) {
                    $this->requests->recordPaidPayout($partner, $q->wallet_slug ?: 'principal', (float) $q->amount, $paidOn, $paidRef, $by, $q->id,
                        'Kept from the borrower\'s repayment of loan #' . $loan->id);
                }
            }
            $line = $line->fresh();

            return ['skipped' => null, 'line' => $line->id, 'name' => $line->name, 'status' => $line->status, 'changes' => $changes,
                'figures' => $this->lines->status($line), 'requests' => $new()];
        });
    }
}
