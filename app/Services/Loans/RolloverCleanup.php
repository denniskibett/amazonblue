<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\LoanCycle;
use App\Models\Repayment;
use App\Services\AuditTrail;
use App\Services\CustomerWallets;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Feature 2a (owner, 21 Oct 2026): a rollover lives in loan_cycles only.
 *
 * 1. Chains the first merge (loans:merge-chains, 3-day window) did not link. A continuation C is linked to the same
 *    borrower's loan P when C has a ROLL OVER disbursement, P has a ROLL OVER repayment, and C's first cycle starts
 *    within WINDOW days of P's latest cycle's due date (on time or a few days late), or P's ROLL OVER repayment is
 *    exactly C's first-cycle principal within AMOUNT_WINDOW days. P must still owe on that day, and P's latest
 *    cycle must not start after C's first one, so the merged cycles stay in date order. Amounts need not match: the ledger books any
 *    carry difference as a rollover adjustment, and the review shows it. Ties are reported, never guessed.
 * 2. ROLL OVER rows the cycles already hold: a ROLL OVER repayment on a cycle that was carried into a later one,
 *    and a ROLL OVER disbursement where a later cycle of the same loan starts within WINDOW days. They were never
 *    money (no wallet, ledger or split posting), so removing them (soft delete) changes no balance.
 */
class RolloverCleanup
{
    public const WINDOW = 3;

    /** How far apart the ROLL OVER repayment and the continuation may be when the amounts match to the cent. */
    public const AMOUNT_WINDOW = 15;

    /** @return array{links: Collection, chains: Collection, unmatched: Collection} */
    public function chains(): array
    {
        $roRepayments = Repayment::with('loan:id,user_id,deleted_at')->get()
            ->filter(fn ($r) => CustomerWallets::kindOf($r) === 'rollover' && $r->loan && ! $r->loan->trashed());
        $opens = Disbursement::with('loan:id,user_id,deleted_at')->orderBy('disburse_date')->orderBy('id')->get()
            ->filter(fn ($d) => CustomerWallets::kindOf($d) === 'rollover' && $d->loan && ! $d->loan->trashed())
            ->groupBy('loan_id')->map->first();   // a continuation opens once (checked against its first cycle below)

        $latest = fn (int $loanId) => LoanCycle::where('loan_id', $loanId)->orderByDesc('cycle_number')->first();
        $first = fn (int $loanId) => LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->first();

        $proposals = collect();
        $unmatched = collect();
        foreach ($opens as $loanId => $open) {
            $c1 = $first($loanId);
            // the loan opened by rollover: its first disbursement is a ROLL OVER, or one is dated at its first cycle
            // (later ROLL OVER rows are its own cycles)
            $earliest = Disbursement::where('loan_id', $loanId)->orderBy('disburse_date')->orderBy('id')->first();
            if (! $c1 || (CustomerWallets::kindOf($earliest) !== 'rollover'
                && abs($c1->start_date->diffInDays(Carbon::parse($open->disburse_date), false)) > self::WINDOW)) {
                continue;
            }
            $start = $c1->start_date->toDateString();
            // a loan that also got cash when it opened is a top-up, not a pure continuation: merging it would book
            // that cash twice (as cash and as a carry difference); the consolidation transfer handles it instead
            $cashAtStart = Disbursement::where('loan_id', $loanId)->get()->contains(fn ($d) => CustomerWallets::kindOf($d) === 'cash'
                && abs($c1->start_date->diffInDays(Carbon::parse($d->disburse_date), false)) <= self::WINDOW);
            if ($cashAtStart) {
                $unmatched->push(['loan' => (int) $loanId, 'user' => (int) $open->loan->user_id, 'date' => $start,
                    'why' => 'opened with a ROLL OVER plus new cash (a top-up): handled as a consolidation transfer, not merged']);
                continue;
            }
            $candidates = $roRepayments->filter(fn ($r) => (int) $r->loan->user_id === (int) $open->loan->user_id && (int) $r->loan_id !== (int) $loanId)
                ->groupBy('loan_id')
                ->map(function ($rows, $pid) use ($latest, $start, $open, $c1) {
                    $p = $latest((int) $pid);
                    if (! $p || $p->start_date->toDateString() > $start) {
                        return null;
                    }
                    $gap = (int) $p->due_date->diffInDays(Carbon::parse($start), false);   // days late (negative: early)
                    // a ROLL OVER repayment for exactly what the continuation lent is the strongest sign; else the nearest by date
                    $exact = $rows->first(fn ($r) => abs((float) $r->amount - $c1->lentPrincipal()) < 0.01
                        && abs(Carbon::parse($r->repayment_date)->diffInDays(Carbon::parse($start), false)) <= self::AMOUNT_WINDOW);
                    $close = $exact ?? $rows->sortBy(fn ($r) => abs(Carbon::parse($r->repayment_date)->diffInDays(Carbon::parse($start), false)))->first();
                    $b = CycleBalance::compute(CycleBalance::termsFor($p),
                        array_values(array_filter(CycleBalance::paymentsFor($p), fn ($x) => ($x['kind'] ?? 'cash') !== 'rollover')), $start);

                    return ['from' => (int) $pid, 'to' => (int) $open->loan_id, 'user' => (int) $open->loan->user_id,
                        'from_cycle_id' => $p->id, 'from_cycle' => $p->cycle_number, 'from_start' => $p->start_date->toDateString(),
                        'from_due' => $p->due_date->toDateString(), 'to_start' => $start, 'days_late' => $gap,
                        'repayment_id' => $close->id, 'repayment_date' => Carbon::parse($close->repayment_date)->toDateString(), 'amount_closed' => (float) $close->amount,
                        'disbursement_id' => $open->id, 'disbursement_date' => Carbon::parse($open->disburse_date)->toDateString(), 'amount_opened' => (float) $open->amount,
                        'owed_at_rollover' => $b['final_outstanding'], 'penalty_at_rollover' => $b['penalty'],
                        'continuation_principal' => $c1->lentPrincipal(),
                        'carry_difference' => round($c1->lentPrincipal() - $b['final_outstanding'], 2),
                        'matched_on' => $exact ? 'amount' : 'dates', 'overlap' => $p->start_date->toDateString() === $start];
                })
                // something must have been owed that day: a loan already paid off was not rolled over
                ->filter(fn ($x) => $x && $x['owed_at_rollover'] > 0.004
                    && ($x['matched_on'] === 'amount' || abs($x['days_late']) <= self::WINDOW))
                ->sortBy([fn ($a, $b) => ($a['matched_on'] === 'amount' ? 0 : 1) <=> ($b['matched_on'] === 'amount' ? 0 : 1),
                    fn ($a, $b) => abs($a['days_late']) <=> abs($b['days_late']), fn ($a, $b) => abs($a['carry_difference']) <=> abs($b['carry_difference'])])
                ->values();

            if ($candidates->isEmpty()) {
                $unmatched->push(['loan' => (int) $loanId, 'user' => (int) $open->loan->user_id, 'date' => $start,
                    'why' => 'opened with a ROLL OVER, but no loan of this borrower still owed money on a latest cycle due within ' . self::WINDOW . ' days of ' . $start]);
                continue;
            }
            if ($candidates->count() > 1 && abs($candidates[0]['days_late']) === abs($candidates[1]['days_late'])
                && abs($candidates[0]['carry_difference'] - $candidates[1]['carry_difference']) < 0.01) {
                $unmatched->push(['loan' => (int) $loanId, 'user' => (int) $open->loan->user_id, 'date' => $start,
                    'why' => 'loans #' . $candidates[0]['from'] . ' and #' . $candidates[1]['from'] . ' fit equally']);
                continue;
            }
            $proposals->push($candidates[0]);
        }

        // one continuation per predecessor: the closest wins, the rest are reported
        $links = collect();
        foreach ($proposals->groupBy('from') as $from => $group) {
            $sorted = $group->sortBy(fn ($l) => [abs($l['days_late']), abs($l['carry_difference'])])->values();
            $links->push($sorted[0]);
            foreach ($sorted->slice(1) as $l) {
                $unmatched->push(['loan' => $l['to'], 'user' => $l['user'], 'date' => $l['to_start'],
                    'why' => 'loan #' . $from . ' is already continued by #' . $sorted[0]['to']]);
            }
        }

        // walk back to roots
        $byTo = $links->keyBy('to');
        $chains = [];
        foreach ($links as $l) {
            $root = $l['to'];
            $seen = [];
            while ($byTo->has($root) && ! isset($seen[$root])) {
                $seen[$root] = true;
                $root = $byTo[$root]['from'];
            }
            $chains[$root]['root'] = $root;
            $chains[$root]['user'] = $l['user'];
            $chains[$root]['links'][] = $l;
        }
        foreach ($chains as &$ch) {
            // order by following the links from the root, so each merge appends after the cycle it continues
            $ordered = [];
            $at = $ch['root'];
            $links2 = collect($ch['links'])->keyBy('from');
            while ($links2->has($at)) {
                $ordered[] = $links2[$at];
                $at = $links2[$at]['to'];
            }
            $ch['links'] = $ordered;
            $ch['loans'] = array_merge([$ch['root']], array_column($ordered, 'to'));
            $ch['borrower'] = DB::table('users')->where('id', $ch['user'])->value('name');
            $ch['carry_difference'] = round(array_sum(array_column($ordered, 'carry_difference')), 2);
        }
        unset($ch);
        ksort($chains);

        return ['links' => $links, 'chains' => collect($chains)->values(), 'unmatched' => $unmatched->sortBy('loan')->values()];
    }

    /** ROLL OVER rows whose carry the cycles already hold (on visible loans). */
    public function strayRows(?array $loanIds = null): Collection
    {
        $out = collect();
        $loans = Loan::query()->when($loanIds, fn ($q) => $q->whereIn('id', $loanIds))->pluck('id');
        foreach ($loans as $loanId) {
            $cycles = LoanCycle::where('loan_id', $loanId)->orderBy('cycle_number')->get();
            if ($cycles->count() < 2) {
                continue;
            }
            $latestId = (int) $cycles->last()->id;
            foreach (Repayment::where('loan_id', $loanId)->get() as $r) {
                if (CustomerWallets::kindOf($r) !== 'rollover') {
                    continue;
                }
                $cycle = $r->loan_cycle_id ? $cycles->first(fn ($c) => (int) $c->id === (int) $r->loan_cycle_id) : null;
                $day = Carbon::parse($r->repayment_date)->toDateString();
                $carried = $cycle ? (int) $cycle->id !== $latestId
                    : $cycles->contains(fn ($c) => $c->cycle_number > 1 && abs($c->start_date->diffInDays(Carbon::parse($day), false)) <= self::WINDOW);
                if ($carried) {
                    $out->push(['table' => 'repayments', 'id' => $r->id, 'loan_id' => $loanId, 'date' => $day, 'amount' => (float) $r->amount,
                        'transaction' => $r->transaction, 'why' => $cycle ? "on cycle {$cycle->cycle_number}, carried into the next cycle" : 'a later cycle starts within ' . self::WINDOW . ' days']);
                }
            }
            foreach (Disbursement::where('loan_id', $loanId)->get() as $d) {
                if (CustomerWallets::kindOf($d) !== 'rollover') {
                    continue;
                }
                $day = Carbon::parse($d->disburse_date)->toDateString();
                $opens = $cycles->first(fn ($c) => $c->cycle_number > 1 && abs($c->start_date->diffInDays(Carbon::parse($day), false)) <= self::WINDOW);
                if ($opens) {
                    $out->push(['table' => 'disbursements', 'id' => $d->id, 'loan_id' => $loanId, 'date' => $day, 'amount' => (float) $d->amount,
                        'transaction' => $d->transaction, 'why' => "cycle {$opens->cycle_number} starts {$opens->start_date->toDateString()}"]);
                }
            }
        }

        return $out;
    }

    /** Merge one chain (by its root) as proposed now, then remove the ROLL OVER rows its cycles hold. */
    public function applyChain(int $root, ?string $note = null, ?array $chain = null): array
    {
        $chain ??= $this->chains()['chains']->firstWhere('root', $root)
            ?? throw new \RuntimeException("Loan #{$root}: no chain to merge any more.");

        DB::transaction(function () use ($chain) {
            app(LegacyChains::class)->merge($chain);
        });
        $removed = $this->removeStray([$root], $note ?? 'chain merge');
        $loan = Loan::findOrFail($root);
        LoanStatus::apply($loan);
        AuditTrail::record('loans', $root, 'update', null, ['rollover_cleanup' => 'chain', 'merged' => array_slice($chain['loans'], 1),
            'rows_removed' => $removed, 'note' => $note]);

        return $chain + ['rows_removed' => $removed];
    }

    /** Loans never sent money whose cycles still carry ledger charges or show a balance. */
    public function neverSent(): Collection
    {
        return Loan::whereIn('status', [Loan::STATUS_PENDING, Loan::STATUS_APPROVED, Loan::STATUS_REJECTED])->with('user:id,name')->get()
            ->filter(fn ($l) => \App\Services\Ledger::neverSent($l) && LoanCycle::where('loan_id', $l->id)->get()
                ->contains(fn ($c) => ! \App\Services\Ledger::enabled()
                    || app(\App\Services\Ledger::class)->posted('loan_cycle:' . $c->id)->filter()->isNotEmpty()))
            ->map(fn ($l) => ['loan_id' => $l->id, 'borrower' => $l->user?->name, 'status' => $l->status])->values();
    }

    /** Re-books the cycles of never-sent loans: their charges come off the ledger. */
    public function clearNeverSent(): int
    {
        $n = 0;
        if (! \App\Services\Ledger::enabled()) {
            return 0;
        }
        foreach ($this->neverSent() as $row) {
            foreach (LoanCycle::where('loan_id', $row['loan_id'])->get() as $cycle) {
                $n += count(app(\App\Services\Ledger::class)->syncLoanCycle($cycle));
            }
        }

        return $n;
    }

    /** Soft-delete the stray ROLL OVER rows of these loans (all loans when null). Returns how many. */
    public function removeStray(?array $loanIds = null, ?string $note = null): int
    {
        $n = 0;
        foreach ($this->strayRows($loanIds) as $row) {
            DB::transaction(function () use ($row, $note, &$n) {
                $model = $row['table'] === 'repayments' ? Repayment::find($row['id']) : Disbursement::find($row['id']);
                if ($model) {
                    $model->delete();
                    AuditTrail::record($row['table'], $row['id'], 'delete', ['amount' => $row['amount'], 'transaction' => $row['transaction']],
                        ['rollover_cleanup' => $row['why'], 'note' => $note]);
                    $n++;
                }
            });
        }

        return $n;
    }

    /** How far apart a ROLL OVER repayment and the loan that took it may be for a consolidation transfer. */
    public const TRANSFER_WINDOW = 15;

    /** The visible loan a (possibly merged) loan now lives in. */
    private function root(int $loanId): ?Loan
    {
        $seen = [];
        $loan = Loan::withTrashed()->find($loanId);
        while ($loan && $loan->merged_into_loan_id && ! isset($seen[$loan->id])) {
            $seen[$loan->id] = true;
            $loan = Loan::withTrashed()->find($loan->merged_into_loan_id);
        }

        return $loan && ! $loan->trashed() ? $loan : null;
    }

    /**
     * Rollovers into a loan that already existed (consolidations and top-ups). The old loan's ROLL OVER repayment
     * closed it, and the other loan's cycle took the balance, but the ledger posts neither ROLL OVER row (decision 6),
     * so the receivable stayed on the old loan. The fix takes it off the old loan in the ledger: to the other loan's
     * receivable when it went into that loan's first cycle, or against gl-rollover-adjustments when it went into a later
     * cycle (that cycle's carry leg already booked it on the other loan). Both loans' rows stay as they are. Only exact amounts, same borrower, within TRANSFER_WINDOW days:
     *   exact:  a ROLL OVER disbursement for the same amount on the other loan
     *   no row: the other loan has no disbursement at all and its first cycle lent exactly that amount
     *   top-up: the other loan's first cycle lent exactly that amount plus the cash it was sent
     */
    public function transfers(): Collection
    {
        $usedDisb = [];
        $out = collect();
        $repayments = Repayment::orderBy('repayment_date')->orderBy('id')->get()->filter(fn ($r) => CustomerWallets::kindOf($r) === 'rollover');
        foreach ($repayments as $r) {
            $from = $this->root((int) $r->loan_id);
            if (! $from) {
                continue;
            }
            $day = Carbon::parse($r->repayment_date);
            $amount = round((float) $r->amount, 2);
            $userLoans = Loan::where('user_id', $from->user_id)->where('id', '!=', $from->id)->pluck('id');
            $match = null;

            // soft-deleted ROLL OVER disbursements count too: 2a removed the ones a cycle start already shows, but they
            // are still the record of which loan took the balance
            foreach (Disbursement::withTrashed()->whereIn('loan_id', Loan::withTrashed()->where('user_id', $from->user_id)->pluck('id'))->get() as $d) {
                $to = $this->root((int) $d->loan_id);
                if ($to && $to->id !== $from->id && ! isset($usedDisb[$d->id]) && CustomerWallets::kindOf($d) === 'rollover'
                    && abs((float) $d->amount - $amount) < 0.01 && abs(Carbon::parse($d->disburse_date)->diffInDays($day, false)) <= self::TRANSFER_WINDOW) {
                    $match = ['to' => $to, 'how' => 'exact', 'disbursement_id' => $d->id, 'date' => Carbon::parse($d->disburse_date)->toDateString()];
                    break;
                }
            }
            if (! $match) {
                foreach ($userLoans as $lid) {
                    $c1 = LoanCycle::where('loan_id', $lid)->orderBy('cycle_number')->first();
                    if (! $c1 || abs($c1->start_date->diffInDays($day, false)) > self::TRANSFER_WINDOW) {
                        continue;
                    }
                    $disb = Disbursement::where('loan_id', $lid)->get();
                    $cash = $disb->filter(fn ($d) => CustomerWallets::kindOf($d) === 'cash')->sum('amount');
                    if ($disb->isEmpty() && abs($c1->lentPrincipal() - $amount) < 0.01) {
                        $match = ['to' => Loan::find($lid), 'how' => 'no disbursement row', 'disbursement_id' => null, 'date' => $c1->start_date->toDateString()];
                    } elseif ($cash > 0 && abs($c1->lentPrincipal() - $amount - $cash) < 0.01) {
                        $match = ['to' => Loan::find($lid), 'how' => 'top-up (+' . number_format($cash, 2) . ' cash)', 'disbursement_id' => null, 'date' => $c1->start_date->toDateString()];
                    }
                    if ($match) {
                        break;
                    }
                }
            }
            if (! $match) {
                continue;
            }
            if ($match['disbursement_id']) {
                $usedDisb[$match['disbursement_id']] = true;
            }
            // which cycle of the other loan took it: its first (principal the ledger never saw: move the receivable
            // across) or a later one (the ledger already booked it there as a rollover adjustment: only the old
            // loan's receivable comes off, against that adjustment account)
            $into = LoanCycle::where('loan_id', $match['to']->id)->get()
                ->sortBy(fn ($c) => abs($c->start_date->diffInDays(Carbon::parse($match['date']), false)))->first();
            $target = $into && (int) $into->cycle_number > 1 ? 'adjustment' : 'receivable';
            $out->push(['repayment_id' => $r->id, 'from' => $from->id, 'to' => $match['to']->id, 'user' => $from->user_id,
                'borrower' => DB::table('users')->where('id', $from->user_id)->value('name'), 'amount' => $amount,
                'closed' => $day->toDateString(), 'opened' => $match['date'], 'how' => $match['how'],
                'into_cycle' => $into?->cycle_number, 'target' => $target,
                'posted' => $this->transferPosted($r->id)]);
        }

        return $out;
    }

    private function transferPosted(int $repaymentId): bool
    {
        return \App\Services\Ledger::enabled() && app(\App\Services\Ledger::class)->posted('rollover_transfer:' . $repaymentId)->filter()->isNotEmpty();
    }

    /** Posts (or re-posts to the same plan) each consolidation transfer. Returns how many were posted. */
    public function postTransfers(?Collection $rows = null): int
    {
        if (! \App\Services\Ledger::enabled()) {
            return 0;
        }
        $ledger = app(\App\Services\Ledger::class);
        $n = 0;
        foreach ($rows ?? $this->transfers() as $t) {
            $from = Loan::findOrFail($t['from']);
            $to = Loan::findOrFail($t['to']);
            $into = $t['target'] === 'adjustment' ? $ledger->houseAccount('gl-rollover-adjustments') : $ledger->account($to, 'gl-receivable');
            $steps = $ledger->sync('rollover_transfer', (int) $t['repayment_id'], [[
                'leg' => 'rollover_transfer', 'from' => $ledger->account($from, 'gl-receivable'), 'to' => $into, 'amount' => $t['amount'],
            ]], ['loan_id' => $to->id, 'date' => $t['opened'], 'source' => 'ledger', 'reference' => "ROLL OVER #{$from->id} -> #{$to->id}"]);
            if ($steps) {
                $n++;
                AuditTrail::record('loans', $to->id, 'update', null, ['rollover_transfer' => $t]);
            }
        }

        return $n;
    }
}
