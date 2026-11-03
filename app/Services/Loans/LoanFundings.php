<?php

namespace App\Services\Loans;

use App\Models\Disbursement;
use App\Models\Investment;
use App\Models\InvestmentFunding;
use App\Models\Loan;
use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Part funding (owner, 1 Nov 2026): who put in what of a loan's cash (table loan_fundings).
 *
 *  - several rows: the loan is part funded; each repayment's interest and principal split pro rata to the amounts,
 *    every slice then follows RepaymentSplit's single-funder rules; any rounding cent goes to the house row;
 *  - one row: a single funder, e.g. the frozen funding of a past loan (source BACKFILL), read exactly as before;
 *  - no rows: RepaymentSplit reads the loan's first cash disbursement, as before.
 */
class LoanFundings
{
    public const BACKFILL = 'backfill 2026-11-02';

    private static ?bool $ready = null;

    public static function ready(): bool
    {
        return self::$ready ??= Schema::hasTable('loan_fundings');
    }

    /** For tests that build the schema after the first check. */
    public static function forget(): void
    {
        self::$ready = null;
    }

    /**
     * The loan's funding rows, oldest first (each with is_house). Empty when none are recorded.
     * $asOf (a repayment's day, owner 3 Nov 2026): a slice the house settled before that day is the house's from then
     * on, so the payments after it share nothing with that partner.
     */
    public function rows(int $loanId, ?string $asOf = null): Collection
    {
        if (! self::ready()) {
            return collect();
        }
        $house = Partner::where('is_house_account', true)->pluck('id')->all();
        $settled = Schema::hasColumn('loan_fundings', 'settled_on');

        // a partner's slice counts from the day its disbursement went out: a repayment before that was the house's money
        return DB::table('loan_fundings as f')->leftJoin('disbursements as d', 'd.id', '=', 'f.disbursement_id')->where('f.loan_id', $loanId)
            ->orderBy('f.id')->get(['f.*', 'd.disburse_date as lent_on'])
            ->each(function ($r) use ($house, $asOf, $settled) {
                $r->is_house = ! $r->partner_id || in_array((int) $r->partner_id, $house, true)
                    || ($settled && $asOf && $r->settled_on && $r->settled_on < $asOf)
                    || ($asOf && $r->source === 'slices' && $r->lent_on && substr((string) $r->lent_on, 0, 10) > $asOf);
            });
    }

    /** Funded by more than one source. */
    public function isPartFunded(int $loanId): bool
    {
        return self::ready() && DB::table('loan_fundings')->where('loan_id', $loanId)->count() > 1;
    }

    /**
     * $total split pro rata to $amounts, to the cent: every part but one is rounded, the remainder part (the house
     * row, else the first) takes what is left, so the parts add up to $total exactly. One amount = $total untouched.
     */
    public static function apportion(float $total, array $amounts, ?int $remainderIndex = null): array
    {
        $amounts = array_values($amounts);
        $sum = array_sum($amounts);
        if (count($amounts) <= 1 || $sum <= 0) {
            return count($amounts) ? [$total] + array_fill(0, count($amounts), 0.0) : [];
        }
        $rest = $remainderIndex ?? 0;
        $parts = [];
        foreach ($amounts as $i => $amount) {
            $parts[$i] = $i === $rest ? 0.0 : round($total * $amount / $sum, 2);
        }
        $parts[$rest] = round($total - array_sum($parts), 2);

        return $parts;
    }

    /** $total split over the loan's rows: [row, part] pairs in row order. */
    public function split(Collection $rows, float $total): array
    {
        $rows = $rows->values();
        $house = $rows->search(fn ($r) => $r->is_house);
        $parts = self::apportion($total, $rows->map(fn ($r) => (float) $r->amount)->all(), $house === false ? null : $house);

        return $rows->map(fn ($r, $i) => [$r, $parts[$i]])->all();
    }

    /** The non-house partner behind a line (null for a house line). */
    public static function partnerOf(?Investment $line): ?int
    {
        if (! $line || $line->isHouseLine()) {
            return null;
        }
        $id = InvestmentFunding::where('investment_id', $line->id)->whereHas('partner', fn ($q) => $q->where('is_house_account', false))
            ->orderBy('id')->value('partner_id');

        return $id ? (int) $id : null;
    }

    /**
     * Record the funding of a new disbursement: $lines = [[investment_id, amount], ...] (they add up to the amount sent).
     */
    public function record(Loan $loan, Disbursement $d, array $lines, string $source = 'disbursement', ?string $notes = null): void
    {
        $total = array_sum(array_map(fn ($l) => (float) $l[1], $lines));
        foreach ($lines as [$lineId, $amount]) {
            $line = $lineId ? Investment::find($lineId) : null;
            DB::table('loan_fundings')->insert(['loan_id' => $loan->id, 'investment_id' => $line?->id, 'partner_id' => self::partnerOf($line),
                'amount' => round((float) $amount, 2), 'share' => $total > 0 ? round((float) $amount / $total, 6) : 1,
                'disbursement_id' => $d->id, 'source' => $source, 'notes' => $notes, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /** Slices of a line the house settled (owner, 3 Nov 2026). */
    public function settledSlices(int $lineId): Collection
    {
        if (! self::ready() || ! Schema::hasColumn('loan_fundings', 'settled_on')) {
            return collect();
        }

        return DB::table('loan_fundings')->where('investment_id', $lineId)->whereNotNull('settled_on')->get();
    }

    /** Loans part funded (several rows) by a line. */
    public function partFundedLoanIds(int $lineId): Collection
    {
        if (! self::ready()) {
            return collect();
        }

        return DB::table('loan_fundings')->where('investment_id', $lineId)
            ->whereIn('loan_id', DB::table('loan_fundings')->select('loan_id')->groupBy('loan_id')->havingRaw('count(*) > 1'))
            ->distinct()->pluck('loan_id');
    }

    /**
     * Loans a line lent to: its cash disbursements (loans not part funded) plus the part-funded loans it has a row on.
     */
    public function loanIdsFor(int $lineId): Collection
    {
        $multi = self::ready() ? DB::table('loan_fundings')->select('loan_id')->groupBy('loan_id')->havingRaw('count(*) > 1')->pluck('loan_id') : collect();
        $byCash = Disbursement::where('investment_id', $lineId)->where('kind', 'cash')->distinct()->pluck('loan_id')
            ->reject(fn ($id) => $multi->contains($id));

        return $byCash->merge($this->partFundedLoanIds($lineId))->unique()->values();
    }

    /**
     * What the split reads as the loan's funding today, for freezing past loans: [investment_id|null, partner_id|null,
     * amount sent in cash]. Null when nothing was sent in cash.
     */
    public function current(Loan $loan): ?array
    {
        $cash = Disbursement::where('loan_id', $loan->id)->where('kind', 'cash')->get(['id', 'amount', 'investment_id']);
        if ($cash->isEmpty()) {
            return null;
        }
        $lineId = $cash->sortBy('id')->first(fn ($d) => $d->investment_id)?->investment_id;
        $line = $lineId ? Investment::find($lineId) : null;

        return [$line?->id, self::partnerOf($line), round((float) $cash->sum('amount'), 2), (int) $cash->sortBy('id')->first()->id];
    }
}
