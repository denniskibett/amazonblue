<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = ['loan_id', 'amount', 'transaction', 'repayment_date'];

    protected $casts = [
        'repayment_date' => 'datetime', // Cast repayment_date to a Carbon instance
    ];


    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
