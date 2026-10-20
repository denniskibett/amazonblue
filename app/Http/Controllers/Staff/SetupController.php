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

        $versions = DB::table('loan_type_versions')->select('loan_type_id', DB::raw('count(*) as n'))->groupBy('loan_type_id')->pluck('n', 'loan_type_id');
        $editable = \App\Services\LoanCalculator::v2() && auth()->user()->can('manage loan types');

        return view('staff.desk.products', compact('products', 'openLoans', 'versions', 'editable'));
    }

    /**
     * Edit a product (Stage 4 Phase 5). Only on engine v2, where each cycle keeps the terms it was created with,
     * so running loans are not changed; the values before the edit are kept in loan_type_versions.
     */
    public function updateProduct(\Illuminate\Http\Request $request, LoanType $loanType)
    {
        if (! \App\Services\LoanCalculator::v2()) {
            return back()->with('error', 'Products can be edited once the v2 engine is on (running loans would otherwise change).');
        }
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'interest_rate' => 'required|numeric|min:0|max:500',
            'penalty_rate' => 'required|numeric|min:0|max:100',
            'period' => 'required|integer|min:1|max:120',
            'unit' => 'required|in:days,weeks,months,years',
            'grace_period_days' => 'required|integer|min:0|max:365',
            'default_threshold_days' => 'required|integer|min:1|max:3650',
            'reason' => 'required|string|min:5|max:500',
        ]);
        $fields = ['name', 'interest_rate', 'penalty_rate', 'period', 'unit', 'grace_period_days', 'default_threshold_days'];
        DB::transaction(function () use ($loanType, $data, $fields, $request) {
            $version = (int) DB::table('loan_type_versions')->where('loan_type_id', $loanType->id)->max('version') + 1;
            DB::table('loan_type_versions')->insert(['loan_type_id' => $loanType->id, 'version' => $version,
                'values' => json_encode($loanType->only($fields)), 'changed_by' => $request->user()->id, 'reason' => $data['reason'],
                'created_at' => now(), 'updated_at' => now()]);
            $loanType->forceFill(collect($data)->only($fields)->all())->save();
        });

        return back()->with('success', $loanType->name . ' updated. Running loans keep the terms their cycles were created with; new cycles use the new ones.');
    }
}
