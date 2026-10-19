<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Read-only audit trail (audit_logs) for admins: who changed what, when, old/new values, IP.
 * Route audit-log.index, permission "view audit log".
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name')->latest('id');

        if ($request->filled('table')) {
            $query->where('table_name', $request->input('table'));
        }
        if ($request->filled('record')) {
            $query->where('record_id', (int) $request->input('record'));
        }
        if ($request->filled('user')) {
            $query->where('user_id', (int) $request->input('user'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->paginate(25)->withQueryString();
        $tables = AuditLog::query()->distinct()->orderBy('table_name')->pluck('table_name');
        $users = User::whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')->get(['id', 'name']);

        return view('audit-log.index', compact('logs', 'tables', 'users'));
    }
}
