<?php

namespace App\Support;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Wallet handles (rule 9): lowercase letters and digits, no spaces, shown as @handle.
 *
 *  - people: first + last word of the name (Dennis Kibet -> denniskibet, Edward K. Kibet -> edwardkibet);
 *    a one-word name is that word
 *  - companies (partners of type corporate/business/company): the whole name compacted
 *    (IMAGINE-NATION AGENCY LIMITED -> imaginenationagencylimited)
 *  - collisions get 2, 3, ... appended (ianokoth, ianokoth2); a handle is unique across users AND
 *    partners, so @handle always points at one wallet holder; the one exception is a partner linked to
 *    a user (a broker's partner record), which shares that user's handle
 */
class Handle
{
    public const COMPANY_TYPES = ['corporate', 'business', 'company'];

    private static array $columnExists = [];

    public static function forUser(User $user): ?string
    {
        if (! self::enabled('users')) {
            return null;
        }

        return self::unique(self::fromPersonName($user->name) ?: 'user', 'users', $user->getKey());
    }

    public static function forPartner(Partner $partner): ?string
    {
        if (! self::enabled('partners')) {
            return null;
        }
        // a partner that is a user's own partner record (e.g. a broker) shares that user's handle
        $userHandle = $partner->user_id ? DB::table('users')->where('id', $partner->user_id)->value('handle') : null;
        if ($userHandle && DB::table('partners')->where('handle', $userHandle)->when($partner->getKey(), fn ($q, $id) => $q->where('id', '<>', $id))->doesntExist()) {
            return $userHandle;
        }
        $name = $partner->name ?: $partner->company_name;
        $base = in_array($partner->type, self::COMPANY_TYPES, true) ? self::compact($name) : self::fromPersonName($name);

        return self::unique($base ?: 'partner', 'partners', $partner->getKey());
    }

    public static function fromPersonName(?string $name): string
    {
        $words = array_values(array_filter(array_map([self::class, 'compact'], preg_split('/\s+/', trim((string) $name)))));
        if (! $words) {
            return '';
        }

        return count($words) === 1 ? $words[0] : $words[0] . end($words);
    }

    public static function compact(?string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $text)));
    }

    /**
     * $base, or $base2, $base3, ... : the first not taken by another user or partner.
     */
    public static function unique(string $base, string $ownTable, $ownId = null): string
    {
        $base = substr($base, 0, 60);
        $taken = collect();
        foreach (['users', 'partners'] as $table) {
            if (! self::enabled($table)) {
                continue;
            }
            $taken = $taken->merge(DB::table($table)
                ->where(fn ($q) => $q->where('handle', $base)->orWhere('handle', 'like', $base . '%'))
                ->when($table === $ownTable && $ownId, fn ($q) => $q->where('id', '<>', $ownId))
                ->pluck('handle'));
        }
        $taken = $taken->map(fn ($h) => strtolower((string) $h))->flip();

        $handle = $base;
        for ($n = 2; isset($taken[$handle]); $n++) {
            $handle = $base . $n;
        }

        return $handle;
    }

    /**
     * The handle column exists (code can ship before the migration runs without breaking saves).
     */
    public static function enabled(string $table): bool
    {
        return self::$columnExists[$table] ??= Schema::hasColumn($table, 'handle');
    }
}
