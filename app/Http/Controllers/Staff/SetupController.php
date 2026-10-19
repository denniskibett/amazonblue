<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LoanType;
use App\Services\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin set-up screens: roles x permissions (read-only, one guarded edit) and loan products (read-only).
 */
class SetupController extends Controller
{
    /** Never edited from the screen. */
    public const IMMUTABLE_ROLES = ['super-admin'];

    public function roles()
    {
        $roles = Role::with('permissions:id,name')->orderBy('id')->get();
        $permissions = Permission::orderBy('name')->get(['id', 'name']);

        // users per role (Spatie pivot), names for small roles
        $members = DB::table('model_has_roles as mr')
            ->join('users as u', 'u.id', '=', 'mr.model_id')
            ->where('mr.model_type', (new \App\Models\User)->getMorphClass())
            ->whereNull('u.deleted_at')
            ->get(['mr.role_id', 'u.id', 'u.name'])
            ->groupBy('role_id');

        // group permissions by their subject word ("view loans" -> loans)
        $groups = $permissions->groupBy(function ($p) {
            $words = explode(' ', $p->name);
            return count($words) > 1 ? implode(' ', array_slice($words, 1)) : $p->name;
        })->sortKeys();

        return view('staff.desk.roles', compact('roles', 'permissions', 'members', 'groups'));
    }

    /** Give or remove one permission on one role (Spatie API), reset the cache and audit it. */
    public function updateRolePermission(Request $request, Role $role)
    {
        $data = $request->validate([
            'permission' => 'required|exists:permissions,name',
            'change' => 'required|in:give,remove',
        ]);

        if (in_array($role->name, self::IMMUTABLE_ROLES, true)) {
            return back()->with('error', 'The ' . $role->name . ' role cannot be changed here.');
        }

        $before = $role->permissions()->pluck('name')->sort()->values()->all();
        $has = in_array($data['permission'], $before, true);

        if ($data['change'] === 'give' && ! $has) {
            $role->givePermissionTo($data['permission']);
        } elseif ($data['change'] === 'remove' && $has) {
            $role->revokePermissionTo($data['permission']);
        } else {
            return back()->with('error', 'No change: the ' . $role->name . ' role ' . ($has ? 'already has' : 'does not have') . ' "' . $data['permission'] . '".');
        }

        Artisan::call('permission:cache-reset');

        AuditTrail::record('roles', $role->id, 'update',
            ['role' => $role->name, 'permission' => $data['permission'], 'had' => $has],
            ['role' => $role->name, 'permission' => $data['permission'], 'has' => ! $has, 'change' => $data['change']]);

        return back()->with('success', ($data['change'] === 'give' ? 'Gave' : 'Removed') . ' "' . $data['permission'] . '" ' . ($data['change'] === 'give' ? 'to' : 'from') . ' the ' . $role->name . ' role.');
    }

    public function products()
    {
        $products = LoanType::orderBy('name')->get();
        $openLoans = DB::table('loans')->whereNull('deleted_at')
            ->whereNotIn('status', ['repaid', 'rejected', 'written_off'])
            ->select('loan_type_id', DB::raw('count(*) as n'))->groupBy('loan_type_id')->pluck('n', 'loan_type_id');

        return view('staff.desk.products', compact('products', 'openLoans'));
    }
}
