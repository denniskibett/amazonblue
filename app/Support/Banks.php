<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Banks in Kenya for payout dropdowns, read from resources/data/banks-kenya.txt
 * ("Name | code" per line, # for comments). Edit that file to add or rename banks;
 * the list is re-read whenever the file changes.
 */
class Banks
{
    public const OTHER = 'Other';

    /** Saved as bank_code when staff type a bank that is not on the list (its name goes in bank_name). */
    public const OTHER_CODE = 'other';

    /**
     * @return array<int, array{name: string, code: ?string}>
     */
    public static function all(): array
    {
        $path = resource_path('data/banks-kenya.txt');
        if (! is_file($path)) {
            return [];
        }

        return Cache::rememberForever('banks-kenya:' . filemtime($path), function () use ($path) {
            $banks = [];
            foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                [$name, $code] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
                if ($name !== '') {
                    $banks[] = ['name' => $name, 'code' => $code !== '' ? $code : null];
                }
            }

            return $banks;
        });
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }

    /** One bank by its id (or its full name, for older forms that sent the name). */
    public static function find(string $codeOrName): ?array
    {
        $key = mb_strtolower(trim($codeOrName));
        foreach (self::all() as $b) {
            if (($b['code'] !== null && mb_strtolower($b['code']) === $key) || mb_strtolower($b['name']) === $key) {
                return $b;
            }
        }

        return null;
    }

    /** id => name for dropdowns (banks with an id only). */
    public static function options(): array
    {
        return collect(self::all())->filter(fn ($b) => $b['code'])->mapWithKeys(fn ($b) => [$b['code'] => $b['name']])->all();
    }

    /** A short name for statements and lists: "Equity Bank Kenya Limited" -> "Equity Bank". */
    public static function short(?string $name): ?string
    {
        if (! $name) {
            return null;
        }
        $n = preg_replace('/\s*\((K|Kenya)\)\s*/i', ' ', $name);
        $n = preg_replace('/\s+(PLC|Limited|Ltd\.?)$/i', '', trim($n));
        $n = preg_replace('/\s+(of\s+)?Kenya$/i', '', trim($n));

        return trim($n) ?: $name;
    }

    /** "I&M Bank → Stanbic Bank": money we sent goes ours → client's, money we received client's → ours (owner, 16 Nov 2026). */
    public static function route(object $row, bool $weSent): ?string
    {
        $ours = self::short($row->bank_name ?? null);
        $theirs = self::short($row->client_bank_name ?? null);
        if (! $ours && ! $theirs) {
            return null;
        }
        if (! $theirs) {
            return $ours;
        }
        if (! $ours) {
            return $weSent ? '→ ' . $theirs : $theirs . ' →';
        }

        return $weSent ? $ours . ' → ' . $theirs : $theirs . ' → ' . $ours;
    }
}
