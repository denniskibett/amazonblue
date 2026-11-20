<?php

namespace App\Models\Funding;

use Illuminate\Support\Facades\DB;

/**
 * Addresses we never email again: opted out, bounced, complained, or added by hand (Kenya Data Protection Act 2019,
 * s.26 and the right to object to direct marketing). Checked before every cold email.
 */
class Suppression
{
    public const REASONS = ['opt_out' => 'Opted out', 'bounce' => 'Bounced', 'complaint' => 'Complained', 'manual' => 'Added by staff'];

    public static function has(string $email): bool
    {
        return DB::table('funding_suppressions')->where('email', strtolower(trim($email)))->exists();
    }

    public static function add(string $email, string $reason, ?string $source = null): void
    {
        $email = strtolower(trim($email));
        if ($email === '' || self::has($email)) {
            return;
        }
        DB::table('funding_suppressions')->insert(['email' => $email, 'reason' => $reason, 'source' => $source ? mb_substr($source, 0, 255) : null, 'created_at' => now()]);
        DB::table('funding_prospects')->where('email', $email)->whereNull('opted_out_at')->update(['opted_out_at' => now(), 'updated_at' => now()]);
    }
}
