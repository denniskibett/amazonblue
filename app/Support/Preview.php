<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * "View as" for the sysadmin (owner, 23 Oct 2026). Who the sysadmin is previewing as, kept in the session:
 *
 *   ['kind' => 'role', 'role' => 'teller']       a stand-in staff member with exactly that role, made in memory only
 *   ['kind' => 'new']                            a stand-in new borrower (no KYC, no loans), made in memory only
 *   ['kind' => 'user', 'user_id' => 123]         a real borrower, partner or broker's own account, read-only
 *
 * Stand-ins are never saved: they have no row in users, and their id points at nobody. Every request made while
 * previewing runs in a database transaction that is always rolled back (Http\Middleware\PreviewAs), with email,
 * SMS, M-Pesa and file storage switched to local fakes, so nothing done in a preview is kept or sent.
 */
class Preview
{
    public const SESSION = 'az_preview';

    /** Ids no real user has: stand-ins use 2,147,480,000 and up. */
    public const BASE_ID = 2147480000;

    public const ROLE_NOTES = [
        'admin' => 'Runs the business: everything except system settings.',
        'manager' => 'Approves loans, signs off money actions, sees reports.',
        'loan-officer' => 'Works the loan inbox, KYC reviews and applications.',
        'accountant' => 'Wallets, reconciliation, partner payouts and reports.',
        'teller' => 'Cash desk: receives payments, sends money, closes the day.',
        'recovery-officer' => 'Collections and debt recovery cases.',
        'auditor' => 'Reads everything, changes nothing.',
        'broker' => 'Their own clients and fees (a customer-side login).',
    ];

    public static function active(): ?array
    {
        $p = session(self::SESSION);

        return is_array($p) ? $p : null;
    }

    /** The user to show the app as, or null when the preview no longer applies. */
    public static function persona(array $p): ?User
    {
        return match ($p['kind'] ?? null) {
            'role' => self::standIn((string) $p['role']),
            'new' => self::standIn('borrower', 'New borrower (preview)'),
            'user' => User::find((int) ($p['user_id'] ?? 0)),
            default => null,
        };
    }

    public static function label(array $p): string
    {
        return match ($p['kind'] ?? null) {
            'role' => \Illuminate\Support\Str::headline((string) $p['role']) . ' (stand-in)',
            'new' => 'a new borrower (stand-in)',
            'user' => (User::find((int) ($p['user_id'] ?? 0))?->name ?? 'someone') . ' (their account, read-only)',
            default => 'someone',
        };
    }

    /** A user that exists only for this request: the role and its permissions, nothing else. */
    public static function standIn(string $roleName, ?string $name = null): ?User
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->with('permissions')->first();
        if (! $role || $roleName === 'super-admin') {
            return null;
        }
        $index = array_search($roleName, Role::orderBy('id')->pluck('name')->all(), true);
        $u = new User();
        $u->forceFill([
            'name' => $name ?? \Illuminate\Support\Str::headline($roleName) . ' (preview)',
            'email' => 'preview-' . $roleName . '@preview.invalid', 'phone' => null, 'status' => 1,
            'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $u->id = self::BASE_ID + (int) $index;
        $u->exists = true;            // treated as saved, but there is no row: an update touches nothing
        $u->setRelation('roles', collect([$role]));
        $u->setRelation('permissions', collect());

        return $u;
    }

    /**
     * For a request that saves something: writes the stand-in into users (and its role) inside the preview's
     * transaction, so records that name who did it can point at it. It is rolled back with everything else.
     * Only on saving requests, so browsing doesn't use up user numbers.
     */
    public static function materialize(User $u): User
    {
        if ($u->id < self::BASE_ID) {
            return $u;                              // a real account: nothing to write
        }
        $roles = $u->roles;
        $row = new User();
        $row->forceFill(array_merge(\Illuminate\Support\Arr::only($u->getAttributes(), ['name', 'email', 'status', 'email_verified_at']),
            ['password' => \Illuminate\Support\Str::random(40), 'phone' => '099' . random_int(1000000, 9999999)])); // no real number starts 099
        $row->saveQuietly();
        foreach ($roles as $role) {
            \Illuminate\Support\Facades\DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->insert(['role_id' => $role->id, 'model_type' => $row->getMorphClass(), 'model_id' => $row->id]);
        }
        $row->setRelation('roles', $roles);
        $row->setRelation('permissions', collect());

        return $row;
    }

    /** Staff roles a sysadmin can preview (everything but super-admin and the customer-side roles). */
    public static function staffRoles(): array
    {
        return collect(User::STAFF_ROLES)->reject(fn ($r) => $r === 'super-admin')
            ->filter(fn ($r) => Role::where('name', $r)->exists())->values()->all();
    }
}
