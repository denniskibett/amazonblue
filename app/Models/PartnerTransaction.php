<?php
// app/Models/PartnerTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id', 'type', 'amount', 'balance_after',
        'reference', 'notes', 'transaction_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function getFormattedTypeAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }
}