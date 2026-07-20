<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'contact_type_id',
        'name',
        'phone',
        'phone_2',
        'email',
        'relationship_specific',
        'is_primary_contact',
        'priority',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'priority' => 'integer',
    ];

    protected $appends = [
        'contact_type_name',
        'formatted_priority',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recoveryActions()
    {
        return $this->hasMany(RecoveryAction::class, 'contact_id');
    }

    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    // ============ ACCESSORS ============

    public function getContactTypeNameAttribute()
    {
        return $this->contactType ? $this->contactType->name : null;
    }

    public function getFormattedPriorityAttribute()
    {
        $levels = [
            10 => 'Critical',
            9 => 'Very High',
            8 => 'High',
            7 => 'Above Average',
            6 => 'Average',
            5 => 'Moderate',
            4 => 'Below Average',
            3 => 'Low',
            2 => 'Very Low',
            1 => 'Minimal',
        ];
        return $levels[$this->priority] ?? $this->priority;
    }

    // ============ SCOPES ============

    public function scopePrimary($query)
    {
        return $query->where('is_primary_contact', true);
    }

    public function scopeByType($query, $typeSlug)
    {
        return $query->whereHas('contactType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }

    public function scopePriority($query, $minPriority = 5)
    {
        return $query->where('priority', '>=', $minPriority);
    }
}