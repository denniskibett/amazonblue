<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Something that moves or books money failed where nobody is watching (a callback, an after-commit posting, a
 * nightly job). It is logged, and admins get one email per kind of failure per hour, so it can't go unnoticed
 * and a burst of the same failure doesn't flood their inbox.
 */
class StaffAlarm
{
    public static function raise(string $kind, string $subject, string $line, array $context = []): void
    {
        Log::error($subject, $context + ['kind' => $kind]);
        try {
            if (! Cache::add('staff-alarm:' . $kind, 1, 3600)) {
                return;
            }
            $alert = new StaffAlert($subject, $line . ' Details are in the server log (' . now()->format('Y-m-d H:i') . ').',
                'Open the dashboard', route('dashboard'));
            User::whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'admin']))->whereNotNull('email')->get()
                ->each(function (User $user) use ($alert) {
                    try {
                        $user->notify($alert);
                    } catch (\Throwable $e) {
                        Log::warning('Staff alarm email failed', ['user' => $user->id, 'error' => $e->getMessage()]);
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('Staff alarm could not be sent', ['kind' => $kind, 'error' => $e->getMessage()]);
        }
    }
}
