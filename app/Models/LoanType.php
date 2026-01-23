<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'period', 'unit', 'interest_rate', 'description'];


    public function disbursements()
    {
        return $this->hasMany(Disbursement::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

}
