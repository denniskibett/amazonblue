<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Writes rows to audit_logs: who (user), what (table, record, action), old/new values (changed fields
 * only for updates) and where from (IP, user agent). Never throws: a failed audit write is logged and
 * the business action carries on.
 */
class AuditTrail
{
    /** Never stored in the audit trail. */
    public const SECRET_FIELDS = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
        'api_token', 'signature', 'token', 'secret',
    ];

    public static function record(string $table, $recordId, string $action, ?array $old = null, ?array $new = null): void
    {
        try {
            $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

            AuditLog::create([
                'table_name' => $table,
                'record_id' => (int) $recordId,
                'action' => $action,
                'old_values' => $old ? self::clean($old) : null,
                'new_values' => $new ? self::clean($new) : null,
                'user_id' => Auth::id(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 1000) : 'console',
            ]);
        } catch (\Throwable $e) {
            Log::error("Audit log write failed for {$table} #{$recordId} ({$action})", ['error' => $e->getMessage()]);
        }
    }

    /** Role names from what Spatie's RoleAttached/Detached events carry (ids, names, Role models or a collection). */
    public static function roleNames($rolesOrIds): array
    {
        try {
            $items = collect($rolesOrIds instanceof \Illuminate\Support\Collection ? $rolesOrIds : \Illuminate\Support\Arr::wrap($rolesOrIds));
            $ids = $items->map(fn ($r) => is_object($r) ? ($r->name ?? $r->id ?? null) : $r)->filter()->values();
            $numeric = $ids->filter(fn ($v) => is_numeric($v));
            $names = $numeric->isNotEmpty()
                ? \Spatie\Permission\Models\Role::whereIn('id', $numeric)->pluck('name')
                : collect();

            return $names->merge($ids->reject(fn ($v) => is_numeric($v)))->values()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function clean(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::SECRET_FIELDS, true)) {
                $values[$key] = '[hidden]';
            } elseif ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_string($value) && strlen($value) > 2000) {
                $values[$key] = mb_substr($value, 0, 2000) . '…';
            }
        }

        return $values;
    }
}
