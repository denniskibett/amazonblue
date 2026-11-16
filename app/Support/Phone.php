<?php

namespace App\Support;

/**
 * One stored format for people's phones (owner, 16 Nov 2026): Kenyan numbers as +2547…/+2541…,
 * other countries as + and their digits. Searches match a number typed as 07…, 2547… or +254 7…
 */
class Phone
{
    /** +254712345678 for any Kenyan form (0712…, 712…, 254712…, +254 712 …); +<digits> for a foreign number typed with +; else as typed. */
    public static function normal(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $d = preg_replace('/\D+/', '', $raw);
        if (preg_match('/^(?:254|0)?([17]\d{8})$/', $d, $m)) {
            return '+254' . $m[1];
        }
        if (str_starts_with($raw, '+') && strlen($d) >= 10 && strlen($d) <= 15) {
            return '+' . $d;
        }

        return $raw;
    }

    /** A LIKE pattern that finds a stored phone however the search was typed (the leading 0 or 254 dropped). */
    public static function like(string $q): string
    {
        $d = preg_replace('/\D+/', '', $q);
        if (strlen($d) >= 4 && preg_match('/^[\d\s+\-()]+$/', trim($q))) {
            $d = preg_replace('/^(?:254|0)(?=[17])/', '', $d);

            return '%' . $d . '%';
        }

        return '%' . addcslashes($q, '%_\\') . '%';
    }
}
