<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\Partner;
use App\Services\Partners\PartnerLinks;
use Illuminate\Console\Command;

/**
 * Partners borrowing for themselves (owner, 23 Oct 2026): a partner pays the full rate on their own loans and
 * their broker share of every repayment comes back to their broker wallet. This finds a partner's own loans that
 * are not brokered yet and shows, per loan, what would move (dry run, CSV in storage/app/private/reports).
 * --apply marks them brokered by the partner and re-splits their repayments (new journal rows; nothing edited).
 * Loans already brokered by someone else are left alone.
 */
class PartnersOwnLoans extends Command
{
    protected $signature = 'partners:own-loans {--partner= : Only this partner id} {--apply : Make the change}';

    protected $description = 'Give partners their broker share back on their own loans (dry run unless --apply)';

    public function handle(PartnerLinks $links): int
    {
        $partners = Partner::where('is_house_account', false)->whereNotNull('user_id')
            ->when($this->option('partner'), fn ($q, $id) => $q->whereKey($id))->orderBy('id')->get();
        $dir = storage_path('app/private/reports');
        @mkdir($dir, 0775, true);
        $file = $dir . '/partners-own-loans-' . now()->format('Ymd-Hi') . ($this->option('apply') ? '' : '-dry-run') . '.csv';
        $fh = fopen($file, 'w');
        fputcsv($fh, ['partner_id', 'partner', 'loan_id', 'status', 'amount', 'back_to_partner', 'off_ac_share']);
        $total = 0.0;
        $count = 0;

        foreach ($partners as $p) {
            $loans = Loan::where('user_id', $p->user_id)->where(fn ($q) => $q->where('broker_status', 0)->orWhereNull('broker_status'))
                ->whereNotIn('status', ['pending', 'rejected', 'approved'])->orderBy('id')->get();
            foreach ($loans as $loan) {
                $pv = $links->preview($loan, $p->id, null);
                $back = round(array_sum($pv['after'][$p->name] ?? []) - array_sum($pv['before'][$p->name] ?? []), 2);
                fputcsv($fh, [$p->id, $p->name, $loan->id, $loan->status, number_format((float) $loan->amount, 2, '.', ''), number_format($back, 2, '.', ''), number_format(-$back, 2, '.', '')]);
                $total += $back;
                $count++;
                if ($this->option('apply')) {
                    $links->apply($loan, $p->id, null, null, 'partner_own_loan');
                }
            }
            if ($loans->isNotEmpty()) {
                $this->line(str_pad($p->name, 32) . $loans->count() . ' own loan(s)');
            }
        }
        fclose($fh);
        $this->info($count . ' loan(s); KES ' . number_format($total, 2) . ' of interest and penalty already collected ' . ($this->option('apply') ? 'moved' : 'would move') . ' from AC to the partners.');
        $this->line('CSV: ' . $file);
        if (! $this->option('apply')) {
            $this->warn('Dry run: nothing changed. Check the CSV, then run with --apply.');
        }

        return self::SUCCESS;
    }
}
