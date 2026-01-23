<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = ['bank_name', 'name', 'number', 'branch', 'accountable_id', 'accountable_type'];

    public function accountable()
    {
        return $this->morphTo();
    }
}
