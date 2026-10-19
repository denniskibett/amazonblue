<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Minimal model for the existing `credit_bureau_reports` table (recovery schema, migrations 000038-000060).
 * No screens use it yet; it exists so DebtRecoveryCase::creditBureauReports() resolves.
 */
class CreditBureauReport extends Model
{
    use SoftDeletes;

    protected $table = 'credit_bureau_reports';

    protected $guarded = ['id'];

    public function recoveryCase()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }
}
