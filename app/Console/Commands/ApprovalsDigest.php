<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\StaffAlert;
use App\Services\Approvals;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Daily (08:00): second sign-offs waiting more than 24 hours, emailed to the people who can decide them, so an
 * approval never silently blocks a payout or a write-off (Stage 4 Phase 5).
 */
class ApprovalsDigest extends Command
{
    protected $signature = 'approvals:digest';

    protected $description = 'Email the people who can decide second sign-offs waiting more than 24 hours';

    public function handle(): int
    {
        $old = DB::table('approvals')->where('status', 'pending')->where('created_at', '<', now()->subDay())->get();
        if ($old->isEmpty()) {
            $this->line('No sign-off waiting more than 24 hours.');

            return self::SUCCESS;
        }
        $sent = 0;
        foreach ($old->groupBy('action') as $action => $rows) {
            $permission = Approvals::ACTIONS[$action]['permission'] ?? null;
            if (! $permission) {
                continue;
            }
            $users = User::permission($permission)->whereNotNull('email')->get()
                ->reject(fn ($u) => $rows->every(fn ($r) => (int) $r->requested_by === (int) $u->id));
            $line = $rows->count() . ' "' . (Approvals::ACTIONS[$action]['label'] ?? $action) . '" sign-off(s) have waited more than a day for a second person.';
            Notification::send($users, new StaffAlert('Waiting for your sign-off', $line, 'Open second sign-offs', route('staff.sign-offs')));
            $sent += $users->count();
        }
        $this->line($old->count() . ' waiting; ' . $sent . ' email(s) sent.');

        return self::SUCCESS;
    }
}
