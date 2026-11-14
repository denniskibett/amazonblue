<?php

namespace App\Services\Loans;

use App\Models\FacilitySplit;
use App\Models\Loan;
use App\Models\Repayment;
use App\Services\CustomerWallets;

/**
 * A facility's own split (owner, 14 Nov 2026; first for loan 458, the ISIRO facility).
 *
 * From `counted_from`, the cash collected on the loan is shared in two bands:
 *  1. principal plus interest, up to `pi_target` less what the parties had already received. Each party gets this
 *     band in proportion to what it is still owed of its own principal plus interest (pi_entitled - received_before),
 *     so a party that got less than its share earlier catches up, and both finish the band at their entitlement;
 *  2. penalty: everything after that, up to `total_cap`, by each party's penalty_pct (50/50 for ISIRO and us).
 * Anything above total_cap is overpaid and shared with nobody.
 *
 * It is a plain partner split: no broker fee is taken (owner, 14 Nov 2026). For a partner, the principal part of
 * its band-1 money goes back as principal returned, the rest as interest; the house is recorded as in the normal
 * split (interest and penalty only). Payments before counted_from keep the normal split.
 */
class FacilitySplitter
{
    /** @var array<int, FacilitySplit|null> */
    private array $cache = [];

    public function for(Loan|int $loan): ?FacilitySplit
    {
        $id = $loan instanceof Loan ? $loan->id : $loan;

        return $this->cache[$id] ??= FacilitySplit::with('parties.partner')->where('loan_id', $id)->first();
    }

    /** The split rule that governs a payment on $loan on $date, if any. */
    public function governing(Loan $loan, ?string $date): ?FacilitySplit
    {
        $split = $this->for($loan);

        return $split && $date && $date >= $split->counted_from->toDateString() ? $split : null;
    }

    /** Cash repayments counted by the split, in order: [[repayment_id, date, amount], ...]. */
    public function payments(FacilitySplit $split): array
    {
        return Repayment::where('loan_id', $split->loan_id)
            ->whereDate('repayment_date', '>=', $split->counted_from->toDateString())
            ->orderBy('repayment_date')->orderBy('id')->get()
            ->filter(fn ($r) => ! CustomerWallets::isGone($r) && CustomerWallets::kindOf($r) === 'cash')
            ->map(fn ($r) => [$r->id, optional($r->repayment_date)->toDateString(), round((float) $r->amount, 2)])
            ->values()->all();
    }

    /** Cash counted before repayment $r (earlier day, or same day and lower id). */
    public function collectedBefore(FacilitySplit $split, Repayment $r): float
    {
        $sum = 0.0;
        foreach ($this->payments($split) as [$id, , $amount]) {
            if ($id === $r->id) {
                break;
            }
            $sum += $amount;
        }

        return round($sum, 2);
    }

    /** The bands: [pi band size, penalty band size, weights in band 1 by partner_id]. */
    private function bands(FacilitySplit $split): array
    {
        $parties = $split->parties;
        $before = (float) $parties->sum('received_before');
        $piBand = max(0.0, (float) $split->pi_target - $before);
        $penBand = max(0.0, (float) $split->total_cap - max((float) $split->pi_target, $before));
        $left = $parties->mapWithKeys(fn ($p) => [$p->partner_id => max(0.0, (float) $p->pi_entitled - (float) $p->received_before)]);
        $total = $left->sum();
        $weights = $total > 0
            ? $left->map(fn ($v) => $v / $total)
            : $parties->mapWithKeys(fn ($p) => [$p->partner_id => (float) $p->principal / max(1, (float) $parties->sum('principal'))]);

        return [$piBand, $penBand, $weights->all()];
    }

    /**
     * Each party's cumulative share once $x has been collected since counted_from: [partner_id => [pi, penalty]].
     * The house takes the rounding, so the parts always add up to what was collected.
     */
    public function cumulative(FacilitySplit $split, float $x): array
    {
        [$piBand, $penBand, $weights] = $this->bands($split);
        $xp = round(min($x, $piBand), 2);
        $xpen = round(min(max(0.0, $x - $piBand), $penBand), 2);
        $house = $this->houseId($split);
        $out = [];
        foreach ($split->parties as $p) {
            if ($p->partner_id === $house) {
                continue;
            }
            $out[$p->partner_id] = [round($xp * ($weights[$p->partner_id] ?? 0), 2), round($xpen * (float) $p->penalty_pct / 100, 2)];
        }
        if ($house) {
            $out[$house] = [round($xp - array_sum(array_column($out, 0)), 2), round($xpen - array_sum(array_column($out, 1)), 2)];
        }

        return $out;
    }

    /**
     * The journal rows a payment of $amount produces when $before had already been collected:
     * [ [partner_id, type, wallet, amount, why], ... ], in RepaymentSplit's format.
     */
    public function shares(FacilitySplit $split, float $before, float $amount): array
    {
        $a = $this->cumulative($split, $before);
        $b = $this->cumulative($split, $before + $amount);
        $house = $this->houseId($split);
        $out = [];
        foreach ($split->parties as $p) {
            $id = $p->partner_id;
            $pi = round($b[$id][0] - $a[$id][0], 2);
            $pen = round($b[$id][1] - $a[$id][1], 2);
            $principalPart = (float) $p->pi_entitled > 0 ? round($pi * (float) $p->principal / (float) $p->pi_entitled, 2) : 0.0;
            if ($id !== $house && $principalPart != 0.0) {
                $out[] = [$id, 'principal_returned', 'principal', $principalPart, 'Facility split: principal back'];
            }
            $out[] = [$id, 'profit_distribution', 'interest', round($pi - $principalPart, 2), $id === $house ? 'AC: facility interest' : 'Facility split: interest'];
            $out[] = [$id, 'penalty_share', 'interest', $pen, $id === $house ? 'AC: facility penalty' : 'Facility split: ' . rtrim(rtrim((string) $p->penalty_pct, '0'), '.') . '% of penalty'];
        }

        return array_values(array_filter($out, fn ($s) => abs($s[3]) >= 0.005));
    }

    /** Where each party stands: entitlement, received before, received since, still to come. */
    public function status(FacilitySplit $split): array
    {
        $collected = round(array_sum(array_column($this->payments($split), 2)), 2);
        $now = $this->cumulative($split, $collected);
        $full = $this->cumulative($split, (float) $split->total_cap);
        $rows = [];
        foreach ($split->parties as $p) {
            $id = $p->partner_id;
            $entitled = round((float) $p->received_before + $full[$id][0] + $full[$id][1], 2);
            $since = round($now[$id][0] + $now[$id][1], 2);
            $rows[] = [
                'partner_id' => $id, 'name' => $p->partner?->name ?? '#' . $id, 'house' => $id === $this->houseId($split),
                'principal' => (float) $p->principal, 'pi_entitled' => (float) $p->pi_entitled, 'penalty_pct' => (float) $p->penalty_pct,
                'penalty_entitled' => $full[$id][1], 'entitled' => $entitled,
                'received_before' => (float) $p->received_before, 'received_since' => $since,
                'to_come' => round($entitled - (float) $p->received_before - $since, 2),
            ];
        }
        $before = (float) $split->parties->sum('received_before');

        return [
            'collected_since' => $collected,
            'collected_total' => round($before + $collected, 2),
            'to_come' => round(max(0, (float) $split->total_cap - $before - $collected), 2),
            'in_penalty' => $before + $collected >= (float) $split->pi_target,
            'parties' => $rows,
        ];
    }

    private function houseId(FacilitySplit $split): ?int
    {
        return $split->parties->first(fn ($p) => $p->partner?->is_house_account)?->partner_id;
    }
}
