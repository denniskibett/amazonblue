<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Communication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'communication_type_id',
        'communication_status_id',
        'direction',
        'recipient',
        'recipient_phone',
        'recipient_email',
        'subject',
        'message',
        'provider_response',
        'delivery_attempts',
        'sent_at',
        'read_at',
        'notes',
        'created_by',
        'recovery_action_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'delivery_attempts' => 'integer',
    ];

    protected $appends = [
        'communication_type_name',
        'communication_status_name',
        'formatted_direction',
        'status_color',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function communicationType()
    {
        return $this->belongsTo(CommunicationType::class);
    }

    public function communicationStatus()
    {
        return $this->belongsTo(CommunicationStatus::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recoveryAction()
    {
        return $this->belongsTo(RecoveryAction::class, 'recovery_action_id');
    }

    // ============ ACCESSORS ============

    public function getCommunicationTypeNameAttribute()
    {
        return $this->communicationType ? $this->communicationType->name : null;
    }

    public function getCommunicationStatusNameAttribute()
    {
        return $this->communicationStatus ? $this->communicationStatus->name : null;
    }

    public function getFormattedDirectionAttribute()
    {
        return ucfirst($this->direction);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'sent' => 'blue',
            'delivered' => 'green',
            'failed' => 'red',
            'read' => 'green',
            'replied' => 'purple',
        ];
        return $colors[$this->communicationStatus->slug ?? 'sent'] ?? 'gray';
    }

    // ============ SCOPES ============

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeByType($query, $typeSlug)
    {
        return $query->whereHas('communicationType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }

    public function scopeSent($query)
    {
        return $query->whereHas('communicationStatus', function ($q) {
            $q->whereIn('slug', ['sent', 'delivered', 'read', 'replied']);
        });
    }

    public function scopeFailed($query)
    {
        return $query->whereHas('communicationStatus', function ($q) {
            $q->where('slug', 'failed');
        });
    }
}