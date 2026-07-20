<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'requires_verification',
        'is_active',
    ];

    protected $casts = [
        'requires_verification' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ RELATIONSHIPS ============

    public function documents()
    {
        return $this->hasMany(RecoveryDocument::class);
    }
}