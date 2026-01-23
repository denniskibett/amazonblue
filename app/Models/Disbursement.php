<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Disbursement extends Model
{
    use HasFactory;

    protected $fillable = ['loan_id', 'amount', 'transaction', 'mode', 'disburse_date', 'payment_date'];

    protected $dates = [
        'disburse_date', 
        'payment_date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
