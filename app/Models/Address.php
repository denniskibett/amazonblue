<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'address_type_id',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_primary',
        'from_date',
        'to_date',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    protected $appends = [
        'full_address',
        'address_type_name',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addressType()
    {
        return $this->belongsTo(AddressType::class);
    }

    // ============ ACCESSORS ============

    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        return implode(', ', $parts);
    }

    public function getAddressTypeNameAttribute()
    {
        return $this->addressType ? $this->addressType->name : null;
    }

    // ============ SCOPES ============

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeCurrent($query)
    {
        return $query->whereHas('addressType', function ($q) {
            $q->where('slug', 'current');
        });
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('to_date')
              ->orWhere('to_date', '>=', now());
        });
    }
}