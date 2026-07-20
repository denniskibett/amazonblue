<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============

    public function timelines()
    {
        return $this->hasMany(RecoveryTimeline::class);
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}