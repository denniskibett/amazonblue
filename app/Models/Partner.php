<?php
// app/Models/Partner.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'company_name',
        'registration_number', 'type', 'status', 'total_contribution',
        'total_withdrawn', 'current_balance', 'profit_share_rate', 'notes'
    ];

    protected $casts = [
        'total_contribution' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'profit_share_rate' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(PartnerTransaction::class);
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