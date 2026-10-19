<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Minimal model for the existing `financial_assessments` table (recovery schema, migrations 000038-000060).
 * No screens use it yet; it exists so DebtRecoveryCase::financialAssessments() resolves.
 */
class FinancialAssessment extends Model
{
    use SoftDeletes;

    protected $table = 'financial_assessments';

    protected $guarded = ['id'];

    public function recoveryCase()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }
}
