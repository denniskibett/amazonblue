<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'method_type_id',
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'swift_code',
        'mobile_network',
        'mobile_number',
        'crypto_currency',
        'wallet_address',
        'wallet_provider',
        'is_primary',
        'is_verified',
        'verification_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verification_date' => 'date',
    ];

    protected $appends = [
        'method_type_name',
        'formatted_status',
        'display_name',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function methodType()
    {
        return $this->belongsTo(PaymentMethodType::class);
    }

    // ============ ACCESSORS ============

    public function getMethodTypeNameAttribute()
    {
        return $this->methodType ? $this->methodType->name : null;
    }

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending_verification' => 'Pending Verification',
            'suspended' => 'Suspended',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getDisplayNameAttribute()
    {
        if ($this->methodType && $this->methodType->slug === 'bank_account') {
            return $this->bank_name . ' - ' . $this->account_number;
        }
        if ($this->methodType && $this->methodType->slug === 'mobile_money') {
            return $this->mobile_network . ' - ' . $this->mobile_number;
        }
        if ($this->methodType && $this->methodType->slug === 'crypto_wallet') {
            return $this->crypto_currency . ' - ' . substr($this->wallet_address, 0, 10) . '...';
        }
        return $this->account_name;
    }

    // ============ SCOPES ============

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}