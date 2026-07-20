<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryAgency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_name',
        'license_number',
        'commission_rate',
        'status',
        'notes',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_status',
    ];

    public function contacts()
    {
        return $this->hasMany(AgencyContact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(AgencyContact::class)->where('is_primary', true);
    }

    public function caseAssignments()
    {
        return $this->hasMany(AgencyCaseAssignment::class);
    }

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}