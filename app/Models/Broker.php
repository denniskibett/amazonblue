<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Broker extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'interest_client', 'interest_broker', 'penalty_client', 'penalty_broker', 'cert_no'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'accountable');
    }

    public function borrowers() 
    {
        return $this->hasMany(Borrower::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
    
    
}
