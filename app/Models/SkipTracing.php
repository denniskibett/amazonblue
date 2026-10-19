<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Minimal model for the existing `skip_tracing` table (recovery schema, migrations 000038-000060).
 * No screens use it yet; it exists so DebtRecoveryCase::skipTracingRecords() resolves.
 */
class SkipTracing extends Model
{
    use SoftDeletes;

    protected $table = 'skip_tracing';

    protected $guarded = ['id'];

    public function recoveryCase()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }
}
