<?php

namespace App\Console\Commands;

use App\Models\Disbursement;
use App\Models\PartnerTransaction;
use App\Models\Repayment;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Posts the ledger for every existing disbursement, repayment and partner journal row (Stage 4 Phase 1),
 * with today's meaning (see App\Services\Ledger). Safe to run any number of times: each source is brought
 * to its plan, so a second run posts nothing.
 *
 * --dry-run runs everything inside a transaction that is rolled back (no wallet or posting survives) and
 * writes the CSV of what would be posted, including every row that is not posted and why.
 */
class LedgerBackfill extends Command
{
    protected $signature = 'ledger:backfill
        {--dry-run : Write the CSV of what would be posted; change nothing}';

    protected $description = 'Post the double-entry ledger (bavix gl- wallets) for existing money rows; dry-run first';

    public function handle(Ledger $ledger): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry && ! Ledger::enabled()) {
            $this->error('lending.ledger.enabled is off. Turn on LENDING_LEDGER_ENABLED first, so rows saved during and after the backfill post too.');

            return self::FAILURE;
        }

        $csv = [];
        $sum = ['sources' => 0, 'posted' => 0, 'not_posted' => 0, 'transfers' => 0];
        $notes = [];
        $names = [];
        $name = function (int $walletId) use (&$names) {
            return $names[$walletId] ??= (function () use ($walletId) {
                $w = Wallet::find($walletId);

                return $w ? $w->slug . '@' . class_basename($w->holder_type) . '#' . $w->holder_id : '#' . $walletId;
            })();
        };

        $sources = [
            'disbursement' => fn () => Disbursement::query()->orderBy('id')->lazy(),
            'repayment' => fn () => Repayment::withTrashed()->orderBy('id')->lazy(),
            'partner_transaction' => fn () => PartnerTransaction::query()->orderBy('id')->lazy(),
        ];
        if (\App\Services\LoanCalculator::v2()) {
            // engine v2: each cycle's interest, penalty and carry difference
            $sources['loan_cycle'] = fn () => \App\Models\LoanCycle::query()->orderBy('id')->lazy();
        }

        if ($dry) {
            DB::beginTransaction();
        }
        try {
            foreach ($sources as $source => $rows) {
                foreach ($rows() as $row) {
                    $sum['sources']++;
                    [$plan, $what, $date, $ref] = match ($source) {
                        'disbursement' => [$ledger->planDisbursement($row), CustomerWallets::kindOf($row), $row->disburse_date, $row->transaction],
                        'repayment' => [$ledger->planRepayment($row), CustomerWallets::kindOf($row), $row->repayment_date, $row->transaction],
                        'partner_transaction' => [$ledger->planPartnerTransaction($row), $row->type, $row->transaction_date, $row->reference],
                        'loan_cycle' => [$ledger->planLoanCycle($row), 'cycle ' . $row->cycle_number . ' of loan ' . $row->loan_id, $row->start_date, null],
                    };
                    $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : '';
                    // a real run posts through the same path as live saves (with the row's date, reference, loan)
                    $steps = $dry
                        ? $ledger->sync($source, $row->id, $plan['legs'], [], true)
                        : $ledger->{'sync' . str_replace('_', '', ucwords($source, '_'))}($row);

                    if ($plan['note'] !== null) {
                        $sum['not_posted']++;
                        $notes[$source . ': ' . $plan['note']] = ($notes[$source . ': ' . $plan['note']] ?? 0) + 1;
                        $csv[] = [$source, $row->id, $date, $ref, $what, '', '', '', (float) ($row->amount ?? 0), 0, $plan['note']];
                        // posted earlier under an older rule: the sync reverses it, and the CSV says so
                        foreach ($steps as $st) {
                            $csv[] = [$source, $row->id, $date, $ref, $what, $st['leg'], $name($st['from']), $name($st['to']), 0,
                                round($st['minor'] / (10 ** Ledger::dp()), 4), 'reverses an earlier posting'];
                        }
                        $sum['transfers'] += count($steps);
                        continue;
                    }
                    $sum['posted']++;
                    foreach ($plan['legs'] as $l) {
                        $toPost = collect($steps)->where('leg', $l['leg'])->where('from', $l['from']->getKey())->where('to', $l['to']->getKey())->sum('minor');
                        $csv[] = [$source, $row->id, $date, $ref, $what, $l['leg'], $name($l['from']->getKey()), $name($l['to']->getKey()),
                            round($l['amount'], 4), round($toPost / (10 ** Ledger::dp()), 4), $toPost ? '' : 'already posted'];
                    }
                    $sum['transfers'] += count($steps);
                }
            }
            $tb = $ledger->trialBalance();
        } finally {
            if ($dry) {
                DB::rollBack();
            }
        }

        $path = 'reports/ledger-backfill-' . now()->format('Ymd-His') . ($dry ? '-dry-run' : '') . '.csv';
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['source', 'id', 'date', 'reference', 'kind_or_type', 'leg', 'credit_account', 'debit_account', 'amount', 'to_post', 'note']);
        foreach ($csv as $line) {
            fputcsv($fh, $line);
        }
        rewind($fh);
        Storage::disk('local')->put($path, stream_get_contents($fh));
        fclose($fh);

        $this->table(['rows read', 'posted', 'not posted', 'transfers ' . ($dry ? 'to post' : 'posted')],
            [[$sum['sources'], $sum['posted'], $sum['not_posted'], $sum['transfers']]]);
        if ($notes) {
            ksort($notes);
            $this->table(['not posted, because', 'rows'], collect($notes)->map(fn ($n, $k) => [$k, $n])->values()->all());
        }
        $this->line($dry ? 'Dry run: nothing posted, so the ledger is as it was.' : 'Trial balance after: ' . number_format($tb['total'], 4) . ' (must be 0) ' . json_encode($tb['by_type']));
        $this->line(($dry ? 'Dry run, rolled back. ' : 'Done. ') . 'CSV: ' . Storage::disk('local')->path($path));

        return abs($tb['total']) < 0.0001 ? self::SUCCESS : self::FAILURE;
    }
}
