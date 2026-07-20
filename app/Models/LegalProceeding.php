<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalProceeding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'proceeding_type_id',
        'filing_date',
        'court_name',
        'case_number',
        'judge_name',
        'plaintiff',
        'defendant',
        'amount_claimed',
        'amount_awarded',
        'judgment_date',
        'status',
        'next_hearing_date',
        'lawyer_name',
        'lawyer_contact',
        'lawyer_fees',
        'costs',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'filing_date' => 'date',
        'judgment_date' => 'date',
        'next_hearing_date' => 'date',
        'amount_claimed' => 'decimal:2',
        'amount_awarded' => 'decimal:2',
        'lawyer_fees' => 'decimal:2',
        'costs' => 'decimal:2',
    ];

    protected $appends = [
        'proceeding_type_name',
        'formatted_status',
        'total_cost',
        'net_recovery',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function proceedingType()
    {
        return $this->belongsTo(LegalProceedingType::class);
    }

    public function hearings()
    {
        return $this->hasMany(CourtHearing::class, 'legal_proceeding_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ ACCESSORS ============

    public function getProceedingTypeNameAttribute()
    {
        return $this->proceedingType ? $this->proceedingType->name : null;
    }

    public function getFormattedStatusAttribute()
    {
        $statuses = [
            'filed' => 'Filed',
            'pending' => 'Pending',
            'active' => 'Active',
            'resolved' => 'Resolved',
            'appealed' => 'Appealed',
            'dismissed' => 'Dismissed',
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getTotalCostAttribute()
    {
        return ($this->lawyer_fees ?? 0) + ($this->costs ?? 0);
    }

    public function getNetRecoveryAttribute()
    {
        if (!$this->amount_awarded) {
            return 0;
        }
        return $this->amount_awarded - $this->total_cost;
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['filed', 'pending', 'active']);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'appealed', 'dismissed']);
    }

    public function scopeHasHearing($query)
    {
        return $query->whereNotNull('next_hearing_date');
    }

    // ============ HELPERS ============

    public function getLastHearing()
    {
        return $this->hearings()->latest('hearing_date')->first();
    }

    public function getNextHearing()
    {
        return $this->hearings()
            ->where('hearing_date', '>=', now())
            ->orderBy('hearing_date')
            ->first();
    }
}