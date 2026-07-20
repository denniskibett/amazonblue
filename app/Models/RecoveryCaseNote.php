<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryCaseNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'note_type',
        'note',
        'created_by',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    protected $appends = [
        'formatted_note_type',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ ACCESSORS ============

    public function getFormattedNoteTypeAttribute()
    {
        $types = [
            'general' => 'General',
            'action' => 'Action',
            'reminder' => 'Reminder',
            'alert' => 'Alert',
            'legal' => 'Legal',
        ];
        return $types[$this->note_type] ?? ucfirst($this->note_type);
    }

    // ============ SCOPES ============

    public function scopeGeneral($query)
    {
        return $query->where('note_type', 'general');
    }

    public function scopeAlerts($query)
    {
        return $query->where('note_type', 'alert');
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }
}