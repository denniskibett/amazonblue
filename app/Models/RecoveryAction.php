<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'contact_id',
        'action_type_id',
        'action_date',
        'performed_by',
        // Snapshot fields (intentional denormalization)
        'contact_person',
        'contact_relationship',
        'contact_phone',
        'contact_email',
        'outcome',
        'promised_amount',
        'promised_date',
        'amount_collected',
        'notes',
        'follow_up_date',
        'follow_up_notes',
        'attachment_path',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'promised_date' => 'date',
        'follow_up_date' => 'date',
        'promised_amount' => 'decimal:2',
        'amount_collected' => 'decimal:2',
    ];

    protected $appends = [
        'action_type_name',
        'formatted_outcome',
        'formatted_date',
        'outcome_color',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function actionType()
    {
        return $this->belongsTo(ActionType::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function communication()
    {
        return $this->hasOne(Communication::class, 'recovery_action_id');
    }

    // ============ ACCESSORS ============

    public function getActionTypeNameAttribute()
    {
        return $this->actionType ? $this->actionType->name : null;
    }

    public function getFormattedOutcomeAttribute()
    {
        $outcomes = [
            'successful' => 'Successful',
            'partial' => 'Partial',
            'failed' => 'Failed',
            'promise_to_pay' => 'Promise to Pay',
            'no_answer' => 'No Answer',
            'wrong_number' => 'Wrong Number',
            'refused' => 'Refused',
            'pending' => 'Pending',
        ];
        return $outcomes[$this->outcome] ?? ucfirst($this->outcome);
    }

    public function getOutcomeColorAttribute()
    {
        $colors = [
            'successful' => 'green',
            'partial' => 'yellow',
            'failed' => 'red',
            'promise_to_pay' => 'blue',
            'no_answer' => 'gray',
            'wrong_number' => 'gray',
            'refused' => 'red',
            'pending' => 'yellow',
        ];
        return $colors[$this->outcome] ?? 'gray';
    }

    public function getFormattedDateAttribute()
    {
        return $this->action_date ? $this->action_date->format('d/m/Y H:i') : null;
    }

    // ============ SCOPES ============

    public function scopeSuccessful($query)
    {
        return $query->where('outcome', 'successful');
    }

    public function scopeWithFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date');
    }

    public function scopePendingFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date')
            ->where('follow_up_date', '>=', now());
    }

    public function scopeByType($query, $typeSlug)
    {
        return $query->whereHas('actionType', function ($q) use ($typeSlug) {
            $q->where('slug', $typeSlug);
        });
    }

    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }
}