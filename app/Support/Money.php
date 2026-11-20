<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Money for display (ported from scharge). format() keeps an amount in its own currency; show() converts a book
 * amount to the display currency at Sharet's rate; note() is the line with the rate and its date. Nothing here
 * changes a stored amount or what someone is charged. With no rate, the figure stays in its own currency.
 */
class Money
{
    public static function convert(float|int|string|null $amount, ?string $from = null, ?string $to = null): ?float
    {
        $from = Currency::normalize($from ?? Currency::book());
        $to = Currency::normalize($to ?? Currency::display());
        $rate = FxRates::rate($from, $to);

        return $rate === null ? null : round((float) $amount * $rate, Currency::decimals($to));
    }

    public static function format(float|int|string|null $amount, ?string $currency = null, ?int $decimals = null): string
    {
        $currency = strtoupper(trim((string) ($currency ?: Currency::book())));

        return $currency . ' ' . number_format((float) $amount, $decimals ?? Currency::decimals($currency));
    }

    /** A book amount (or one in $from) shown in the display currency; its own currency when there is no rate. */
    public static function show(float|int|string|null $amount, ?string $from = null, ?string $to = null, ?int $decimals = null): string
    {
        $from = strtoupper(trim((string) ($from ?: Currency::book())));
        $to = Currency::normalize($to ?? Currency::display());
        $c = Currency::supported($from) && $from !== $to ? self::convert($amount, $from, $to) : null;

        return $c === null ? self::format($amount, $from, $decimals) : self::format($c, $to, $decimals);
    }

    /** Sum [amount, currency] pairs into $to; amounts with no rate are left out and listed in 'missing'. */
    public static function total(iterable $items, ?string $to = null): array
    {
        $to = Currency::normalize($to ?? Currency::display());
        $sum = 0.0;
        $missing = [];
        foreach ($items as $i) {
            $cur = strtoupper(trim((string) ($i[1] ?? Currency::book())));
            $c = Currency::supported($cur) ? self::convert($i[0] ?? 0, $cur, $to) : null;
            if ($c === null) {
                $missing[] = $cur;

                continue;
            }
            $sum += $c;
        }

        return ['amount' => round($sum, Currency::decimals($to)), 'currency' => $to, 'missing' => array_values(array_unique($missing))];
    }

    /** "Shown in USD at Sharet's rate of 20 Nov 2026 (1 KES = 0.0077 USD)." or null when nothing is converted. */
    public static function note(?string $from = null, ?string $to = null): ?string
    {
        $from = Currency::normalize($from ?? Currency::book());
        $to = Currency::normalize($to ?? Currency::display());
        if ($from === $to) {
            return null;
        }
        $rate = FxRates::rate($from, $to);
        $snap = FxRates::snapshot();
        if ($rate === null || $snap === null) {
            return "No Sharet rate for $from to $to yet, so amounts are shown in $from.";
        }
        $f = $rate;
        $dp = $f >= 100 ? 2 : ($f >= 1 ? 4 : max(4, (int) ceil(-log10($f)) + 3));
        $line = "Shown in $to at Sharet's rate of " . Carbon::parse($snap['date'])->format('j M Y') . " (1 $from = " . rtrim(rtrim(number_format($f, $dp, '.', ','), '0'), '.') . " $to).";

        return ! empty($snap['stale']) ? rtrim($line, '.') . '; Sharet could not be reached, so this is the last known rate.' : $line;
    }
}
