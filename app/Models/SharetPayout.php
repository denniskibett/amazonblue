<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Money sent out through Sharet (database/sql/2026_11_09_sharet_pay.sql): a loan disbursement or an approved partner
 * payout. awaiting_approval -> queued -> paying -> paid | failed, or cancelled. Only payments:sweep sends.
 */
class SharetPayout extends Model
{
    public const OPEN = ['awaiting_approval', 'queued', 'paying'];

    protected $fillable = ['kind', 'loan_id', 'partner_request_id', 'partner_id', 'amount', 'destination', 'status', 'attempt', 'slot',
        'idempotency_key', 'request_body', 'environment', 'sharet_payment_id', 'sharet_transaction_id', 'disbursement_id',
        'requested_by', 'approved_by', 'paid_at', 'failure'];

    protected $casts = ['amount' => 'decimal:2', 'destination' => 'array', 'request_body' => 'array', 'paid_at' => 'datetime'];

    public function loan()
    {
        return $this->belongsTo(Loan::class)->withTrashed();
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    /** "M-Pesa 2547•••678" for screens. */
    public function destinationLabel(): string
    {
        $to = $this->destination ?? [];

        return match ($to['type'] ?? null) {
            'mpesa' => 'M-Pesa ' . substr((string) ($to['phone'] ?? ''), 0, 4) . '•••' . substr((string) ($to['phone'] ?? ''), -3),
            default => ucfirst((string) ($to['type'] ?? '?')),
        };
    }
}
