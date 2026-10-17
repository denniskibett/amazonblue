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
}
