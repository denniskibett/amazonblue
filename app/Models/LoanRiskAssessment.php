<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanRiskAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'loan_id',
        'character_score',
        'capacity_score',
        'capital_score',
        'conditions_score',
        'overall_score',
        'risk_category_id',
        'assessed_by',
        'assessment_notes',
        'recommendation'
    ];

    protected $casts = [
        'character_score' => 'integer',
        'capacity_score' => 'integer',
        'capital_score' => 'integer',
        'conditions_score' => 'integer',
        'overall_score' => 'integer',
    ];

    protected $appends = [
        'risk_level',
        'risk_category_name',
        'formatted_scores',
        'is_pass',
    ];

    // ============ RELATIONSHIPS ============

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function riskCategory()
    {
        return $this->belongsTo(RiskCategory::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    // ============ ACCESSORS ============

    public function getRiskLevelAttribute()
    {
        $score = $this->overall_score ?? $this->calculateOverallScore();
        
        if ($score >= 80) return 'Low';
        if ($score >= 65) return 'Medium';
        if ($score >= 50) return 'High';
        return 'Very High';
    }

    public function getRiskCategoryNameAttribute()
    {
        if ($this->riskCategory) {
            return $this->riskCategory->name;
        }
        
        // Fallback to calculated category
        $score = $this->overall_score ?? $this->calculateOverallScore();
        
        if ($score >= 80) return 'Low Risk';
        if ($score >= 65) return 'Medium Risk';
        if ($score >= 50) return 'High Risk';
        return 'Very High Risk';
    }

    public function getFormattedScoresAttribute()
    {
        return [
            'character' => $this->character_score ?? 0,
            'capacity' => $this->capacity_score ?? 0,
            'capital' => $this->capital_score ?? 0,
            'conditions' => $this->conditions_score ?? 0,
            'overall' => $this->overall_score ?? $this->calculateOverallScore(),
        ];
    }

    public function getIsPassAttribute()
    {
        $score = $this->overall_score ?? $this->calculateOverallScore();
        return $score >= 65; // Medium risk or better is considered a pass
    }

    // ============ HELPER METHODS ============

    public function calculateOverallScore()
    {
        $scores = [
            $this->character_score ?? 0,
            $this->capacity_score ?? 0, 
            $this->capital_score ?? 0,
            $this->conditions_score ?? 0
        ];
        
        $validScores = array_filter($scores);
        
        if (empty($validScores)) {
            return 0;
        }
        
        return round(array_sum($validScores) / count($validScores));
    }

    public function getRiskCategoryFromScore($score = null)
    {
        $score = $score ?? $this->overall_score ?? $this->calculateOverallScore();
        
        // Try to find matching risk category from lookup table
        $category = RiskCategory::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
            
        if ($category) {
            return $category;
        }
        
        // Fallback to string
        if ($score >= 80) return 'Low Risk';
        if ($score >= 65) return 'Medium Risk';
        if ($score >= 50) return 'High Risk';
        return 'Very High Risk';
    }

    public function getRecommendation()
    {
        $score = $this->overall_score ?? $this->calculateOverallScore();
        
        if ($score >= 80) {
            return 'Low risk - Proceed with standard terms';
        } elseif ($score >= 65) {
            return 'Medium risk - Consider slightly higher interest rate or additional collateral';
        } elseif ($score >= 50) {
            return 'High risk - Require significant collateral and higher interest rate';
        } else {
            return 'Very high risk - Consider rejecting or requiring full collateralization';
        }
    }

    public function getAssessmentSummary()
    {
        return [
            'loan_id' => $this->loan_id,
            'overall_score' => $this->overall_score ?? $this->calculateOverallScore(),
            'risk_category' => $this->risk_category_name,
            'risk_level' => $this->risk_level,
            'scores' => $this->formatted_scores,
            'recommendation' => $this->recommendation ?? $this->getRecommendation(),
            'assessed_by' => $this->assessor?->name,
            'assessed_at' => $this->created_at?->format('Y-m-d H:i'),
            'is_pass' => $this->is_pass,
        ];
    }

    // ============ SCOPES ============

    public function scopePassing($query)
    {
        return $query->where('overall_score', '>=', 65);
    }

    public function scopeFailing($query)
    {
        return $query->where('overall_score', '<', 65);
    }

    public function scopeByRiskCategory($query, $categorySlug)
    {
        return $query->whereHas('riskCategory', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }
}