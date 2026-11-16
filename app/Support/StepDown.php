<?php

namespace App\Support;

/**
 * Step-down interest on rollovers (owner, 16 Nov 2026): a loan that keeps rolling over pays less each step instead of
 * ballooning: the product's rate for cycles 1-3, half for cycle 4, a quarter for cycle 5, then forbearance.
 * The steps are config('lending.rollover.stepdown').
 */
class StepDown
{
    /** @return array<int, float> */
    public static function factors(): array
    {
        return array_map('floatval', (array) config('lending.rollover.stepdown', [1, 1, 1, 0.5, 0.25]));
    }

    /** The rate for a cycle, or null once the steps are over (forbear the loan instead). */
    public static function rate(float $productRate, int $cycleNumber): ?float
    {
        $f = self::factors()[$cycleNumber - 1] ?? null;

        return $f === null ? null : round($productRate * $f, 2);
    }
}
