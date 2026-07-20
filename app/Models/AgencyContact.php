<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'contact_person',
        'title',
        'phone',
        'phone_2',
        'email',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function agency()
    {
        return $this->belongsTo(RecoveryAgency::class);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}