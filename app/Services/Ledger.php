<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use Bavix\Wallet\External\Dto\Extra;
use Bavix\Wallet\External\Dto\Option;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * The double-entry ledger, kept in bavix wallets (Stage 4 Phase 1; accounts in config lending.ledger).
 *
 * Each source record (a disbursement, repayment or partner journal row) has a plan: a list of legs, each
 * "move this amount from account A (credit) to account B (debit)". sync() brings what is posted for the
 * source to what its plan says now, posting only the difference as bavix transfers: an edit posts the
 * change, a deletion posts the full reverse, a restore posts it again. Nothing is ever edited or deleted.
 *
 * Every transfer gets uuids derived from (source, leg, accounts, sequence), so a retry, a second worker or
 * a double callback produces the same uuids and the database refuses the duplicate.
 *
 * Phase 1 posts today's meaning only: cash vs book entry, no principal/interest/penalty split (Phase 2) and
 * no six-way allocation (Phase 3). Rows whose meaning is still undecided (rollovers, non_cash book entries)
 * are not posted; plan() says why, and ledger:backfill lists them.
 */
class Ledger
{
    // namespace for the deterministic uuids (any fixed uuid; never change it once live)
    private const UUID_NS = '6f1c2b5e-3c0a-4c7e-9a49-2f7b8d1e4a10';

    private ?Partner $house = null;

    public static function enabled(): bool
    {
        return (bool) config('lending.ledger.enabled');
    }

    public static function dp(): int
    {
        return (int) config('lending.ledger.decimal_places', 4);
    }

    public static function minor(float $amount): int
    {
        return (int) round($amount * (10 ** self::dp()));
    }

    // ---------------------------------------------------------------- accounts

    /** The house partner that holds AC's accounts. */
    public function house(): Partner
    {
        return $this->house ??= Partner::where('is_house_account', true)
            ->orderByRaw("account_type = 'principal' desc")->orderBy('id')->first()
            ?? throw new \RuntimeException('No house partner (partners.is_house_account = 1): the ledger has nowhere to keep AC accounts.');
    }

    /** The wallet for one account of one holder, created on first use. */
    public function account(Model $holder, string $slug): Wallet
    {
        $def = config("lending.ledger.accounts.{$slug}") ?? throw new \InvalidArgumentException("Unknown ledger account {$slug}.");
        $wallet = $holder->getWallet($slug) ?? $holder->createWallet([
            'name' => $def['name'],
            'slug' => $slug,
            'decimal_places' => self::dp(),
            'meta' => ['ledger' => true, 'type' => $def['type']],
        ]);
        // bavix moves the same integer out of one wallet and into the other: mixed decimals would be wrong by 10^n
        if ((int) $wallet->decimal_places !== self::dp()) {
            throw new \RuntimeException("Ledger wallet #{$wallet->id} ({$slug}) has {$wallet->decimal_places} decimal places, not " . self::dp() . '.');
        }

        return $wallet;
    }

    public function houseAccount(string $slug): Wallet
    {
        return $this->account($this->house(), $slug);
    }

    // ---------------------------------------------------------------- plans

    /**
     * What a disbursement should have posted now: ['legs' => [...], 'note' => why nothing, or null].
     * Each leg: ['leg' => name, 'from' => Wallet, 'to' => Wallet, 'amount' => KES (signed; negative = reverse)].
     */
    public function planDisbursement(Disbursement $d): array
    {
        $kind = CustomerWallets::kindOf($d);
        $loan = Loan::withTrashed()->find($d->loan_id);
        if (! $loan) {
            return ['legs' => [], 'note' => 'loan not found'];
        }
        if (CustomerWallets::isGone($d)) {
            return ['legs' => [], 'note' => 'deleted'];
        }
        if ($kind !== 'cash') {
            return ['legs' => [], 'note' => "{$kind}: book entry, posted from Phase 2"];
        }
        $gross = (float) $d->amount;
        $fee = (float) $d->processing_fee;
        $net = (float) ($d->net_amount ?: $gross - $fee);
        $receivable = $this->account($loan, 'gl-receivable');

        return ['legs' => array_values(array_filter([
            ['leg' => 'cash', 'from' => $this->houseAccount('gl-cash'), 'to' => $receivable, 'amount' => $net],
            $gross - $net != 0 ? ['leg' => 'fee', 'from' => $this->houseAccount('gl-fee-income'), 'to' => $receivable, 'amount' => round($gross - $net, 4)] : null,
        ])), 'note' => null];
    }

    public function planRepayment(Repayment $r): array
    {
        $kind = CustomerWallets::kindOf($r);
        $loan = Loan::withTrashed()->find($r->loan_id);
        if (! $loan) {
            return ['legs' => [], 'note' => 'loan not found'];
        }
        if (CustomerWallets::isGone($r)) {
            return ['legs' => [], 'note' => 'deleted'];
        }
        $debit = ['cash' => 'gl-cash', 'discount' => 'gl-discounts', 'bad_debt' => 'gl-bad-debt'][$kind] ?? null;
        if (! $debit) {
            return ['legs' => [], 'note' => "{$kind}: book entry, posted from Phase 2/3"];
        }

        return ['legs' => [
            ['leg' => $kind, 'from' => $this->account($loan, 'gl-receivable'), 'to' => $this->houseAccount($debit), 'amount' => (float) $r->amount],
        ], 'note' => null];
    }

    public function planPartnerTransaction(PartnerTransaction $pt): array
    {
        $map = config('lending.ledger.partner_types.' . $pt->type);
        $partner = Partner::find($pt->partner_id);
        if (! $map || ! $partner) {
            return ['legs' => [], 'note' => $partner ? "{$pt->type}: moves money inside the partner's own buckets, posted from Phase 3" : 'partner not found'];
        }
        [$partnerSlug, $acSlug] = $map;
        if ($partnerSlug === 'by_wallet') {
            $partnerSlug = config('lending.ledger.partner_wallets.' . $pt->wallet_slug);
            if (! $partnerSlug) {
                return ['legs' => [], 'note' => "{$pt->type} from the {$pt->wallet_slug} wallet: not a ledger account"];
            }
        }

        // a positive journal amount credits the partner: the partner account gives, the AC account receives
        return ['legs' => [
            ['leg' => $pt->type, 'from' => $this->account($partner, $partnerSlug), 'to' => $this->houseAccount($acSlug), 'amount' => (float) $pt->amount],
        ], 'note' => null];
    }

    // ---------------------------------------------------------------- posting

    public function syncDisbursement(Disbursement $d): array
    {
        return $this->sync('disbursement', $d->id, $this->planDisbursement($d)['legs'], $this->metaFor($d->disburse_date, $d->transaction, $d->loan_id));
    }

    public function syncRepayment(Repayment $r): array
    {
        return $this->sync('repayment', $r->id, $this->planRepayment($r)['legs'], $this->metaFor($r->repayment_date, $r->transaction, $r->loan_id));
    }

    public function syncPartnerTransaction(PartnerTransaction $pt): array
    {
        return $this->sync('partner_transaction', $pt->id, $this->planPartnerTransaction($pt)['legs'],
            $this->metaFor($pt->transaction_date, $pt->reference, $pt->loan_id) + ['partner_id' => $pt->partner_id]);
    }

    /**
     * Bring what is posted for one source to its plan. Returns the transfers posted:
     * [['leg', 'from' => wallet id, 'to' => wallet id, 'minor' => int], ...]. With $dryRun nothing is written.
     */
    public function sync(string $source, int $sourceId, array $legs, array $meta = [], bool $dryRun = false): array
    {
        $key = "{$source}:{$sourceId}";
        $target = [];
        foreach ($legs as $l) {
            $k = $l['leg'] . '|' . $l['from']->getKey() . '|' . $l['to']->getKey();
            $target[$k] = ($target[$k] ?? 0) + self::minor($l['amount']);
        }

        $run = function () use ($key, $target, $meta, $dryRun) {
            $posted = $this->posted($key);
            $out = [];
            foreach (array_unique(array_merge(array_keys($target), $posted->keys()->all())) as $k) {
                $diff = ($target[$k] ?? 0) - ($posted[$k] ?? 0);
                if ($diff === 0) {
                    continue;
                }
                [$leg, $fromId, $toId] = explode('|', $k);
                $out[] = ['leg' => $leg, 'from' => (int) $fromId, 'to' => (int) $toId, 'minor' => $diff];
                if (! $dryRun) {
                    $this->transfer($key, $leg, (int) $fromId, (int) $toId, $diff, $meta);
                }
            }

            return $out;
        };

        if ($dryRun) {
            return $run();
        }

        try {
            return DB::transaction($run);
        } catch (\Throwable $e) {
            if (! self::isDuplicate($e)) {
                throw $e;
            }

            // another worker posted the same step first (same uuid): one more pass posts whatever is left
            return DB::transaction($run);
        }
    }

    /** A unique-key refusal, possibly wrapped by bavix (TransactionFailedException). */
    private static function isDuplicate(\Throwable $e): bool
    {
        for (; $e; $e = $e->getPrevious()) {
            if ($e instanceof UniqueConstraintViolationException) {
                return true;
            }
        }

        return false;
    }

    /** Net minor units posted per "leg|from|to" for one source (positive = in the from → to direction). */
    public function posted(string $key): Collection
    {
        return Transaction::where('type', Transaction::TYPE_DEPOSIT)
            ->where('meta->ledger', $key)
            ->get(['amount', 'meta'])
            ->groupBy(fn ($t) => $t->meta['ledger_leg'] . '|' . $t->meta['ledger_from'] . '|' . $t->meta['ledger_to'])
            ->map(fn ($g) => (int) $g->sum(fn ($t) => (int) $t->meta['ledger_dir'] * abs((int) $t->amount)));
    }

    /**
     * One bavix transfer for a signed difference on a leg. Positive: from → to. Negative: the reverse.
     * The meta on both sides names the leg in its planned direction, so posted() can net them.
     */
    private function transfer(string $key, string $leg, int $fromId, int $toId, int $minor, array $meta): void
    {
        $from = Wallet::findOrFail($fromId);
        $to = Wallet::findOrFail($toId);
        [$giver, $taker] = $minor > 0 ? [$from, $to] : [$to, $from];
        // lock both wallets in id order, so two postings on the same accounts queue rather than deadlock
        Wallet::whereKey([min($fromId, $toId), max($fromId, $toId)])->orderBy('id')->lockForUpdate()->get();

        $seq = Transaction::where('type', Transaction::TYPE_DEPOSIT)->where('meta->ledger', $key)->where('meta->ledger_leg', $leg)->count();
        $name = "{$key}|{$leg}|{$fromId}|{$toId}|{$seq}";
        $payload = array_filter($meta, fn ($v) => $v !== null && $v !== '') + [
            'ledger' => $key,
            'ledger_leg' => $leg,
            'ledger_from' => $fromId,
            'ledger_to' => $toId,
            'ledger_dir' => $minor > 0 ? 1 : -1,
            'description' => ucfirst(str_replace('_', ' ', strtok($key, ':'))) . ' #' . substr($key, strpos($key, ':') + 1) . ' · ' . $leg . ($minor < 0 ? ' (reversal)' : ''),
        ];

        $giver->forceTransfer($taker, abs($minor), new Extra(
            deposit: new Option($payload, true, Uuid::uuid5(self::UUID_NS, $name . '|d')->toString()),
            withdraw: new Option($payload, true, Uuid::uuid5(self::UUID_NS, $name . '|w')->toString()),
            uuid: Uuid::uuid5(self::UUID_NS, $name . '|t')->toString(),
            extra: ['ledger' => $key, 'ledger_leg' => $leg],
        ));
    }

    private function metaFor($date, ?string $reference, ?int $loanId): array
    {
        return [
            'date' => $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : null,
            'reference' => $reference,
            'loan_id' => $loanId,
            'source' => 'ledger',
            'by' => auth()->id(),
        ];
    }

    // ---------------------------------------------------------------- reading

    /**
     * Every ledger account's balance (KES), and their total, which must be 0 because every entry balances.
     *
     * @return array{total: float, by_type: array<string, float>, accounts: int}
     */
    public function trialBalance(): array
    {
        $types = collect(config('lending.ledger.accounts'))->map(fn ($a) => $a['type']);
        $rows = DB::table('wallets')->where('slug', 'like', 'gl-%')->whereNull('deleted_at')
            ->groupBy('slug')->get(['slug', DB::raw('sum(balance) as total'), DB::raw('count(*) as n')]);
        $byType = [];
        foreach ($rows as $r) {
            $type = $types[$r->slug] ?? 'unknown';
            $byType[$type] = ($byType[$type] ?? 0) + (float) $r->total / (10 ** self::dp());
        }

        return [
            'total' => round((float) $rows->sum('total') / (10 ** self::dp()), 4),
            'by_type' => array_map(fn ($v) => round($v, 4), $byType),
            'accounts' => (int) $rows->sum('n'),
        ];
    }

    /**
     * Per borrower: cash the ledger says went out to them and came back, vs their customer wallet (borrower_loan).
     *
     * @return array{checked: int, issues: array<int, array>}
     */
    public function checkBorrowerCash(): array
    {
        $cash = $this->houseAccount('gl-cash')->getKey();
        $rows = Transaction::where('wallet_id', $cash)->where('meta->ledger_leg', 'cash')->get(['type', 'amount', 'meta']);
        $byLoan = [];
        foreach ($rows as $t) {
            $loanId = $t->meta['loan_id'] ?? null;
            $source = strtok((string) ($t->meta['ledger'] ?? ''), ':');
            if ($loanId && in_array($source, ['repayment', 'disbursement'], true)) {
                $sign = $t->type === Transaction::TYPE_DEPOSIT ? 1 : -1;
                $byLoan[$loanId] = ($byLoan[$loanId] ?? 0) + $sign * abs((int) $t->amount);
            }
        }
        $byUser = [];
        foreach (Loan::withTrashed()->whereIn('id', array_keys($byLoan))->pluck('user_id', 'id') as $loanId => $userId) {
            $byUser[$userId] = ($byUser[$userId] ?? 0) + $byLoan[$loanId];
        }

        $issues = [];
        foreach ($byUser as $userId => $minor) {
            $ledger = round($minor / (10 ** self::dp()), 2);
            $wallet = Wallet::where('holder_type', (new \App\Models\User)->getMorphClass())->where('holder_id', $userId)
                ->where('slug', CustomerWallets::BORROWER_WALLET)->first();
            $balance = $wallet ? round((float) $wallet->balanceFloat, 2) : null;
            if ($balance === null || abs($balance - $ledger) >= 0.01) {
                $issues[] = ['user' => $userId, 'ledger' => $ledger, 'wallet' => $balance];
            }
        }

        return ['checked' => count($byUser), 'issues' => $issues];
    }
}
