<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'audience', 'period', 'unit', 'interest_rate', 'description'];

    // who a product is for (owner, 24 Oct 2026): individual borrowers or verified businesses
    public const AUDIENCES = ['individual' => 'Individuals', 'business' => 'Businesses'];


    public function disbursements()
    {
        return $this->hasMany(Disbursement::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

}
