<?php
// app/Models/InvestmentTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_id', 'type', 'amount', 'quantity',
        'unit_price', 'transaction_date', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }
}