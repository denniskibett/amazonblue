<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepaymentOverflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount',
        'applied_to_loan_id',
        'status',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function appliedLoan()
    {
        return $this->belongsTo(Loan::class, 'applied_to_loan_id');
    }
}
