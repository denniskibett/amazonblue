<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie roles are the source of truth; users.role only mirrors the primary role.
 * For every user:
 *   - no Spatie role but users.role set   -> assign that Spatie role (backfill)
 *   - Spatie role(s) and users.role agree -> nothing
 *   - both set but different              -> reported as a conflict, NOT changed (decide by hand)
 *   - neither                             -> reported (treated as a customer, lands on /account)
 */
class RolesSync extends Command
{
    protected $signature = 'roles:sync {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill Spatie roles from the legacy users.role column and report conflicts';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $roles = Role::where('guard_name', 'web')->pluck('name')->all();

        $assigned = [];
        $conflicts = [];
        $unknown = [];
        $none = [];
        $ok = 0;

        User::withTrashed()->with('roles')->orderBy('id')->chunk(200, function ($users) use ($dry, $roles, &$assigned, &$conflicts, &$unknown, &$none, &$ok) {
            foreach ($users as $user) {
                $legacy = $user->getAttributes()['role'] ?? null;
                $spatie = $user->roles->pluck('name');

                if ($spatie->isEmpty()) {
                    if (! $legacy) {
                        $none[] = [$user->id, $user->email, '-', '-'];
                        continue;
                    }
                    if (! in_array($legacy, $roles, true)) {
                        $unknown[] = [$user->id, $user->email, $legacy, '-'];
                        continue;
                    }
                    $assigned[] = [$user->id, $user->email, $legacy, $legacy];
                    if (! $dry) {
                        $user->assignRole($legacy);
                    }
                    continue;
                }

                if ($legacy && ! $spatie->contains($legacy)) {
                    $conflicts[] = [$user->id, $user->email, $legacy, $spatie->implode(', ')];
                    continue;
                }

                $ok++;
            }
        });

        if (! $dry && $assigned) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $head = ['id', 'email', 'users.role', 'spatie roles'];
        $this->info(($dry ? '[dry run] would assign' : 'Assigned') . ' Spatie role to ' . count($assigned) . ' user(s):');
        if ($assigned) {
            $this->table($head, $assigned);
        }
        if ($conflicts) {
            $this->warn(count($conflicts) . ' conflict(s): users.role differs from the Spatie roles. Not changed; fix by editing the user (Spatie wins):');
            $this->table($head, $conflicts);
        }
        if ($unknown) {
            $this->warn(count($unknown) . ' user(s) whose users.role is not a role in the roles table (run the RoleSeeder?):');
            $this->table($head, $unknown);
        }
        if ($none) {
            $this->line(count($none) . ' user(s) with no role at all (treated as customers):');
            $this->table($head, $none);
        }
        $this->line("{$ok} user(s) already consistent.");

        return self::SUCCESS;
    }
}
