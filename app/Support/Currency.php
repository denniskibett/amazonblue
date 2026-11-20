<?php

namespace App\Support;

/**
 * Which currency figures are shown in. book(): the currency of every stored amount without its own (KES, never
 * changes). display(): the system display currency a sysadmin chose (Integrations › Currency, else .env), if it is
 * one we know; else the book. Display only: stored amounts and what anyone is charged keep their own currency.
 */
class Currency
{
    public static function book(): string
    {
        return strtoupper((string) config('currency.book', 'KES'));
    }

    public static function all(): array
    {
        return (array) config('currency.currencies', []);
    }

    public static function supported(?string $code): bool
    {
        return $code !== null && isset(self::all()[strtoupper(trim($code))]);
    }

    public static function normalize(?string $code): string
    {
        return self::supported($code) ? strtoupper(trim((string) $code)) : self::book();
    }

    public static function decimals(string $code): int
    {
        return (int) (self::all()[strtoupper($code)]['decimals'] ?? 2);
    }

    public static function display(): string
    {
        return self::normalize((string) config('currency.display'));
    }
}
