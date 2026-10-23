<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\BorrowerAnalytics;
use App\Services\BorrowingEligibility;
use App\Services\DisbursementService;
use App\Services\Ledger;
use App\Services\LoanCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Top-up (owner, 23 Oct 2026): more money on a loan that is being repaid on time, as a new cycle on the same loan.
 *
 *  - Who: a disbursed loan that is not late, not in default or forbearance, whose current cycle is at least half paid
 *    in cash, and whose borrower has nothing gone bad in their history.
 *  - How much: up to the borrower's limit less what they owe today, and at least the minimum loan amount.
 *  - What is booked (same as a rollover, plus fresh cash): the current cycle closes today; the new cycle lends what
 *    was owed today + the top-up, at the current rate, from today for one product period; the top-up is a cash
 *    disbursement on the new cycle. The ledger already treats cash sent on a later cycle as that cycle's new money
 *    (Ledger::planLoanCycle, LoanBalancer::newMoney), so the carry nets to zero and nothing is counted twice.
 *  - preview() and execute() run the same plan(), so what the borrower is shown is what is booked.
 */
class TopUp
{
    public const STATUSES = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE];

    /** Half of the current cycle (principal + interest) paid in cash before a top-up. */
    public const PAID_SHARE = 0.5;

    /** Why this loan can't be topped up now (empty = it can), plus the most that can be added. */
    public function check(Loan $loan, ?Carbon $day = null, ?int $requestId = null): array
    {
        $day = ($day ?? now())->copy()->startOfDay();
        $why = [];
        $cycle = LoanCycle::where('loan_id', $loan->id)->orderByDesc('cycle_number')->first();
        $b = $cycle ? CycleBalance::at($cycle, $day->toDateString()) : null;

        if (! LoanCalculator::v2()) {
            $why[] = 'Top-ups need the current loan engine.';
        }
        if (! in_array($loan->status, self::STATUSES, true) || $loan->default_triggered) {
            $why[] = 'Only a running loan that is up to date can be topped up.';
        }
        if (! $cycle || $cycle->status !== 'active') {
            $why[] = 'This loan has no running cycle.';
        } elseif ($day->gt(Carbon::parse($cycle->due_date)->startOfDay())) {
            $why[] = 'The loan is past its due date. Pay what is due first.';
        }
        $owed = round((float) ($b['final_outstanding'] ?? 0), 2);
        $total = $cycle ? round($cycle->lentPrincipal() * (1 + (float) $cycle->interest_rate / 100), 2) : 0.0;
        $paid = $cycle ? round((float) DB::table('repayments')->whereNull('deleted_at')->where('loan_cycle_id', $cycle->id)->get(['amount', 'kind', 'transaction'])
            ->filter(fn ($r) => \App\Services\CustomerWallets::kindOf($r) === 'cash')->sum('amount'), 2) : 0.0;
        if ($cycle && $total > 0 && $paid < $total * self::PAID_SHARE - 0.005) {
            $why[] = 'Pay at least half of this cycle first (KES ' . number_format($total * self::PAID_SHARE, 2) . '; paid KES ' . number_format($paid, 2) . ').';
        }
        $profile = BorrowerAnalytics::profileFor((int) $loan->user_id);
        if ($profile && (int) $profile->bad_loans > 0) {
            $why[] = 'A previous loan went unpaid, so top-ups are not available.';
        }
        if (DB::table('borrower_requests')->where('loan_id', $loan->id)->where('type', 'topup')->where('status', 'open')
            ->when($requestId, fn ($q) => $q->where('id', '!=', $requestId))->exists()) {
            $why[] = 'A top-up request on this loan is already waiting for us.';
        }
        $user = User::find($loan->user_id);
        $limit = $user ? (int) (app(BorrowingEligibility::class)->limit($user)['limit'] ?? 0) : 0;
        $max = max(0, (int) (floor(($limit - $owed) / 500) * 500));
        $min = (int) config('lending.min_amount', 1000);
        if ($max < $min) {
            $why[] = 'Your limit (KES ' . number_format($limit, 2) . ') leaves no room above what you owe (KES ' . number_format($owed, 2) . ').';
        }

        return ['ok' => ! $why, 'why' => array_values(array_unique($why)), 'owed' => $owed, 'paid' => $paid, 'cycle_total' => $total,
            'limit' => $limit, 'max' => $max, 'min' => $min, 'cycle' => $cycle];
    }

    /** What a top-up of $amount would book today. */
    public function plan(Loan $loan, float $amount, ?Carbon $day = null, ?int $requestId = null): array
    {
        $loan->loadMissing('loanType');
        $day = ($day ?? now())->copy()->startOfDay();
        $c = $this->check($loan, $day, $requestId);
        if (! $c['ok']) {
            throw new \DomainException($c['why'][0]);
        }
        $amount = round($amount, 2);
        if ($amount < $c['min'] || $amount > $c['max']) {
            throw new \DomainException('Choose an amount between KES ' . number_format($c['min'], 2) . ' and KES ' . number_format($c['max'], 2) . '.');
        }
        $type = $loan->loanType ?? throw new \DomainException('Loan type not found.');
        $cycle = $c['cycle'];
        $rate = (float) ($cycle->interest_rate ?? $type->interest_rate);
        $principal = round($c['owed'] + $amount, 2);
        $interest = round($principal * $rate / 100, 2);
        $due = Period::add($day, (int) $type->period, (string) $type->unit);

        return [
            'cycle' => $cycle, 'owed' => $c['owed'], 'amount' => $amount, 'principal' => $principal, 'interest_rate' => $rate,
            'interest' => $interest, 'new_balance' => round($principal + $interest, 2), 'start_date' => $day->toDateString(),
            'due_date' => $due->toDateString(), 'new_cycle' => $cycle->cycle_number + 1,
            'penalty_rate' => (float) $type->penalty_rate, 'grace_days' => (int) ($type->grace_period_days ?? 0),
            'threshold_days' => (int) ($loan->npl_trigger_threshold ?: ($type->default_threshold_days ?? 30)),
        ];
    }

    /**
     * Books the top-up and records the money sent. $sent: transaction (M-Pesa code), mode, disburse_date.
     * Returns the plan plus the new cycle and the disbursement.
     */
    public function execute(Loan $loan, float $amount, array $sent, User $by, ?int $requestId = null): array
    {
        $result = DB::transaction(function () use ($loan, $amount, $sent, $by, $requestId) {
            $locked = Loan::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            LoanCycle::where('loan_id', $loan->id)->lockForUpdate()->get();
            $plan = $this->plan($locked, $amount, null, $requestId);
            $old = $plan['cycle'];
            $b = CycleBalance::at($old, $plan['start_date']);

            $old->update(['status' => 'completed', 'closed_on' => $plan['start_date'], 'closing_balance' => $b['final_outstanding'],
                'penalty_accrued' => $b['penalty'], 'principal_paid' => $b['principal_paid'], 'interest_paid' => $b['interest_paid'],
                'penalty_paid' => $b['penalty_paid'], 'balance_as_of' => $plan['start_date']]);

            $new = LoanCycle::create([
                'loan_id' => $locked->id, 'cycle_number' => $plan['new_cycle'],
                'previous_balance' => $plan['principal'], 'principal' => $plan['principal'],
                'interest_capitalized' => $plan['interest'], 'new_balance' => $plan['new_balance'],
                'interest_rate' => $plan['interest_rate'], 'penalty_rate' => $plan['penalty_rate'], 'grace_days' => $plan['grace_days'],
                'default_threshold_days' => $plan['threshold_days'], 'start_date' => $plan['start_date'], 'due_date' => $plan['due_date'],
                'status' => 'active',
                'notes' => 'Top-up: cycle ' . $plan['new_cycle'] . ' carries KES ' . number_format($plan['owed'], 2) . ' + KES ' . number_format($plan['amount'], 2) . ' new money'
                    . ($requestId ? ' (request #' . $requestId . ')' : ''),
            ]);

            // the same funding line as the loan's first cash, so partner capital and house money stay right
            $first = Disbursement::where('loan_id', $locked->id)->where('kind', 'cash')->orderBy('id')->first();
            $line = $first?->investment_id ? Investment::find($first->investment_id) : null;
            $line?->assertCanDisburse($plan['amount'], null);
            $disbursement = app(DisbursementService::class)->createDisbursement($locked, [
                'amount' => $plan['amount'], 'transaction' => $sent['transaction'], 'kind' => 'cash', 'mode' => $sent['mode'] ?? 'mpesa',
                'disburse_date' => $plan['start_date'], 'payment_date' => $sent['disburse_date'] ?? $plan['start_date'],
                'investment_id' => $line?->id, 'funding_source' => $first?->funding_source,
                'notes' => 'Top-up on loan #' . $locked->id . ' (cycle ' . $plan['new_cycle'] . ')',
            ], $new);
            $line?->recordFundingDisbursement($plan['amount'], null);

            $locked->forceFill(['cycle' => $plan['new_cycle'], 'due_date' => $plan['due_date'], 'calculated_due_date' => $plan['due_date'],
                'status' => Loan::STATUS_DISBURSED, 'days_overdue' => 0, 'is_non_performing' => false])->save();
            LoanSync::fromCycles($locked);
            AuditTrail::record('loans', $locked->id, 'update', ['cycle' => $old->cycle_number], ['event' => 'top_up', 'cycle' => $plan['new_cycle'],
                'carried' => $plan['owed'], 'top_up' => $plan['amount'], 'principal' => $plan['principal'], 'due_date' => $plan['due_date'],
                'disbursement_id' => $disbursement->id, 'by' => $by->id]);

            return $plan + ['created' => $new, 'disbursement' => $disbursement];
        });

        // the closed cycle's carry and the new cycle's charges (the carry nets to zero: the cash is the new cycle's)
        if (Ledger::enabled() && LoanCalculator::v2()) {
            foreach ([$result['cycle']->fresh(), $result['created']->fresh()] as $c) {
                app(Ledger::class)->syncLoanCycle($c);
            }
        }

        return $result;
    }
}
