<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecoveryPriority extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'priority_level',
        'color_code',
    ];

    protected $casts = [
        'priority_level' => 'integer',
    ];

    // ============ RELATIONSHIPS ============

    public function recoveryCases()
    {
        return $this->hasMany(DebtRecoveryCase::class, 'priority_id');
    }

    public function tasks()
    {
        return $this->hasMany(RecoveryTask::class, 'priority_id');
    }

    // ============ SCOPES ============

    public function scopeByLevel($query, $level)
    {
        return $query->where('priority_level', '>=', $level);
    }
}