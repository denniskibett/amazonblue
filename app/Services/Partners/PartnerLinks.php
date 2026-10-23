<?php

namespace App\Services\Partners;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Loans\RepaymentSplit;
use Illuminate\Support\Facades\DB;

/**
 * Who brought a loan in and whose money it is (owner, 23 Oct 2026).
 *
 *  - Brokered by: the partner who brought the client. Sets loans.broker_status = 1, partner_id and the broker rates
 *    (partners.broker_rate % of the interest, brokers.penalty_broker or 50% of the penalty).
 *  - A partner's own loan: the partner is their own broker, so they pay the full rate and their broker share of every
 *    repayment comes back to their broker wallet (owner's choice, 23 Oct 2026).
 *  - Funded by: the line the cash came from (house, or a partner's line). Moving a past loan to another line moves the
 *    draw between the lines.
 * After any change every repayment of the loan is re-split, so the shares move by exactly the difference (new journal
 * rows; nothing is edited). preview() runs the same change and rolls it back.
 */
class PartnerLinks
{
    /** The partner whose own account borrowed this loan, if any (never the house). */
    public function ownPartner(Loan $loan): ?Partner
    {
        return $loan->user_id ? Partner::where('user_id', $loan->user_id)->where('is_house_account', false)->first() : null;
    }

    public function brokerFields(Partner $p): array
    {
        $penalty = DB::table('brokers')->where('user_id', $p->user_id ?: 0)->value('penalty_broker');

        return ['broker_status' => 1, 'partner_id' => $p->id, 'broker_rate' => (float) ($p->broker_rate ?: 40),
            'broker_penalty_rate' => (float) ($penalty ?? config('lending.split.default_broker_penalty_rate', 50))];
    }

    /**
     * Set the broker (null = leave it, 0 = not brokered) and/or move the loan's cash to another line (null = leave it).
     * Returns what changed. Runs in one transaction; the shares are re-split at the end.
     */
    public function apply(Loan $loan, ?int $brokerId, ?int $lineId, ?User $by = null, string $why = 'partner_links'): array
    {
        return DB::transaction(function () use ($loan, $brokerId, $lineId, $by, $why) {
            $loan = Loan::withTrashed()->lockForUpdate()->findOrFail($loan->id);
            $before = ['broker_status' => (int) $loan->broker_status, 'partner_id' => $loan->partner_id, 'broker_rate' => $loan->broker_rate,
                'lines' => Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->pluck('investment_id', 'id')->all()];
            $changes = [];

            if ($brokerId !== null) {
                $fields = $brokerId === 0 ? ['broker_status' => 0, 'partner_id' => null] : $this->brokerFields(Partner::findOrFail($brokerId));
                if ((int) $loan->broker_status !== (int) $fields['broker_status'] || (int) $loan->partner_id !== (int) ($fields['partner_id'] ?? 0)) {
                    $loan->forceFill($fields)->saveQuietly();
                    $changes[] = $brokerId === 0 ? 'not brokered' : 'brokered by ' . Partner::whereKey($brokerId)->value('name');
                }
            }

            $oldLines = [];
            if ($lineId !== null) {
                $line = Investment::findOrFail($lineId);
                foreach (Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->orderBy('id')->get() as $d) {
                    if ((int) $d->investment_id === $line->id) {
                        continue;
                    }
                    if ($d->investment_id && ($old = Investment::find($d->investment_id))) {
                        $this->undraw($old, (float) $d->amount);
                        $oldLines[$old->id] = $old;
                    }
                    $partnerId = $line->isHouseLine() ? null : InvestmentFunding::where('investment_id', $line->id)
                        ->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->orderBy('id')->value('partner_id');
                    $line->recordFundingDisbursement((float) $d->amount, $partnerId);
                    $d->forceFill(['investment_id' => $line->id, 'funding_source' => $line->isHouseLine() ? 'internal' : 'partner'])->saveQuietly();
                    $changes[] = 'disbursement #' . $d->id . ' (KES ' . number_format((float) $d->amount, 2) . ') now from ' . $line->name;
                }
            }

            $split = app(RepaymentSplit::class);
            foreach ($loan->cycles()->pluck('id') as $cycleId) {
                $split->syncCycle((int) $cycleId);
            }
            foreach ($oldLines as $old) {
                $split->syncLineReturns($old->fresh());
            }
            if ($changes) {
                AuditTrail::record('loans', $loan->id, 'update', $before, ['event' => $why, 'changes' => $changes, 'by' => $by?->id]);
            }

            return $changes;
        });
    }

    /**
     * What apply() would do, without saving: the journal for this loan by partner and type, before and after,
     * plus the change list. Runs apply() in a transaction that is always rolled back.
     */
    public function preview(Loan $loan, ?int $brokerId, ?int $lineId, ?\Closure $prepare = null): array
    {
        $before = $this->journal($loan->id);
        $out = ['before' => $before, 'after' => $before, 'changes' => [], 'error' => null];
        try {
            DB::transaction(function () use ($loan, $brokerId, $lineId, $prepare, &$out) {
                if ($prepare) {
                    $lineId = $prepare() ?? $lineId;
                }
                $out['changes'] = $this->apply($loan, $brokerId, $lineId);
                $out['after'] = $this->journal($loan->id);
                throw new PreviewRollback();
            });
        } catch (PreviewRollback) {
            // rolled back on purpose
        } catch (\DomainException $e) {
            $out['error'] = $e->getMessage();
        }

        return $out;
    }

    /** [partner name => [type => total]] of the split rows on one loan. */
    public function journal(int $loanId): array
    {
        $rows = PartnerTransaction::where('loan_id', $loanId)->where('reference', 'like', RepaymentSplit::PREFIX . '%')
            ->selectRaw('partner_id, type, sum(amount) as total')->groupBy('partner_id', 'type')->get();
        $names = Partner::whereIn('id', $rows->pluck('partner_id'))->pluck('name', 'id');
        $out = [];
        foreach ($rows as $r) {
            if (abs((float) $r->total) >= 0.005) {
                $out[$names[$r->partner_id] ?? ('Partner #' . $r->partner_id)][$r->type] = round((float) $r->total, 2);
            }
        }
        ksort($out);

        return $out;
    }

    /** Take a draw back off a line (the loan's cash moved to another line). Newest rows first; a partner row reopens. */
    private function undraw(Investment $line, float $amount): void
    {
        $left = round($amount, 2);
        foreach ($line->fundings()->where('status', '!=', 'cancelled')->orderByDesc('id')->lockForUpdate()->get() as $f) {
            if ($left <= 0) {
                break;
            }
            $take = min($left, (float) $f->amount_disbursed);
            if ($take <= 0) {
                continue;
            }
            $f->amount_disbursed = round((float) $f->amount_disbursed - $take, 2);
            if ($f->status === 'settled' && $line->status === 'active' && (float) $f->amount_committed > (float) $f->amount_disbursed) {
                $f->status = 'active';
            }
            $f->save();
            $left = round($left - $take, 2);
        }
    }
}
