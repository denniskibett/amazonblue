<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'character_score',
        'capacity_score',
        'capital_score',
        'conditions_score',
        'overall_score',
        'risk_category',
        'assessed_by',
        'assessment_notes',
        'recommendation'
    ];

    protected $casts = [
        'character_score' => 'integer',
        'capacity_score' => 'integer',
        'capital_score' => 'integer',
        'conditions_score' => 'integer',
        'overall_score' => 'integer'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function calculateOverallScore()
    {
        $scores = [
            $this->character_score ?? 0,
            $this->capacity_score ?? 0, 
            $this->capital_score ?? 0,
            $this->conditions_score ?? 0
        ];
        
        $validScores = array_filter($scores);
        
        if (empty($validScores)) return 0;
        
        return round(array_sum($validScores) / count($validScores));
    }

    public function getRiskCategory()
    {
        $score = $this->overall_score ?? $this->calculateOverallScore();
        
        if ($score >= 80) return 'Low Risk';
        if ($score >= 65) return 'Medium Risk';
        if ($score >= 50) return 'High Risk';
        return 'Very High Risk';
    }
}