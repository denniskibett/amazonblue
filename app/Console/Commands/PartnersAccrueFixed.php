<?php

namespace App\Console\Commands;

use App\Models\InvestmentFunding;
use App\Services\PartnerLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Fixed-rate partners (owner, 21 Oct 2026): simple interest at investments.interest_rate % a month on the amount
 * committed, for whole calendar months only, starting on the 1st of the month after the funding date. A line
 * that is no longer active (cashed out) earns nothing for the month it left in: early exit = principal plus the
 * completed months. Money left in after the term simply keeps earning (a new locked term).
 *
 * One profit_distribution row per funding per month (reference FIXED-F<id>-<yyyymm>), so it is safe to run any
 * number of times; scheduled on the 1st of each month.
 */
class PartnersAccrueFixed extends Command
{
    protected $signature = 'partners:accrue-fixed {--dry-run : Show what would be posted}';

    protected $description = 'Credit fixed-rate partners their monthly simple interest for each completed calendar month';

    public function handle(PartnerLedger $ledger): int
    {
        $rows = 0;
        $total = 0.0;
        $fundings = InvestmentFunding::with(['investment', 'partner'])
            ->whereHas('investment', fn ($q) => $q->where('return_type', 'fixed_rate'))
            ->whereHas('partner', fn ($q) => $q->where('is_house_account', false))->get();

        foreach ($fundings as $f) {
            $rate = (float) $f->investment->interest_rate;
            $from = Carbon::parse($f->created_at)->startOfMonth()->addMonth();
            // active: up to the last month that has fully ended; left: up to the last full month before leaving
            $stop = ($f->status === 'active' ? now() : Carbon::parse($f->updated_at))->startOfMonth();
            for ($m = $from->copy(); $m->lt($stop); $m->addMonth()) {
                $amount = round((float) $f->amount_committed * $rate / 100, 2);
                $ref = 'FIXED-F' . $f->id . '-' . $m->format('Ym');
                if ($amount <= 0 || \App\Models\PartnerTransaction::where('reference', $ref)->exists()) {
                    continue;
                }
                $rows++;
                $total += $amount;
                if (! $this->option('dry-run')) {
                    $ledger->post($f->partner, 'interest', 'profit_distribution', $amount,
                        investmentId: $f->investment_id, investmentFundingId: $f->id, reference: $ref,
                        notes: sprintf('%s%% for %s on KES %s committed to %s', rtrim(rtrim(number_format($rate, 2), '0'), '.'),
                            $m->format('F Y'), number_format((float) $f->amount_committed, 2), $f->investment->name),
                        transactionDate: $m->copy()->endOfMonth()->toDateString());
                }
            }
        }
        $this->line(sprintf('%d month(s) %s, KES %s.', $rows, $this->option('dry-run') ? 'to credit' : 'credited', number_format($total, 2)));

        return self::SUCCESS;
    }
}
