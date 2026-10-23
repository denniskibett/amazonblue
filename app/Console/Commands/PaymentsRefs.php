<?php

namespace App\Console\Commands;

use App\Services\AuditTrail;
use App\Support\PaymentModes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Both references on past payments (owner, 23 Oct 2026). Reads each cash disbursement and repayment's reference:
 *  - an M-Pesa code (10 characters, starts with a letter) goes to mpesa_ref, a bank reference to bank_ref;
 *  - a disbursement marked M-Pesa whose reference is a bank Transaction Ref ID was really sent bank → M-Pesa:
 *    its mode becomes bank_to_mpesa (its M-Pesa code is not in the system; add it on the payment when known);
 *  - old spellings of the mode ("Mpesa", "Bank") are tidied.
 * Dry run by default: CSV in storage/app/private/reports. --apply writes it, with one audit row per table.
 */
class PaymentsRefs extends Command
{
    protected $signature = 'payments:refs {--apply : Write the references and modes}';

    protected $description = 'Fill bank_ref / mpesa_ref from past references and mark bank-to-M-Pesa disbursements (dry run unless --apply)';

    public function handle(): int
    {
        $dir = storage_path('app/private/reports');
        @mkdir($dir, 0775, true);
        $file = $dir . '/payments-refs-' . now()->format('Ymd-Hi') . ($this->option('apply') ? '' : '-dry-run') . '.csv';
        $fh = fopen($file, 'w');
        fputcsv($fh, ['table', 'id', 'loan_id', 'reference', 'mode_before', 'mode_after', 'bank_ref', 'mpesa_ref']);

        foreach (['disbursements' => 'out', 'repayments' => 'in'] as $table => $direction) {
            $rows = DB::table($table)->whereNull('deleted_at')->where(fn ($q) => $q->where('kind', 'cash')->orWhereNull('kind'))
                ->whereNull('bank_ref')->whereNull('mpesa_ref')->orderBy('id')->get(['id', 'loan_id', 'transaction', 'mode']);
            $changes = [];
            $counts = ['mpesa' => 0, 'bank' => 0, 'bank_to_mpesa' => 0, 'mode_tidied' => 0, 'unknown' => 0];
            foreach ($rows as $r) {
                $mode = PaymentModes::normalise($r->mode) ?? $r->mode;   // a blank mode stays as it is
                $shape = PaymentModes::shape($r->transaction);
                [, $bank, $mpesa] = PaymentModes::refs($direction, $mode, $r->transaction, null, null);
                if ($direction === 'out' && $mode === 'mpesa' && $shape === 'bank') {
                    $mode = 'bank_to_mpesa';
                    $counts['bank_to_mpesa']++;
                }
                if (! $bank && ! $mpesa && $mode === $r->mode) {
                    $counts['unknown']++;
                    continue;
                }
                $bank ? $counts['bank']++ : null;
                $mpesa ? $counts['mpesa']++ : null;
                $mode !== $r->mode ? $counts['mode_tidied']++ : null;
                $changes[$r->id] = ['bank_ref' => $bank, 'mpesa_ref' => $mpesa, 'mode' => $mode];
                fputcsv($fh, [$table, $r->id, $r->loan_id, $r->transaction, $r->mode, $mode, $bank, $mpesa]);
            }
            $this->line(str_pad($table, 15) . count($changes) . ' row(s): ' . $counts['mpesa'] . ' M-Pesa code(s), ' . $counts['bank'] . ' bank reference(s), '
                . ($direction === 'out' ? $counts['bank_to_mpesa'] . ' really bank → M-Pesa, ' : '') . $counts['mode_tidied'] . ' mode(s) tidied, ' . $counts['unknown'] . ' left as they are');
            if ($this->option('apply') && $changes) {
                DB::transaction(function () use ($table, $changes) {
                    foreach ($changes as $id => $set) {
                        DB::table($table)->where('id', $id)->update($set);
                    }
                    AuditTrail::record($table, 0, 'update', null, ['event' => 'payments_refs', 'rows' => count($changes)]);
                });
            }
        }
        fclose($fh);
        $this->line('CSV: ' . $file);
        if (! $this->option('apply')) {
            $this->warn('Dry run: nothing changed. Check the CSV, then run with --apply.');
        }

        return self::SUCCESS;
    }
}
