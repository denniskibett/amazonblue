<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanCycle;
use App\Services\Loans\CycleBalance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Report figures from the cycles and the ledger (Stage 4 Phase 7), never from loans.amount:
 *   lent       cash actually sent (net of fees), per loan
 *   repaid     cash actually received, per loan
 *   owed       what the latest cycle says is owed today (v2: penalty, interest, principal)
 *   principal  unpaid principal on the latest cycle (the portfolio "at principal")
 * Plus PAR 1/30/90, non-performing loans, income by type from the ledger, partner earnings and the trial balance.
 */
class LedgerReports
{
    private ?Collection $cash = null;

    private array $balance = [];

    /** [lent, repaid] in cash for every loan, from one query each. */
    public function cash(): Collection
    {
        if ($this->cash) {
            return $this->cash;
        }
        $lent = CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at'))
            ->groupBy('loan_id')->selectRaw('loan_id, sum(' . CustomerWallets::DISBURSED_CASH_SQL . ') as total')->pluck('total', 'loan_id');
        $repaid = CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at'))
            ->groupBy('loan_id')->selectRaw('loan_id, sum(amount) as total')->pluck('total', 'loan_id');

        return $this->cash = $lent->keys()->merge($repaid->keys())->unique()
            ->mapWithKeys(fn ($id) => [$id => ['lent' => (float) ($lent[$id] ?? 0), 'repaid' => (float) ($repaid[$id] ?? 0)]]);
    }

    public function lent(int $loanId): float
    {
        return $this->cash()[$loanId]['lent'] ?? 0.0;
    }

    public function repaid(int $loanId): float
    {
        return $this->cash()[$loanId]['repaid'] ?? 0.0;
    }

    /** The latest cycle's balance today (memoised per request). */
    public function balance(int $loanId): ?array
    {
        if (! array_key_exists($loanId, $this->balance)) {
            $cycle = LoanCycle::where('loan_id', $loanId)->orderByDesc('cycle_number')->first();
            $this->balance[$loanId] = $cycle ? CycleBalance::at($cycle) : null;
        }

        return $this->balance[$loanId];
    }

    public function owed(Loan $loan): float
    {
        if (in_array($loan->status, [Loan::STATUS_PENDING, Loan::STATUS_APPROVED, Loan::STATUS_REJECTED], true)) {
            return 0.0;
        }

        return (float) ($this->balance($loan->id)['final_outstanding'] ?? 0);
    }

    /** The portfolio summary: open loans at principal and in total, PAR, NPL, income, partners, trial balance. */
    public function portfolio(): array
    {
        $open = Loan::whereIn('status', [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE,
            Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY])->get();
        $rows = $open->map(function ($loan) {
            $b = $this->balance($loan->id);

            return ['loan' => $loan, 'owed' => (float) ($b['final_outstanding'] ?? 0), 'principal' => (float) ($b['unpaid_principal'] ?? 0),
                'days' => (int) ($b['days_overdue'] ?? 0)];
        })->filter(fn ($r) => $r['owed'] > 0)->values();

        $total = $rows->sum('owed');
        $par = fn (int $days) => $total > 0 ? round($rows->where('days', '>=', $days)->sum('owed') / $total * 100, 2) : 0.0;
        $ledger = app(Ledger::class);
        $income = DB::table('wallets as w')->join('transactions as t', 't.wallet_id', '=', 'w.id')
            ->whereIn('w.slug', ['gl-interest-income', 'gl-penalty-income', 'gl-fee-income', 'gl-rollover-adjustments', 'gl-discounts', 'gl-bad-debt', 'gl-partner-share'])
            ->where('t.confirmed', 1)->whereNull('t.deleted_at')->groupBy('w.slug')
            ->selectRaw("w.slug as slug, sum(case when t.type = 'deposit' then t.amount else -abs(t.amount) end) as total")->pluck('total', 'slug')
            ->map(fn ($v) => round((float) $v / (10 ** Ledger::dp()), 2));

        return [
            'open_loans' => $rows->count(),
            'owed' => round($total, 2),
            'principal' => round($rows->sum('principal'), 2),
            'par1' => $par(1), 'par30' => $par(30), 'par90' => $par(90),
            'par30_amount' => round($rows->where('days', '>=', 30)->sum('owed'), 2),
            'npl_loans' => $open->where('is_non_performing', true)->count(),
            'npl_owed' => round($rows->filter(fn ($r) => $r['loan']->is_non_performing)->sum('owed'), 2),
            'cash_lent' => round($this->cash()->sum('lent'), 2),
            'cash_repaid' => round($this->cash()->sum('repaid'), 2),
            // income accounts are credit balances (negative); shown as positive income, expenses as positive cost
            'interest_income' => -1 * ($income['gl-interest-income'] ?? 0),
            'penalty_income' => -1 * ($income['gl-penalty-income'] ?? 0),
            'fee_income' => -1 * ($income['gl-fee-income'] ?? 0),
            'rollover_adjustments' => -1 * ($income['gl-rollover-adjustments'] ?? 0),
            'discounts' => $income['gl-discounts'] ?? 0,
            'bad_debts' => $income['gl-bad-debt'] ?? 0,
            'partner_shares' => $income['gl-partner-share'] ?? 0,
            'partners' => DB::table('partner_transactions as pt')->join('partners as p', 'p.id', '=', 'pt.partner_id')
                ->whereIn('pt.type', ['broker_fee_earned', 'penalty_share', 'profit_distribution', 'interest'])
                ->groupBy('p.id', 'p.name')->orderBy('p.id')->get(['p.id', 'p.name', DB::raw('sum(pt.amount) as earned')])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'earned' => round((float) $r->earned, 2)])->all(),
            'trial_balance' => $ledger->trialBalance()['total'],
            'rows' => $rows,
        ];
    }
}
