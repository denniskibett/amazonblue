<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Give an account the super-admin role, creating the account first if asked to.
 * A new account gets a random password, printed once; change it after the first login.
 */
class MakeSuperAdmin extends Command
{
    protected $signature = 'users:super-admin
        {email : Email of the account}
        {--create : Create the account if it does not exist}
        {--name=System Administrator : Name for a new account}
        {--phone= : Phone for a new account (required when creating)}';

    protected $description = 'Assign the super-admin role to an account (optionally creating it).';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $similar = User::where('email', 'like', '%' . Str::before($email, '@') . '%')->pluck('email');
            if (! $this->option('create')) {
                $this->error("No account with {$email}.");
                if ($similar->isNotEmpty()) {
                    $this->line('Similar emails: ' . $similar->implode(', '));
                }
                $this->line('Run again with --create to make it.');

                return self::FAILURE;
            }

            if (! $this->option('phone')) {
                $this->error('A new account needs a phone number: add --phone=07XXXXXXXX');

                return self::FAILURE;
            }

            $password = Str::password(16, symbols: false);
            $user = User::create(array_filter([
                'name' => $this->option('name'),
                'email' => $email,
                'phone' => $this->option('phone'),
                'password' => Hash::make($password),
            ], fn ($v) => $v !== null));
            $user->forceFill(['email_verified_at' => now()])->save();
            $this->info("Created {$email}. Temporary password (shown once): {$password}");
        } elseif ($user->trashed()) {
            $this->error("{$email} is deleted. Restore it first.");

            return self::FAILURE;
        }

        Role::findOrCreate('super-admin', 'web');
        $user->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("{$email} now has: " . $user->fresh()->getRoleNames()->implode(', '));

        return self::SUCCESS;
    }
}
