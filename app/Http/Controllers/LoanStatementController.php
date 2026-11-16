<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use App\Services\Loans\GraceFix;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * The full loan statement (owner, 16 Nov 2026): one ledger from the first cycle to today, with the cycle as a column
 * and a running balance. Charges are money sent, each cycle's interest and penalty; payments are what the borrower
 * paid (and discounts or write-offs). Rollover rows only move the balance between cycles, so they are not money
 * lines. Each cycle starts on its principal and ends on the balance the loan engine works out for it; any
 * difference shows as an adjustment line, so the statement always agrees with the loan page.
 */
class LoanStatementController extends Controller
{
    private const KIND = ['discount' => 'Discount', 'bad_debt' => 'Written off', 'non_cash' => 'Non-cash credit'];

    /** How long a shared statement link works (owner, 16 Nov 2026). */
    public const SHARE_DAYS = 30;

    public function show(int $user, int $loan): View
    {
        $loan = Loan::with(['user', 'loanType', 'cycles'])->where('user_id', $user)->findOrFail($loan);

        return view('loans.statement', $this->data($loan) + ['share' => self::share($loan)]);
    }

    /**
     * The borrower's statement link (owner, 16 Nov 2026): url, until, and a WhatsApp link with a short message.
     * Used by the full statement page and the loan page.
     */
    public static function share(Loan $loan): array
    {
        $until = now()->addDays(self::SHARE_DAYS)->endOfDay();
        $url = URL::temporarySignedRoute('statement.shared', $until, ['loan' => $loan->id]);
        $phone = preg_replace('/\D/', '', (string) $loan->user?->phone);
        $phone = str_starts_with($phone, '0') ? '254' . substr($phone, 1) : $phone;
        $text = 'Hello ' . trim(explode(' ', (string) $loan->user?->name)[0]) . ', here is your Aimzone loan statement for loan #' . $loan->id
            . '. You can view, print or save it as a PDF: ' . $url;

        return ['url' => $url, 'until' => $until, 'wa' => strlen($phone) >= 11 ? 'https://wa.me/' . $phone . '?text=' . rawurlencode($text) : null];
    }

    /** The borrower's link: no sign-in, the signature and expiry are checked by the route (owner, 16 Nov 2026). */
    public function shared(int $loan): View
    {
        $loan = Loan::with(['user', 'loanType', 'cycles'])->findOrFail($loan);
        \Illuminate\Support\Facades\Log::info('Statement link opened', ['loan' => $loan->id, 'ip' => request()->ip()]);

        return view('loans.statement-shared', $this->data($loan));
    }

    private function data(Loan $loan): array
    {
        $cycles = $loan->cycles->sortBy('cycle_number')->values();
        $firstCycleId = $cycles->first()?->id;
        $byCycle = fn ($rows) => $rows->groupBy(fn ($r) => $r->loan_cycle_id ?: $firstCycleId);
        $disbursements = $byCycle(Disbursement::where('loan_id', $loan->id)->orderBy('disburse_date')->orderBy('id')->get());
        $repayments = $byCycle(Repayment::where('loan_id', $loan->id)->orderBy('repayment_date')->orderBy('id')->get());
        $isCash = fn ($x) => in_array($x->kind, ['cash', null], true);
        // how, which bank (owner, 16 Nov 2026) and the reference: "Bank → M-Pesa · Equity Bank · 5291DJTU6274"
        $ref = fn ($x) => implode(' · ', array_filter([$x->mode ? \App\Support\PaymentModes::label($x->mode) : null,
            \App\Support\Banks::route($x, $x instanceof Disbursement), $x->transaction])) ?: null;

        $lines = [];
        $balance = 0.0;
        $totals = ['lent' => 0.0, 'interest' => 0.0, 'penalty' => 0.0, 'paid' => 0.0];
        $last = null;

        foreach ($cycles as $i => $c) {
            $b = CycleBalance::at($c);
            $n = (int) $c->cycle_number;
            $start = Carbon::parse($c->start_date)->toDateString();
            $due = Carbon::parse($c->due_date)->toDateString();
            $rate = rtrim(rtrim(number_format((float) $c->interest_rate, 2), '0'), '.');
            $rows = [];   // [date, order, text, ref, charge, payment, note]

            // how the cycle starts: the money sent (cycle 1) or the balance carried in, plus any top-up
            $sent = 0.0;
            if ($i > 0) {
                $rows[] = [$start, 0, 'Rolled over from #' . ($n - 1), null, 0.0, 0.0, true];
            }
            foreach ($disbursements->get($c->id, collect())->filter($isCash) as $d) {
                $rows[] = [Carbon::parse($d->disburse_date)->toDateString(), 1, $i === 0 ? 'Loan sent' : 'Top-up sent', $ref($d), (float) $d->amount, 0.0, false];
                $sent += (float) $d->amount;
            }
            $totals['lent'] += $sent;
            $gap = round((float) $b['principal'] - ($i === 0 ? 0 : $balance) - $sent, 2);
            if (abs($gap) >= 0.01) {
                $why = $this->reason($c, '/written|write|waiv|discount|adjust|merged|plan|forbear/i')
                    ?? ($gap < 0 ? 'less was carried than was owed' : 'more was carried than was owed');
                $rows[] = [$start, 2, $i === 0 ? ($sent == 0.0 ? 'Loan principal' : 'Principal adjustment') : 'Carry-over adjustment', $why, max(0, $gap), max(0, -$gap), false];
                if ($i === 0 && $sent == 0.0) {
                    $totals['lent'] += $gap;
                }
            }

            // interest is posted at the end of the cycle, on the principal the cycle lent (owner, 16 Nov 2026)
            $rows[] = [$due, 6, 'Interest ' . $rate . '% on ' . number_format((float) $b['principal'], 2), null, (float) $b['interest'], 0.0, false];
            $totals['interest'] += (float) $b['interest'];
            if ((float) $b['penalty'] > 0) {
                $rows[] = [$b['last_penalty_day'] ?? $due, 7, 'Late penalty' . ($b['days_subject_to_penalty'] ? ' · ' . $b['days_subject_to_penalty'] . ' days' : ''), null, (float) $b['penalty'], 0.0, false];
                $totals['penalty'] += (float) $b['penalty'];
            }
            // forbearance or a payment plan paused the penalty: say so, why, and what it saved the borrower
            if ($c->penalty_waived_until && $c->penalty_waived_until->toDateString() > $due) {
                $waived = $this->penaltyWithoutPause($c) - (float) $b['penalty'];
                $why = $this->reason($c, '/forbear|payment plan|plan|waiv|pay by/i') ?? $this->forbearanceNote($loan, $c);
                $kind = preg_match('/plan/i', (string) $why) ? 'Payment plan' : 'Forbearance';
                $until = $c->penalty_waived_until->toDateString() >= GraceFix::OPEN_ENDED ? 'open-ended' : 'to ' . $c->penalty_waived_until->format('j M Y');
                // one line (owner, 16 Nov 2026): what, how long, what it saved, then why
                $rows[] = [$due, 8, $kind, implode(' · ', array_filter([$until, $waived >= 0.01 ? number_format($waived, 2) . ' waived' : null,
                    $why ? preg_replace(['/\s*\((owner|staff)[^)]*\)/i', '/^(forbearance|payment plan)[,:\s-]*/i'], '', $why) : null])), 0.0, 0.0, true];
            }
            foreach ($repayments->get($c->id, collect()) as $r) {
                if ($r->kind === 'rollover' && $b['carried_forward'] !== null) {
                    continue;   // the carry itself, not money paid
                }
                $rows[] = [Carbon::parse($r->repayment_date)->toDateString(), 4, $isCash($r) ? 'Payment received' : (self::KIND[$r->kind] ?? 'Credit'), $ref($r), 0.0, (float) $r->amount, false];
                if ($isCash($r)) {
                    $totals['paid'] += (float) $r->amount;
                }
            }

            usort($rows, fn ($a, $z) => [$a[0], $a[1]] <=> [$z[0], $z[1]]);
            foreach ($rows as [$date, , $text, $refText, $charge, $payment, $note]) {
                $refText = $refText ? Str::limit($refText, 160) : null;
                $balance = round($balance + $charge - $payment, 2);
                $lines[] = (object) ['date' => $date, 'n' => $n, 'text' => $text, 'ref' => $refText, 'charge' => $charge, 'payment' => $payment, 'balance' => $balance, 'note' => $note];
            }

            // end the cycle on the engine's figure (overpayments, allocation rounding)
            $owed = round((float) ($b['owed_on_close'] ?? $b['final_outstanding']), 2);
            if (abs($balance - $owed) >= 0.01) {
                $diff = round($owed - $balance, 2);
                $balance = $owed;
                $why = (float) $b['overpaid'] >= 0.01 ? 'overpaid by KES ' . number_format((float) $b['overpaid'], 2)
                    : (abs($diff) < 1 ? 'rounding' : (Str::limit((string) $this->reason($c, '/written|write|waiv|discount|adjust|cap|plan|forbear/i'), 70) ?: 'how payments were applied'));
                $lines[] = (object) ['date' => $b['closed_on'] ?? now()->toDateString(), 'n' => $n, 'text' => 'Adjustment', 'ref' => $why,
                    'charge' => max(0, $diff), 'payment' => max(0, -$diff), 'balance' => $balance, 'note' => false];
            }
            $last = $b;
        }

        return [
            'status' => $this->status($loan, $cycles->last()),
            'loan' => $loan,
            'lines' => $lines,
            'cycleCount' => $cycles->count(),
            'balance' => $last ? (float) $last['final_outstanding'] : 0.0,
            'dueDate' => $cycles->last()?->due_date,
            'totals' => $totals,
        ];
    }

    /** The latest line of the cycle's notes that matches, e.g. "Payment plan agreed by the owner … PENALTIES WAIVED". */
    private function reason(LoanCycle $c, string $pattern): ?string
    {
        $parts = preg_split('/\n|\s\|\s/', (string) $c->notes) ?: [];
        $hit = collect($parts)->map(fn ($p) => trim($p))->filter(fn ($p) => $p !== '' && preg_match($pattern, $p))->last();

        return $hit ?: null;
    }

    /** The reason staff gave when granting forbearance ("Forbearance granted: <reason> until <date>"). */
    private function forbearanceNote(Loan $loan, LoanCycle $c): ?string
    {
        preg_match_all('/Forbearance granted: (.+?) until (\d{4}-\d{2}-\d{2})/', (string) $loan->recovery_notes, $m, PREG_SET_ORDER);
        $hit = collect($m)->first(fn ($x) => $x[2] === $c->penalty_waived_until->toDateString()) ?? collect($m)->last();

        return $hit[1] ?? null;
    }

    /** The penalty the cycle would carry with no pause, to show what forbearance saved. */
    private function penaltyWithoutPause(LoanCycle $c): float
    {
        $copy = $c->replicate();
        $copy->id = $c->id;
        $copy->penalty_waived_until = null;

        return (float) CycleBalance::at($copy)['penalty'];
    }

    /** The loan's status in words, for the statement (owner, 16 Nov 2026). */
    private function status(Loan $loan, ?LoanCycle $cycle): array
    {
        $until = $cycle?->penalty_waived_until && $cycle->penalty_waived_until->toDateString() < GraceFix::OPEN_ENDED
            ? 'until ' . $cycle->penalty_waived_until->format('j M Y') : 'for now';
        $due = $cycle ? $cycle->due_date->format('j M Y') : null;
        [$label, $tone, $means] = match ($loan->status) {
            Loan::STATUS_FORBEARANCE => ['Forbearance', 'bg-amber-100 text-amber-800',
                'We have paused late penalties ' . $until . ' to give you time to pay. The balance below is still owed and must be paid.'],
            Loan::STATUS_OVERDUE => ['Overdue', 'bg-red-100 text-red-700',
                'The due date' . ($due ? ' (' . $due . ')' : '') . ' has passed. A late penalty is added each day until the balance is paid.'],
            Loan::STATUS_DEFAULTED => ['Defaulted', 'bg-red-100 text-red-700',
                'The loan has stayed unpaid past the default period. The full balance is due now and may be passed to recovery.'],
            Loan::STATUS_RECOVERY => ['In recovery', 'bg-red-100 text-red-700',
                'The loan is with our recovery team. Pay the balance or agree a plan with us to stop further action.'],
            Loan::STATUS_REPAID => ['Repaid', 'bg-emerald-100 text-emerald-700', 'The loan is paid in full. Nothing more is owed.'],
            Loan::STATUS_WRITTEN_OFF => ['Written off', 'bg-gray-200 text-gray-700',
                'We have written the balance off in our books. This does not cancel the debt.'],
            Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE => ['Running', 'bg-blue-100 text-blue-700',
                'The loan is running. Pay the balance by ' . ($due ?? 'the due date') . ' to avoid a late penalty, or ask us to extend it.'],
            default => [ucfirst(str_replace('_', ' ', (string) $loan->status)), 'bg-gray-100 text-gray-700', 'The loan has not been paid out yet.'],
        };

        return ['label' => $label, 'tone' => $tone, 'means' => $means];
    }
}
