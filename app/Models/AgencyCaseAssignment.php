<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Minimal model for the existing `agency_case_assignments` table (recovery schema, migrations 000038-000060).
 * No screens use it yet; it exists so DebtRecoveryCase::agencyAssignments() resolves.
 */
class AgencyCaseAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'agency_case_assignments';

    protected $guarded = ['id'];

    public function recoveryCase()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }
}
