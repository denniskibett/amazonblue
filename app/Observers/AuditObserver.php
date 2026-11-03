<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Model observer that writes the audit trail (audit_logs) for money and access records: loans,
 * repayments, disbursements, partners, partner transactions, investments, and users (roles, status
 * and KYC only). Registered in AppServiceProvider. Failures never block the action.
 */
class AuditObserver
{
    /** Columns never worth an audit row on their own. */
    private const NOISE = ['updated_at', 'created_at'];

    /** For users only these columns are audited (role, account status, KYC decisions). */
    private const USER_FIELDS = ['role', 'status', 'kyc_status', 'kyc_reviewed_by', 'kyc_reviewed_at', 'kyc_review_notes', 'kyc_submitted_at'];

    public function created(Model $model): void
    {
        $this->safely(function () use ($model) {
            $new = $this->filter($model, array_diff_key($model->getAttributes(), array_flip(self::NOISE)), true);
            AuditTrail::record($model->getTable(), $model->getKey(), 'create', null, $new);
        });
    }

    public function updated(Model $model): void
    {
        $this->safely(function () use ($model) {
            $changes = array_diff_key($model->getChanges(), array_flip(self::NOISE));
            // soft delete / restore are logged by their own events
            unset($changes['deleted_at']);
            $changes = $this->filter($model, $changes);
            $original = $model->getOriginal();
            // drop "changes" that are the same number written differently (5000 vs "5000.00")
            foreach ($changes as $key => $value) {
                $before = $original[$key] ?? null;
                if (is_numeric($value) && is_numeric($before) && (float) $value === (float) $before) {
                    unset($changes[$key]);
                }
            }
            if (! $changes) {
                return;
            }
            $old = array_intersect_key($original, $changes);
            AuditTrail::record($model->getTable(), $model->getKey(), 'update', $old, $changes);
        });
    }

    public function deleted(Model $model): void
    {
        $this->safely(function () use ($model) {
            $forced = method_exists($model, 'isForceDeleting') ? $model->isForceDeleting() : true;
            $old = $this->filter($model, $model->getOriginal(), true);
            AuditTrail::record($model->getTable(), $model->getKey(), $forced ? 'force_delete' : 'delete', $old, null);
        });
    }

    public function restored(Model $model): void
    {
        $this->safely(fn () => AuditTrail::record($model->getTable(), $model->getKey(), 'restore', null, ['deleted_at' => null]));
    }

    private function filter(Model $model, array $values, bool $wholeRecord = false): array
    {
        $values = array_diff_key($values, array_flip($model->getHidden()));
        if ($model instanceof User) {
            $keep = $wholeRecord ? array_merge(self::USER_FIELDS, ['name', 'email']) : self::USER_FIELDS;
            $values = array_intersect_key($values, array_flip($keep));
        }

        return $values;
    }

    private function safely(callable $write): void
    {
        try {
            $write();
        } catch (\Throwable $e) {
            Log::error('Audit observer failed', ['error' => $e->getMessage()]);
        }
    }
}
