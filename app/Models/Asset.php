<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'asset_type_id',
        'asset_name',
        'description',
        'registration_number',
        'estimated_value',
        'lien_holder',
        'lien_amount',
        'is_collateral',
        'collateral_for_loan_id',
        'valuation_date',
        'valuation_by',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'lien_amount' => 'decimal:2',
        'is_collateral' => 'boolean',
        'valuation_date' => 'date',
    ];

    protected $appends = [
        'asset_type_name',
        'formatted_status',
        'formatted_value',
        'equity',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'collateral_for_loan_id');
    }

    // ============ ACCESSORS ============

    public function getAssetTypeNameAttribute()
    {
        return $this->assetType ? $this->assetType->name : null;
    }

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'owned' => 'Owned',
            'financed' => 'Financed',
            'leased' => 'Leased',
            'repossessed' => 'Repossessed',
            'sold' => 'Sold',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getFormattedValueAttribute()
    {
        return $this->estimated_value ? 'KES ' . number_format($this->estimated_value, 2) : null;
    }

    public function getEquityAttribute()
    {
        if (!$this->estimated_value || !$this->lien_amount) {
            return $this->estimated_value;
        }
        return $this->estimated_value - $this->lien_amount;
    }

    // ============ SCOPES ============

    public function scopeCollateral($query)
    {
        return $query->where('is_collateral', true);
    }

    public function scopeByType($query, $typeSlug)
    {
        return $query->whereHas('assetType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}