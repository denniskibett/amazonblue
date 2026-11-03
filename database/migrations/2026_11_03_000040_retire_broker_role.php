<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retire the "broker" role (owner, 3 Nov 2026). A broker is a short-term partner: a partner who brings borrowers and
 * can fund them loan by loan. The data already works that way (loans.partner_id + broker_status point at the partner;
 * `brokers` holds the partner's introducer terms), so no money table changes. Anyone still holding the role gets the
 * partner role and a partner row, users.role follows, and the role is deleted. Tables and columns named broker stay.
 * SQL twin: database/sql/2026_11_03_retire_broker_role.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }
        $broker = DB::table('roles')->where('name', 'broker')->where('guard_name', 'web')->value('id');
        if (! $broker) {
            return;
        }
        $partner = DB::table('roles')->where('name', 'partner')->where('guard_name', 'web')->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'partner', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $model = 'App\\Models\\User';

        DB::transaction(function () use ($broker, $partner, $model) {
            $userIds = DB::table('model_has_roles')->where('role_id', $broker)->where('model_type', $model)->pluck('model_id');
            foreach ($userIds as $id) {
                DB::table('model_has_roles')->insertOrIgnore(['role_id' => $partner, 'model_type' => $model, 'model_id' => $id]);
                $user = DB::table('users')->where('id', $id)->first(['id', 'name', 'email', 'phone']);
                if ($user && ! DB::table('partners')->where('user_id', $id)->exists()) {
                    DB::table('partners')->insert(['user_id' => $id, 'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
                        'type' => 'individual', 'status' => 'active', 'profit_share_rate' => 0, 'risk_tolerance' => 'moderate',
                        'notes' => 'Short-term partner (was the broker role, 3 Nov 2026).', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            DB::table('users')->where('role', 'broker')->update(['role' => 'partner']);
            DB::table('model_has_roles')->where('role_id', $broker)->delete();
            DB::table('role_has_permissions')->where('role_id', $broker)->delete();
            DB::table('roles')->where('id', $broker)->delete();
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // the role's users are now partners; recreate the role by hand if it is ever needed again
    }
};
