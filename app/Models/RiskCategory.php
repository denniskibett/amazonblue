<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_score',
        'max_score',
        'color_code',
        'description',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
    ];

    // ============ RELATIONSHIPS ============

    public function riskAssessments()
    {
        return $this->hasMany(LoanRiskAssessment::class);
    }

    // ============ SCOPES ============

    public function scopeByScore($query, $score)
    {
        return $query->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }
}