<?php
// app/Models/Disbursement.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CustomerWallets;

class Disbursement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'loan_cycle_id',
        'amount',
        'processing_fee',
        'net_amount',
        'transaction',
        'bank_ref',      // bank Transaction Ref ID (owner, 23 Oct 2026: bank ↔ M-Pesa keeps both references)
        'mpesa_ref',     // M-Pesa code
        'mode',
        'disburse_date',
        'payment_date',
        'partner_transaction_id',
        'funding_source',
        'investment_id',
        'notes',
        'kind',          // cash | rollover | discount | bad_debt | non_cash (CustomerWallets::KINDS)
        'created_by',
    ];

    protected $casts = [
        'disburse_date' => 'datetime',
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2'
    ];

    private static ?bool $refColumns = null;

    /** bank_ref / mpesa_ref exist once database/sql/2026_10_23_bank_and_mpesa_refs.sql has run. */
    public static function hasRefColumns(): bool
    {
        return self::$refColumns ??= \Illuminate\Support\Facades\Schema::hasColumn((new self)->getTable(), 'mpesa_ref');
    }

    protected static function booted(): void
    {
        // kind is stored (Stage 4 Phase 1). A form that sends no kind gets the old reading of the reference,
        // and editing only the reference re-reads it, so screens behave as before until every form picks one.
        static::saving(function (self $row) {
            if (! in_array($row->kind, CustomerWallets::KINDS, true) || ($row->isDirty('transaction') && ! $row->isDirty('kind') && $row->exists)) {
                $row->kind = CustomerWallets::kind($row->transaction);
            }
            // both references (owner, 23 Oct 2026): filled from the mode and the reference's shape when not given
            if (self::hasRefColumns() && ! $row->bank_ref && ! $row->mpesa_ref && $row->kind === 'cash') {
                [, $row->bank_ref, $row->mpesa_ref] = \App\Support\PaymentModes::refs('out', $row->mode, $row->transaction, null, null);
            }
        });
        static::creating(function (self $row) {
            $row->created_by ??= auth()->id();
        });
    }

    // ============ RELATIONSHIPS ============

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function loanCycle()
    {
        return $this->belongsTo(LoanCycle::class, 'loan_cycle_id');
    }

    public function partnerTransaction()
    {
        return $this->belongsTo(PartnerTransaction::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    // ============ ACCESSORS ============

    public function getFundingSourceLabelAttribute()
    {
        $sources = [
            'internal' => 'Internal Funds',
            'partner' => 'Partner Funds',
            'mixed' => 'Mixed Funds'
        ];
        return $sources[$this->funding_source] ?? ucfirst($this->funding_source);
    }

    public function getPartnerNameAttribute()
    {
        return $this->partnerTransaction?->partner?->name ?? 'N/A';
    }

    public function getIsInvestmentAttribute()
    {
        return !is_null($this->investment_id);
    }

    public function getInvestmentNameAttribute()
    {
        return $this->investment?->name ?? 'N/A';
    }

    // NEW HELPERS

    public function getIsLoanDisbursementAttribute(): bool
    {
        return !is_null($this->loan_id);
    }

    public function getIsInvestmentOnlyDisbursementAttribute(): bool
    {
        return !is_null($this->investment_id) && is_null($this->loan_id);
    }

    public function getFundingPartnerAttribute()
    {
        return $this->partnerTransaction?->partner;
    }

    /**
     * Comma-separated list of partner names funding the linked investment.
     */
    public function getInvestmentPartnerNamesAttribute(): string
    {
        if (!$this->investment) {
            return 'N/A';
        }

        $partners = $this->investment->fundingPartnerModels();

        return $partners->isNotEmpty()
            ? $partners->pluck('name')->implode(', ')
            : 'Internal';
    }
}