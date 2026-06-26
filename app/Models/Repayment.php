<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = ['loan_id', 'amount', 'transaction', 'repayment_date', 'mode'];

    protected $casts = [
        'repayment_date' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}