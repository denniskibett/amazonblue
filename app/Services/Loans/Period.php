<?php

namespace App\Services\Loans;

use Illuminate\Support\Carbon;

/**
 * A product period (loan_types.period + unit) added to a date. Accepts singular and plural units, so a
 * product saved as "1 year" is due a year later, not one day later (B §1).
 */
class Period
{
    public static function add(Carbon $from, int $period, string $unit): Carbon
    {
        $d = $from->copy();

        return match (rtrim(strtolower(trim($unit)), 's')) {
            'week' => $d->addWeeks($period),
            'month' => $d->addMonthsNoOverflow($period), // 31 Jan + 1 month = 28 Feb, not 3 Mar
            'year' => $d->addYearsNoOverflow($period),
            default => $d->addDays($period),
        };
    }
}
