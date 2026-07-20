<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourtHearing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legal_proceeding_id',
        'hearing_date',
        'hearing_time',
        'court_room',
        'judge_name',
        'result',
        'next_hearing_date',
        'notes',
    ];

    protected $casts = [
        'hearing_date' => 'date',
        'next_hearing_date' => 'date',
        'hearing_time' => 'datetime',
    ];

    protected $appends = [
        'formatted_date',
        'formatted_time',
    ];

    // ============ RELATIONSHIPS ============

    public function legalProceeding()
    {
        return $this->belongsTo(LegalProceeding::class, 'legal_proceeding_id');
    }

    // ============ ACCESSORS ============

    public function getFormattedDateAttribute()
    {
        return $this->hearing_date ? $this->hearing_date->format('d/m/Y') : null;
    }

    public function getFormattedTimeAttribute()
    {
        return $this->hearing_time ? $this->hearing_time->format('H:i') : null;
    }

    // ============ SCOPES ============

    public function scopeUpcoming($query)
    {
        return $query->where('hearing_date', '>=', now());
    }

    public function scopePast($query)
    {
        return $query->where('hearing_date', '<', now());
    }
}