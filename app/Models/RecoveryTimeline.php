<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecoveryTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'event_type_id',
        'reference_id',
        'reference_type',
        'description',
        'occurred_at',
        'created_by',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    protected $appends = [
        'event_type_name',
        'formatted_date',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ ACCESSORS ============

    public function getEventTypeNameAttribute()
    {
        return $this->eventType ? $this->eventType->name : null;
    }

    public function getFormattedDateAttribute()
    {
        return $this->occurred_at ? $this->occurred_at->format('d/m/Y H:i') : null;
    }

    // ============ SCOPES ============

    public function scopeByEventType($query, $typeSlug)
    {
        return $query->whereHas('eventType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }
}