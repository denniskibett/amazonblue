<?php

namespace App\Support;

/**
 * What a late borrower is told about penalties (owner, 25 Oct 2026), from the cycle engine's balance
 * (Loans\CycleBalance::at): what was due on the due date, the penalty so far, the total due today, and roughly how
 * much more each further day adds (the daily rate on the unpaid principal and interest; simple, never compounded).
 */
class PenaltySummary
{
    /** @param  array  $b  a CycleBalance::at() result */
    public static function from(array $b): array
    {
        $unpaid = (float) ($b['unpaid_principal'] ?? 0) + (float) ($b['unpaid_interest'] ?? 0);
        $rate = (float) ($b['penalty_rate'] ?? 0);
        $capped = (bool) ($b['penalty_capped'] ?? false);

        return [
            'due_amount' => round((float) ($b['outstanding_at_due'] ?? 0), 2),
            'penalty' => round((float) ($b['penalty'] ?? 0), 2),
            'unpaid_penalty' => round((float) ($b['unpaid_penalty'] ?? 0), 2),
            'owed' => round((float) ($b['final_outstanding'] ?? 0), 2),
            'rate' => $rate,
            'per_day' => $capped || ! empty($b['penalty_waived_until']) ? 0.0 : round($unpaid * $rate / 100, 2),
            'capped' => $capped,
            'days' => (int) ($b['days_overdue'] ?? 0),
        ];
    }

    /** The lines for an email panel: [label, value]. */
    public static function rows(array $p): array
    {
        $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
        $rows = [['Due on the due date', $kes($p['due_amount'])], ['Late penalty so far', $kes($p['penalty'])]];
        if ($p['per_day'] > 0) {
            $rows[] = ['Each further day adds', 'about ' . $kes($p['per_day']) . ' (' . rtrim(rtrim(number_format($p['rate'], 2), '0'), '.') . '% of the unpaid amount)'];
        } elseif ($p['capped']) {
            $rows[] = ['Penalty', 'has reached its limit'];
        }

        return $rows;
    }
}
