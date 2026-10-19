<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Models\User;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Bavix wallets for customers, kept in step with real cash so they can be reconciled.
 *
 * Borrowers: one cash wallet ("borrower_loan"). Cash sent to the borrower is withdrawn, cash they
 * repay is deposited, so balance = cash repaid - cash received. Only real cash is posted: rollovers,
 * credit discounts and bad-debt write-offs move balances between periods or write them off, but no
 * money changes hands, so they stay out of the wallet (and are reported separately).
 *
 * Every posting carries its source in meta (e.g. repayment #812), so syncing is idempotent: the
 * wallet is brought to the amount the source should contribute now (edits post the difference,
 * deletions reverse it, restores repost it).
 *
 * Partners: wallets are fed by the partner journal (PartnerLedger); this class makes sure every
 * partner has them and checks they match the journal.
 */
class CustomerWallets
{
    public const BORROWER_WALLET = 'borrower_loan';
    public const PARTNER_WALLETS = ['principal', 'interest', 'broker_fee', 'investment_tracker'];

    // words in a transaction reference that mean no money changed hands, checked in this order
    public const NON_CASH = [
        'rollover' => ['ROLL', 'CARRY FORWARD'],
        'discount' => ['DISCOUNT', 'WAIVE'],
        'bad_debt' => ['BAD DEBT', 'WRITE OFF', 'WRITE-OFF'],
    ];

    // what was actually sent for a disbursement (net of fees when recorded)
    public const DISBURSED_CASH_SQL = 'COALESCE(NULLIF(net_amount, 0), amount)';

    // ---------------------------------------------------------------- cash rules

    /**
     * cash | rollover | discount | bad_debt, from the transaction reference staff record.
     */
    public static function kind(?string $reference): string
    {
        $ref = strtoupper((string) $reference);
        foreach (self::NON_CASH as $kind => $markers) {
            foreach ($markers as $marker) {
                if (str_contains($ref, $marker)) {
                    return $kind;
                }
            }
        }

        return 'cash';
    }

    /**
     * Limit a query to real cash: the SQL twin of kind() === 'cash'.
     */
    public static function whereCash($query, string $column = 'transaction')
    {
        return $query->where(function ($q) use ($column) {
            $q->whereNull($column)->orWhere(function ($q) use ($column) {
                foreach (array_merge(...array_values(self::NON_CASH)) as $marker) {
                    $q->whereRaw('UPPER(' . $q->getGrammar()->wrap($column) . ') NOT LIKE ?', ['%' . $marker . '%']);
                }
            });
        });
    }

    /**
     * Deleted (hard, or soft where the model supports it) or marked deleted in the table.
     */
    public static function isGone($model): bool
    {
        return ! $model->exists
            || (method_exists($model, 'trashed') && $model->trashed())
            || ! empty($model->getAttribute('deleted_at'));
    }

    // ---------------------------------------------------------------- wallets

    public function borrowerWallet(User $user): Wallet
    {
        return $user->getWallet(self::BORROWER_WALLET) ?? $user->createWallet([
            'name' => 'Loan account (cash)',
            'slug' => self::BORROWER_WALLET,
            'decimal_places' => 2,
        ]);
    }

    public function ensurePartnerWallets(Partner $partner): void
    {
        foreach (self::PARTNER_WALLETS as $slug) {
            if (! $partner->getWallet($slug)) {
                $partner->createWallet(['name' => ucfirst(str_replace('_', ' ', $slug)), 'slug' => $slug, 'decimal_places' => 4]);
            }
        }
    }

    // ---------------------------------------------------------------- posting

    public function syncDisbursement(Disbursement $disbursement): void
    {
        $cash = self::kind($disbursement->transaction) === 'cash' && ! self::isGone($disbursement);
        $amount = (float) ($disbursement->net_amount ?: $disbursement->amount);
        $this->syncSource('disbursement', $disbursement->id, $disbursement->loan_id, $cash ? -$amount : 0.0, [
            'reference' => $disbursement->transaction,
            'date' => optional($disbursement->disburse_date)->toDateString() ?? (string) $disbursement->disburse_date,
        ]);
    }

    public function syncRepayment(Repayment $repayment): void
    {
        $cash = self::kind($repayment->transaction) === 'cash' && ! self::isGone($repayment);
        $this->syncSource('repayment', $repayment->id, $repayment->loan_id, $cash ? (float) $repayment->amount : 0.0, [
            'reference' => $repayment->transaction,
            'date' => optional($repayment->repayment_date)->toDateString() ?? (string) $repayment->repayment_date,
        ]);
    }

    /**
     * Bring the borrower's wallet to what this source should contribute (signed KES); returns the minor units posted.
     */
    public function syncSource(string $source, int $sourceId, ?int $loanId, float $target, array $meta = []): int
    {
        $loan = $loanId ? Loan::withTrashed()->find($loanId) : null;
        $user = $loan?->user_id ? User::withTrashed()->find($loan->user_id) : null;
        if (! $user) {
            return 0;
        }

        return DB::transaction(function () use ($user, $source, $sourceId, $loanId, $target, $meta) {
            $wallet = $this->borrowerWallet($user);
            // one posting at a time per wallet, so concurrent saves can't double-post
            Wallet::whereKey($wallet->getKey())->lockForUpdate()->first();

            $dp = (int) ($wallet->decimal_places ?? 2);
            $targetMinor = (int) round($target * (10 ** $dp));
            $postedMinor = $this->postedMinor($wallet, $source, $sourceId);
            $diff = $targetMinor - $postedMinor;
            if ($diff === 0) {
                return 0;
            }

            $payload = array_filter([
                'source' => $source,
                'source_id' => $sourceId,
                'loan_id' => $loanId,
                'reference' => $meta['reference'] ?? null,
                'date' => $meta['date'] ?? null,
                'kind' => $postedMinor === 0 ? 'posting' : ($targetMinor === 0 ? 'reversal' : 'adjustment'),
                'description' => ucfirst($source) . ' #' . $sourceId . ($loanId ? ' · loan #' . $loanId : ''),
            ], fn ($v) => $v !== null && $v !== '');

            $diff > 0 ? $wallet->deposit($diff, $payload) : $wallet->forceWithdraw(-$diff, $payload);

            return $diff;
        });
    }

    /**
     * Net minor units already posted to the wallet for one source record.
     */
    public function postedMinor(Wallet $wallet, string $source, int $sourceId): int
    {
        $rows = Transaction::where('wallet_id', $wallet->getKey())
            ->where('meta->source', $source)
            ->where('meta->source_id', $sourceId)
            ->get(['type', 'amount']);

        return (int) $rows->sum(fn ($t) => $t->type === Transaction::TYPE_DEPOSIT ? abs((int) $t->amount) : -abs((int) $t->amount));
    }

    /**
     * Post every disbursement and repayment of a borrower's loans (deleted ones included, so reversals apply).
     *
     * @return array{postings: int, minor: int}
     */
    public function syncBorrower(User $user): array
    {
        $this->borrowerWallet($user);
        $loanIds = Loan::withTrashed()->where('user_id', $user->id)->pluck('id');
        $postings = 0;
        $minor = 0;

        foreach (Disbursement::whereIn('loan_id', $loanIds)->orderBy('id')->get() as $d) {
            $amount = (float) ($d->net_amount ?: $d->amount);
            $posted = $this->syncSource('disbursement', $d->id, $d->loan_id, (self::kind($d->transaction) === 'cash' && ! self::isGone($d)) ? -$amount : 0.0,
                ['reference' => $d->transaction, 'date' => (string) $d->disburse_date]);
            $postings += $posted !== 0 ? 1 : 0;
            $minor += $posted;
        }
        foreach (Repayment::withTrashed()->whereIn('loan_id', $loanIds)->orderBy('id')->get() as $r) {
            $posted = $this->syncSource('repayment', $r->id, $r->loan_id, (self::kind($r->transaction) === 'cash' && ! self::isGone($r)) ? (float) $r->amount : 0.0,
                ['reference' => $r->transaction, 'date' => (string) $r->repayment_date]);
            $postings += $posted !== 0 ? 1 : 0;
            $minor += $posted;
        }

        return ['postings' => $postings, 'minor' => $minor];
    }

    // ---------------------------------------------------------------- reading

    /**
     * A borrower's cash position from their wallet (KES): received, repaid and the net balance.
     */
    public function borrowerCash(User $user): array
    {
        $wallet = $user->getWallet(self::BORROWER_WALLET);
        if (! $wallet) {
            return ['received' => 0.0, 'repaid' => 0.0, 'balance' => 0.0];
        }
        $dp = 10 ** (int) ($wallet->decimal_places ?? 2);
        $sums = Transaction::where('wallet_id', $wallet->getKey())
            // JSON_UNQUOTE(JSON_EXTRACT()) rather than ->>, which MariaDB 10.3 (live) doesn't support
            ->selectRaw("json_unquote(json_extract(meta, '$.source')) as source, type, sum(amount) as total")
            ->groupByRaw("json_unquote(json_extract(meta, '$.source')), type")->get();
        $net = fn (string $source) => (float) $sums->where('source', $source)->sum(fn ($r) => $r->type === Transaction::TYPE_DEPOSIT ? $r->total : -abs($r->total)) / $dp;

        return [
            'received' => -$net('disbursement'),
            'repaid' => $net('repayment'),
            'balance' => (float) $wallet->balanceFloat,
        ];
    }

    // ---------------------------------------------------------------- reconciliation

    /**
     * Borrower wallets vs the loan tables (cash only), plus each wallet vs its own transaction log.
     */
    public function checkBorrowers(): array
    {
        $issues = [];
        $cashBy = fn ($rows) => $rows->groupBy('user_id')->map(fn ($g) => $g->sum('amount'));

        $disbursed = DB::table('disbursements as d')->join('loans as l', 'l.id', '=', 'd.loan_id')->whereNull('d.deleted_at')
            ->get(['l.user_id', 'd.transaction', DB::raw('coalesce(nullif(d.net_amount, 0), d.amount) as amount')])
            ->filter(fn ($r) => self::kind($r->transaction) === 'cash');
        $repaid = DB::table('repayments as r')->join('loans as l', 'l.id', '=', 'r.loan_id')->whereNull('r.deleted_at')
            ->get(['l.user_id', 'r.transaction', 'r.amount'])
            ->filter(fn ($r) => self::kind($r->transaction) === 'cash');
        $out = $cashBy($disbursed);
        $in = $cashBy($repaid);

        $userIds = $out->keys()->merge($in->keys())->unique();
        foreach ($userIds as $userId) {
            $user = User::withTrashed()->find($userId);
            $wallet = $user?->getWallet(self::BORROWER_WALLET);
            $expected = round(($in[$userId] ?? 0) - ($out[$userId] ?? 0), 2);
            $actual = $wallet ? round((float) $wallet->balanceFloat, 2) : null;
            if ($actual === null || abs($actual - $expected) >= 0.01) {
                $issues[] = ['user' => $userId, 'name' => $user?->name, 'expected' => $expected, 'wallet' => $actual];
            }
        }

        return ['checked' => $userIds->count(), 'issues' => $issues, 'integrity' => $this->integrityIssues('App\\Models\\User')];
    }

    /**
     * EVERY partner wallet vs the partner journal (partner_transactions), plus each wallet vs its own log.
     *
     * A wallet with no journal rows must hold 0, and a journal group with no wallet is reported as
     * missing: checking only the journal groups is what hid the House Account's 4.42M double count.
     */
    public function checkPartners(): array
    {
        $issues = [];
        $journal = PartnerTransaction::selectRaw('partner_id, wallet_slug, sum(amount) as total')->groupBy('partner_id', 'wallet_slug')->get()
            ->mapWithKeys(fn ($r) => [$r->partner_id . ':' . $r->wallet_slug => round((float) $r->total, 4)]);
        $wallets = Wallet::where('holder_type', (new Partner)->getMorphClass())->get()
            ->keyBy(fn ($w) => $w->holder_id . ':' . $w->slug);

        $keys = $journal->keys()->merge($wallets->keys())->unique()->sort(SORT_NATURAL)->values();
        foreach ($keys as $key) {
            [$partnerId, $slug] = explode(':', $key, 2);
            $wallet = $wallets[$key] ?? null;
            $expected = $journal[$key] ?? 0.0;
            $actual = $wallet ? round((float) $wallet->balanceFloat, 4) : null;
            if ($actual === null || abs($actual - $expected) >= 0.0001) {
                $issues[] = ['partner' => (int) $partnerId, 'wallet' => $slug, 'journal' => $expected, 'balance' => $actual];
            }
        }

        return ['checked' => $keys->count(), 'issues' => $issues, 'integrity' => $this->integrityIssues('App\\Models\\Partner'),
            'slug_mismatches' => $this->journalSlugMismatches()];
    }

    /**
     * Journal rows whose wallet posting (same reference) went to a different wallet than the row says.
     * While any exist the journal itself is wrong, so wallets must NOT be aligned to it: fix the journal
     * first (database/sql/2026_10_16_partner_journal_wallet_slug.sql).
     */
    public function journalSlugMismatches(): array
    {
        return DB::table('partner_transactions as pt')
            ->join('transactions as t', DB::raw("json_unquote(json_extract(t.meta, '$.reference'))"), '=', 'pt.reference')
            ->join('wallets as w', function ($j) {
                $j->on('w.id', '=', 't.wallet_id')->on('w.holder_id', '=', 'pt.partner_id')
                    ->where('w.holder_type', (new Partner)->getMorphClass());
            })
            ->whereColumn('pt.wallet_slug', '<>', 'w.slug')
            ->groupBy('pt.partner_id', 'pt.wallet_slug', 'w.slug')
            ->get([DB::raw('pt.partner_id as partner'), DB::raw('pt.wallet_slug as journal_says'), DB::raw('w.slug as posted_to'),
                DB::raw('count(*) as `rows`'), DB::raw('sum(pt.amount) as amount')])
            ->map(fn ($r) => (array) $r)->all();
    }

    /**
     * Wallets whose stored balance differs from the sum of their own transactions (someone set it directly).
     */
    public function integrityIssues(string $holderType): array
    {
        return DB::table('wallets as w')
            ->leftJoin('transactions as t', function ($j) {
                $j->on('t.wallet_id', '=', 'w.id')->where('t.confirmed', 1)->whereNull('t.deleted_at');
            })
            ->where('w.holder_type', $holderType)
            ->groupBy('w.id', 'w.holder_id', 'w.slug', 'w.balance', 'w.decimal_places')
            ->havingRaw("w.balance <> coalesce(sum(case when t.type = 'deposit' then t.amount else -abs(t.amount) end), 0)")
            ->get(['w.id', 'w.holder_id', 'w.slug', 'w.balance', 'w.decimal_places',
                DB::raw("coalesce(sum(case when t.type = 'deposit' then t.amount else -abs(t.amount) end), 0) as log_total")])
            ->map(fn ($w) => [
                'wallet_id' => $w->id, 'holder' => $w->holder_id, 'slug' => $w->slug,
                'balance' => (float) $w->balance / (10 ** $w->decimal_places),
                'log' => (float) $w->log_total / (10 ** $w->decimal_places),
            ])->all();
    }

    /**
     * Bring a partner wallet to its journal total with one compensating entry (never a balance edit,
     * never a deletion). The stored balance is first recalculated from the wallet's own log, so the
     * entry is computed against what the log really says. Returns the KES posted (+deposit / -withdrawal).
     *
     * Refuses while journal rows point at the wrong wallet (see journalSlugMismatches), which is what
     * made the 2026-10-15 repair deposit 4.42M into the House principal wallet.
     */
    public function correctPartnerWallet(Partner $partner, string $slug, float $journalTotal, string $reason, bool $dryRun = false): float
    {
        if ($this->journalSlugMismatches()) {
            throw new \RuntimeException('Partner journal rows point at the wrong wallet; run database/sql/2026_10_16_partner_journal_wallet_slug.sql before correcting wallets.');
        }
        $wallet = $partner->getWallet($slug);
        if (! $wallet) {
            return 0.0;
        }

        return DB::transaction(function () use ($wallet, $journalTotal, $reason, $dryRun, $partner, $slug) {
            Wallet::whereKey($wallet->getKey())->lockForUpdate()->first();
            $dp = (int) ($wallet->decimal_places ?? 4);
            $log = (int) Transaction::where('wallet_id', $wallet->getKey())->where('confirmed', 1)
                ->selectRaw("coalesce(sum(case when type = 'deposit' then amount else -abs(amount) end), 0) as total")->value('total');
            $diff = (int) round($journalTotal * (10 ** $dp)) - $log;
            if ($dryRun) {
                return $diff / (10 ** $dp);
            }

            $wallet->refreshBalance();
            $wallet->refresh();
            if ($diff !== 0) {
                $meta = [
                    'source' => 'correction',
                    'reason' => $reason,
                    'reference' => sprintf('CORR-P%d-%s-%s', $partner->id, strtoupper($slug), now()->format('YmdHis')),
                    'journal_total' => round($journalTotal, 4),
                    'description' => 'Correction: align wallet with partner journal',
                ];
                $diff > 0 ? $wallet->deposit($diff, $meta) : $wallet->forceWithdraw(-$diff, $meta);
            }

            return $diff / (10 ** $dp);
        });
    }
}
