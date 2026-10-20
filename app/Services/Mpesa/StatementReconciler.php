<?php

namespace App\Services\Mpesa;

use App\Models\MpesaTransaction;
use App\Models\Repayment;

/**
 * M-Pesa statement reconciliation (Stage 4 Phase 4). Reads an M-Pesa organisation statement CSV (columns
 * include "Receipt No." and "Paid In"; header names are matched loosely) and compares it, receipt by receipt,
 * with what was recorded:
 *   on_statement_not_recorded   money arrived but no repayment carries the receipt
 *   recorded_not_on_statement   a repayment says M-Pesa but the receipt isn't on the statement
 *   amount_differs              both sides have the receipt with different amounts
 * Only the date range the statement covers is checked on our side.
 */
class StatementReconciler
{
    public function reconcile(string $path): array
    {
        $fh = fopen($path, 'r');
        $header = null;
        $rows = [];
        while (($line = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            if (! $header) {
                $norm = array_map(fn ($h) => strtolower(preg_replace('/[^a-z]/i', '', (string) $h)), $line);
                if (in_array('receiptno', $norm, true) || in_array('receipt', $norm, true)) {
                    $header = $norm;
                }
                continue;
            }
            $r = array_combine(array_slice($header, 0, count($line)), array_slice($line, 0, count($header)));
            $receipt = strtoupper(trim((string) ($r['receiptno'] ?? $r['receipt'] ?? '')));
            $in = (float) str_replace(',', '', (string) ($r['paidin'] ?? $r['amount'] ?? 0));
            if ($receipt !== '' && $in > 0) {
                $rows[$receipt] = ['amount' => $in, 'time' => $r['completiontime'] ?? $r['date'] ?? null, 'details' => $r['details'] ?? null];
            }
        }
        fclose($fh);

        $recorded = Repayment::where(fn ($q) => $q->where('mode', 'mpesa')->orWhereIn('transaction', array_keys($rows)))
            ->get(['id', 'loan_id', 'transaction', 'amount', 'repayment_date'])->keyBy(fn ($r) => strtoupper((string) $r->transaction));
        $dates = array_filter(array_map(fn ($r) => $r['time'] ? date('Y-m-d', strtotime((string) $r['time'])) : null, $rows));
        [$from, $to] = $dates ? [min($dates), max($dates)] : [null, null];

        $report = ['statement_rows' => count($rows), 'from' => $from, 'to' => $to,
            'on_statement_not_recorded' => [], 'recorded_not_on_statement' => [], 'amount_differs' => [], 'ok' => 0];
        foreach ($rows as $receipt => $s) {
            $r = $recorded[$receipt] ?? null;
            if (! $r) {
                $q = MpesaTransaction::where('trans_id', $receipt)->first();
                $report['on_statement_not_recorded'][] = ['receipt' => $receipt, 'amount' => $s['amount'], 'time' => $s['time'],
                    'queue' => $q ? $q->status : 'not received from Daraja'];
            } elseif (abs((float) $r->amount - $s['amount']) >= 0.01) {
                $report['amount_differs'][] = ['receipt' => $receipt, 'statement' => $s['amount'], 'recorded' => (float) $r->amount, 'repayment' => $r->id];
            } else {
                $report['ok']++;
            }
        }
        if ($from) {
            foreach ($recorded as $receipt => $r) {
                $day = optional($r->repayment_date)->toDateString();
                if ($day >= $from && $day <= $to && preg_match('/^[A-Z0-9]{10}$/', $receipt) && ! isset($rows[$receipt])) {
                    $report['recorded_not_on_statement'][] = ['receipt' => $receipt, 'amount' => (float) $r->amount, 'repayment' => $r->id, 'loan' => $r->loan_id, 'date' => $day];
                }
            }
        }

        return $report;
    }
}
