<?php
// app/Models/Investment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'status', 'initial_amount', 'current_value',
        'expected_return', 'purchase_date', 'maturity_date', 'description'
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'current_value' => 'decimal:2',
        'expected_return' => 'decimal:2',
        'purchase_date' => 'date',
        'maturity_date' => 'date'
    ];

    public function transactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function getReturnPercentageAttribute()
    {
        if ($this->initial_amount <= 0) return 0;
        return (($this->current_value - $this->initial_amount) / $this->initial_amount) * 100;
    }

    public function getFormattedStatusAttribute()
    {
        return ucfirst($this->status);
    }

    public function getFormattedTypeAttribute()
    {
        return ucfirst($this->type);
    }
}