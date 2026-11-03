<?php

namespace App\Console\Commands;

use App\Models\Investment;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Services\Ledger;
use App\Services\Loans\RepaymentSplit;
use App\Services\Partners\PartnerLines;
use App\Support\StaffAlarm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nightly reconciliation of partner money (owner, 3 Nov 2026), after the split, the ledger accrual and the lines run:
 *  1. every split repayment shares out exactly what it collected: broker + partners + house = interest + penalty paid;
 *  2. every partner line: lent never more than came in, back never more than lent, a closed line has its payout queued
 *     or paid;
 *  3. what partners have earned and not been paid (their interest and fee wallets) equals the ledger's
 *     "Partner earnings payable";
 *  4. the trial balance is 0.
 * --notify alerts admins (StaffAlarm) when anything is off. Read-only.
 */
class PartnersCheck extends Command
{
    protected $signature = 'partners:check {--notify : Alert admins when something is off} {--since=2026-10-01 : Repayments on or after this day}';

    protected $description = 'Check that partner shares, lines, earnings payable and the trial balance reconcile';

    public function handle(RepaymentSplit $split, PartnerLines $lines, Ledger $ledger): int
    {
        $issues = [];

        // 1. splits share out what each repayment collected
        $checked = 0;
        foreach (Repayment::where('repayment_date', '>=', $this->option('since'))->whereNull('deleted_at')->orderBy('id')->get() as $r) {
            $collected = $split->collected($r);
            if ($collected <= 0) {
                continue;
            }
            $checked++;
            $shared = round((float) PartnerTransaction::where('repayment_id', $r->id)->where('reference', 'like', RepaymentSplit::PREFIX . $r->id . '-%')->sum('amount'), 2);
            if (abs($shared - $collected) > 0.02) {
                $issues[] = "Repayment #{$r->id} (loan #{$r->loan_id}) collected KES " . number_format($collected, 2) . ' of interest and penalty; its split shares out KES ' . number_format($shared, 2);
            }
        }

        // 2. lines
        foreach (Investment::where('return_type', 'loan_share')->orderBy('id')->get() as $line) {
            if ($line->isHouseLine()) {
                continue;
            }
            $s = $lines->status($line);
            if ($s['lent'] - $s['committed'] > 0.01) {
                $issues[] = "Line #{$line->id} ({$line->name}) lent KES " . number_format($s['lent'], 2) . ' but only KES ' . number_format($s['committed'], 2) . ' came in';
            }
            if ($s['back'] - $s['lent'] > 0.01) {
                $issues[] = "Line #{$line->id} ({$line->name}) got back KES " . number_format($s['back'], 2) . ', more than the KES ' . number_format($s['lent'], 2) . ' it lent';
            }
            if ($line->status === 'matured' && ! DB::table('partner_requests')->where('reference', 'like', 'LINE-' . $line->id . '-%')->exists()) {
                $issues[] = "Line #{$line->id} ({$line->name}) is closed but has no payout on Requests";
            }
        }

        // 3. earnings payable: the partners' unpaid earnings wallets against the ledger
        $wallets = round((float) PartnerTransaction::whereIn('wallet_slug', ['interest', 'broker_fee'])
            ->whereIn('partner_id', Partner::where('is_house_account', false)->pluck('id'))->sum('amount'), 2);
        $tb = $ledger->trialBalance();
        // the ledger's partner earnings payable, from the postings themselves (as the trial balance reads them)
        $payable = round(abs((float) DB::table('wallets as w')->join('transactions as t', 't.wallet_id', '=', 'w.id')->where('w.slug', 'gl-earnings')
            ->where('t.confirmed', 1)->whereNull('t.deleted_at')->whereNull('w.deleted_at')
            ->sum(DB::raw("case when t.type = 'deposit' then t.amount else -abs(t.amount) end")) / (10 ** Ledger::dp())), 2);
        if ($payable !== null && abs($payable - $wallets) > 0.02) {
            $issues[] = 'Partner earnings payable in the ledger is KES ' . number_format($payable, 2) . '; partners\' earnings wallets hold KES ' . number_format($wallets, 2);
        }

        // 4. trial balance
        if (abs((float) ($tb['total'] ?? 0)) > 0.005) {
            $issues[] = 'Trial balance is ' . $tb['total'] . ', not 0';
        }

        $this->info("Checked {$checked} split repayment(s), " . Investment::where('return_type', 'loan_share')->count() . ' partner line(s); earnings wallets KES ' . number_format($wallets, 2)
            . ($payable !== null ? ', ledger payable KES ' . number_format($payable, 2) : '') . '; trial balance ' . ($tb['total'] ?? '?') . '.');
        foreach ($issues as $i) {
            $this->warn('  ' . $i);
        }
        if ($issues && $this->option('notify')) {
            StaffAlarm::raise('partners_check', count($issues) . ' partner money check(s) failed', implode("\n", array_slice($issues, 0, 20)));
        }
        if (! $issues) {
            $this->info('Partner money reconciles.');
        }

        return $issues ? self::FAILURE : self::SUCCESS;
    }
}
