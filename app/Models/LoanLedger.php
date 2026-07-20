<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'entry_type',
        'amount',
        'balance_after',
        'reference_id',
        'reference_type',
        'description',
        'entry_date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'entry_date' => 'date',
    ];

    protected $appends = [
        'formatted_entry_type',
        'formatted_amount',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedEntryTypeAttribute()
    {
        $types = [
            'debit' => 'Debit',
            'credit' => 'Credit',
            'interest' => 'Interest',
            'penalty' => 'Penalty',
            'fee' => 'Fee',
            'adjustment' => 'Adjustment',
        ];
        return $types[$this->entry_type] ?? ucfirst($this->entry_type);
    }

    public function getFormattedAmountAttribute()
    {
        return ($this->entry_type === 'credit' ? '' : '-') . 'KES ' . number_format($this->amount, 2);
    }

    public function scopeByLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId)->orderBy('entry_date');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('entry_type', $type);
    }
}