<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Borrower extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'client_type', 
        'status', 
        'income_type',
        'gross_salary',
        'net_salary', 
        'job_title',
        'workplace',
        'employer_name',
        'employer_email',
        'employer_title',
        'department',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'client_type' => 'string',
        'status' => 'integer',
    ];

    protected $appends = [
        'formatted_client_type',
        'formatted_status',
        'formatted_income_type',
        'formatted_gross_salary',
        'formatted_net_salary',
        'employment_info',
        'salary_info',
        'risk_category',
        'monthly_debt_capacity',
        'recommended_loan_limit',
        'debt_to_income_ratio',
        'is_over_leveraged',
        'four_cs_score',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'accountable');
    }


    public function primaryBankAccount()
    {
        // Remove the where clause since 'is_primary' column doesn't exist
        // or add the column to your database if you want to use it
        return $this->morphOne(BankAccount::class, 'accountable');
        
        // If you want to add 'is_primary' column, run this migration:
        // Schema::table('bank_accounts', function (Blueprint $table) {
        //     $table->boolean('is_primary')->default(false)->after('account_number');
        // });
    }

    public function loans() 
    {
        return $this->hasMany(Loan::class, 'user_id', 'user_id');
    }

    public function activeLoans()
    {
        return $this->loans()->where('status', 'active');
    }

    public function completedLoans()
    {
        return $this->loans()->where('status', 'completed');
    }

    public function overdueLoans()
    {
        return $this->loans()->where('status', 'overdue');
    }

    public function guarantorFor()
    {
        return $this->hasMany(Loan::class, 'guarantor_id');
    }

    // ============ ATTRIBUTE ACCESSORS ============

    public function getFormattedClientTypeAttribute()
    {
        return $this->client_type == 0 ? 'Our Client' : 'Broker Client';
    }

    public function getFormattedStatusAttribute()
    {
        return $this->status == 1 ? 'Active' : 'Inactive';
    }

    public function getFormattedIncomeTypeAttribute()
    {
        if (!$this->income_type) return null;
        
        $types = [
            'employed' => 'Employed',
            'self_employed' => 'Self Employed',
            'business' => 'Business Owner',
            'casual' => 'Casual Worker',
            'student' => 'Student',
            'unemployed' => 'Unemployed',
            'contract' => 'Contract Worker',
            'freelance' => 'Freelancer'
        ];
        
        return $types[$this->income_type] ?? ucfirst(str_replace('_', ' ', $this->income_type));
    }

    public function getFormattedGrossSalaryAttribute()
    {
        return $this->gross_salary ? 'KES ' . number_format($this->gross_salary, 2) : null;
    }

    public function getFormattedNetSalaryAttribute()
    {
        return $this->net_salary ? 'KES ' . number_format($this->net_salary, 2) : null;
    }

    public function getEmploymentInfoAttribute()
    {
        $info = [];
        
        if ($this->job_title) {
            $info[] = $this->job_title;
        }
        
        if ($this->workplace) {
            $info[] = $this->workplace;
        }
        
        if ($this->employer_name) {
            $info[] = $this->employer_name;
        }
        
        if ($this->department) {
            $info[] = $this->department;
        }
        
        return implode(' • ', $info);
    }

    public function getSalaryInfoAttribute()
    {
        $info = [];
        
        if ($this->income_type) {
            $info[] = $this->getFormattedIncomeTypeAttribute();
        }
        
        if ($this->net_salary) {
            $info[] = 'Net: ' . $this->getFormattedNetSalaryAttribute();
        }
        
        if ($this->gross_salary) {
            $info[] = 'Gross: ' . $this->getFormattedGrossSalaryAttribute();
        }
        
        return implode(' | ', $info);
    }

    public function getRiskCategoryAttribute()
    {
        $scores = $this->calculate4csScore();
        $overall = $scores['overall'];
        
        if ($overall >= 80) return 'Low Risk';
        if ($overall >= 65) return 'Medium Risk';
        if ($overall >= 50) return 'High Risk';
        return 'Very High Risk';
    }

    public function getMonthlyDebtCapacityAttribute()
    {
        if (!$this->net_salary) return 0;
        
        // Assume maximum 40% of net salary can go to debt repayment
        return $this->net_salary * 0.4;
    }

    public function getRecommendedLoanLimitAttribute()
    {
        $monthlyCapacity = $this->getMonthlyDebtCapacityAttribute();
        
        // Assume 12-month loan term for calculation
        return $monthlyCapacity * 12;
    }

    public function getDebtToIncomeRatioAttribute()
    {
        return $this->getDebtToIncomeRatio();
    }

    public function getIsOverLeveragedAttribute()
    {
        return $this->isOverLeveraged();
    }

    public function getFourCsScoreAttribute()
    {
        return $this->calculate4csScore();
    }

    // ============ HELPER METHODS ============

    public function getDebtToIncomeRatio()
    {
        if (!$this->net_salary || $this->net_salary <= 0) {
            return 0;
        }
        
        $totalMonthlyLoanPayments = $this->loans()
            ->whereIn('status', ['active', 'processing'])
            ->sum('amount');
        
        if ($totalMonthlyLoanPayments <= 0) {
            return 0;
        }
        
        return ($totalMonthlyLoanPayments / $this->net_salary) * 100;
    }

    public function isOverLeveraged()
    {
        return $this->getDebtToIncomeRatio() > 40;
    }


    public function getCreditScore()
    {
        $fourCs = $this->calculate4csScore();
        $overall = $fourCs['overall'];
        
        // Convert to credit score range (300-850)
        $creditScore = 300 + ($overall / 100) * 550;
        
        return round($creditScore);
    }

    public function getCreditRating()
    {
        $creditScore = $this->getCreditScore();
        
        if ($creditScore >= 750) return 'Excellent';
        if ($creditScore >= 700) return 'Good';
        if ($creditScore >= 650) return 'Fair';
        if ($creditScore >= 600) return 'Poor';
        return 'Very Poor';
    }

    public function getMaxLoanAmount()
    {
        $creditScore = $this->getCreditScore();
        $netSalary = $this->net_salary ?: 0;
        
        // Base on credit score and salary
        if ($creditScore >= 750) {
            return $netSalary * 12; // 12 months salary
        } elseif ($creditScore >= 700) {
            return $netSalary * 9; // 9 months salary
        } elseif ($creditScore >= 650) {
            return $netSalary * 6; // 6 months salary
        } elseif ($creditScore >= 600) {
            return $netSalary * 3; // 3 months salary
        } else {
            return $netSalary * 2; // 2 months salary
        }
    }

    public function getSuggestedInterestRate()
    {
        $riskCategory = $this->getRiskCategoryAttribute();
        
        $rates = [
            'Low Risk' => 8.5,
            'Medium Risk' => 12.5,
            'High Risk' => 18.0,
            'Very High Risk' => 24.0
        ];
        
        return $rates[$riskCategory] ?? 15.0;
    }

    // ============ STATISTICS METHODS ============

    public function getLoanStatistics()
    {
        return [
            'total_loans' => $this->loans()->count(),
            'active_loans' => $this->activeLoans()->count(),
            'completed_loans' => $this->completedLoans()->count(),
            'overdue_loans' => $this->overdueLoans()->count(),
            'total_borrowed' => $this->loans()->sum('amount'),
            'total_repaid' => $this->loans()->sum('amount_paid'),
            'outstanding_balance' => $this->loans()->sum('remaining_balance'),
            'average_loan_amount' => $this->loans()->avg('amount'),
            'average_loan_term' => $this->loans()->avg('term_months'),
        ];
    }

    public function getRepaymentHistory()
    {
        return $this->loans()->with('repayments')->get()->map(function ($loan) {
            return [
                'loan_id' => $loan->id,
                'loan_amount' => $loan->amount,
                'repayments' => $loan->repayments->map(function ($repayment) {
                    return [
                        'date' => $repayment->payment_date,
                        'amount' => $repayment->amount,
                        'status' => $repayment->status
                    ];
                })
            ];
        });
    }

    // ============ QUERY SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeOurClients($query)
    {
        return $query->where('client_type', 0);
    }

    public function scopeBrokerClients($query)
    {
        return $query->where('client_type', 1);
    }

    public function scopeByIncomeType($query, $incomeType)
    {
        return $query->where('income_type', $incomeType);
    }

    public function scopeWithActiveLoans($query)
    {
        return $query->whereHas('loans', function ($q) {
            $q->where('status', 'active');
        });
    }

    public function scopeWithoutActiveLoans($query)
    {
        return $query->whereDoesntHave('loans', function ($q) {
            $q->where('status', 'active');
        });
    }

    
    public function calculate4csScore()
    {
        // Character - based on loan history and repayment behavior
        $characterScore = $this->calculateCharacterScore();
        
        // Capacity - based on income, expenses, and debt service ratio
        $capacityScore = $this->calculateCapacityScore();
        
        // Capital - based on assets, savings, and net worth
        $capitalScore = $this->calculateCapitalScore();
        
        // Conditions - based on employment stability and economic factors
        $conditionsScore = $this->calculateConditionsScore();
        
        // Apply weighting to each factor
        $weights = [
            'character' => 0.35,  // Most important - payment history
            'capacity' => 0.30,   // Income and debt capacity
            'capital' => 0.20,    // Assets and savings
            'conditions' => 0.15  // Employment stability
        ];
        
        $weightedScore = (
            $characterScore * $weights['character'] +
            $capacityScore * $weights['capacity'] +
            $capitalScore * $weights['capital'] +
            $conditionsScore * $weights['conditions']
        );
        
        return [
            'character' => round($characterScore),
            'capacity' => round($capacityScore),
            'capital' => round($capitalScore),
            'conditions' => round($conditionsScore),
            'overall' => round($weightedScore),
            'weights' => $weights
        ];
    }

    private function calculateCharacterScore()
    {
        $loans = $this->loans;
        $totalLoans = $loans->count();
        
        if ($totalLoans === 0) {
            // New borrower - base score with penalty for no history
            return 65;
        }
        
        // Calculate on-time payment rate
        $totalPayments = 0;
        $ontimePayments = 0;
        
        foreach ($loans as $loan) {
            $repayments = $loan->repayments;
            $totalPayments += $repayments->count();
            
            foreach ($repayments as $repayment) {
                if ($repayment->status === 'paid' && 
                    Carbon::parse($repayment->payment_date)->lte(Carbon::parse($repayment->due_date))) {
                    $ontimePayments++;
                }
            }
        }
        
        $ontimeRate = $totalPayments > 0 ? ($ontimePayments / $totalPayments) * 100 : 0;
        
        // Calculate default rate
        $defaultedLoans = $loans->where('status', 'defaulted')->count();
        $defaultRate = ($defaultedLoans / $totalLoans) * 100;
        
        // Base score from on-time rate
        $score = min(100, $ontimeRate * 0.8);
        
        // Penalize for defaults
        $score -= ($defaultRate * 2);
        
        // Bonus for completed loans
        $completedLoans = $loans->where('status', 'completed')->count();
        $completionRate = ($completedLoans / $totalLoans) * 100;
        $score += ($completionRate * 0.2);
        
        return max(20, min(100, $score));
    }

    private function calculateCapacityScore()
    {
        if (!$this->net_salary || $this->net_salary <= 0) {
            return 40; // No income information
        }
        
        $score = 50; // Base score
        
        // Income level scoring
        if ($this->net_salary >= 150000) $score += 30;
        elseif ($this->net_salary >= 100000) $score += 25;
        elseif ($this->net_salary >= 50000) $score += 20;
        elseif ($this->net_salary >= 30000) $score += 15;
        elseif ($this->net_salary >= 20000) $score += 10;
        elseif ($this->net_salary >= 10000) $score += 5;
        
        // Employment type scoring
        $employmentScores = [
            'employed' => 15,
            'business' => 12,
            'self_employed' => 10,
            'contract' => 8,
            'freelance' => 6,
            'casual' => 3,
            'student' => 2,
            'unemployed' => -10
        ];
        
        if (isset($employmentScores[$this->income_type])) {
            $score += $employmentScores[$this->income_type];
        }
        
        // Debt-to-income ratio penalty
        $dti = $this->getDebtToIncomeRatio();
        if ($dti > 50) $score -= 20;
        elseif ($dti > 40) $score -= 15;
        elseif ($dti > 30) $score -= 10;
        elseif ($dti > 20) $score -= 5;
        
        // Job stability bonus
        if ($this->job_title && $this->employer_name && $this->workplace) {
            $score += 10; // Has formal employment details
        }
        
        // Department/position indicates career progression
        if ($this->department) {
            $score += 5;
        }
        
        return max(20, min(100, $score));
    }

    private function calculateCapitalScore()
    {
        $score = 50; // Base score
        
        // Bank accounts - indicator of financial management
        $bankAccounts = $this->bankAccounts->count();
        if ($bankAccounts >= 3) $score += 15;
        elseif ($bankAccounts >= 2) $score += 10;
        elseif ($bankAccounts >= 1) $score += 5;
        
        // Education level
        if ($this->user->education) {
            $educationScores = [
                'phd' => 15,
                'masters' => 12,
                'bachelors' => 10,
                'diploma' => 7,
                'certificate' => 5,
                'college' => 6,
                'post_graduate' => 13,
                'high_school' => 3,
                'secondary' => 2,
                'primary' => 1
            ];
            
            $education = strtolower($this->user->education);
            foreach ($educationScores as $key => $value) {
                if (str_contains($education, $key)) {
                    $score += $value;
                    break;
                }
            }
        }
        
        // Marital status stability
        if ($this->user->marital_status === 'married') $score += 8;
        elseif ($this->user->marital_status === 'single') $score += 3;
        elseif (in_array($this->user->marital_status, ['divorced', 'separated'])) $score -= 5;
        
        // Age factor - middle age is most stable
        if ($this->user->age) {
            if ($this->user->age >= 30 && $this->user->age <= 50) $score += 10;
            elseif ($this->user->age >= 25 && $this->user->age <= 60) $score += 5;
            elseif ($this->user->age < 25) $score -= 5; // Young, less established
        }
        
        // Complete personal information indicates organization
        if ($this->user->hasCompletePersonalInfo()) {
            $score += 10;
        }
        
        // Has identification documents
        if ($this->user->id_front_path && $this->user->id_back_path) {
            $score += 8;
        } elseif ($this->user->id_front_path) {
            $score += 5;
        }
        
        return max(20, min(100, $score));
    }

    private function calculateConditionsScore()
    {
        $score = 70; // Base assuming average conditions
        
        // Employment type stability
        $stabilityScores = [
            'employed' => 20,    // Most stable
            'business' => 15,    // Business owner - depends on business success
            'contract' => 10,    // Contract work - less stable
            'self_employed' => 8, // Self-employed - variable income
            'freelance' => 5,    // Freelance - least stable
            'casual' => 0,       // Casual - very unstable
            'student' => -5,     // Student - no stable income
            'unemployed' => -20   // Unemployed - high risk
        ];
        
        if (isset($stabilityScores[$this->income_type])) {
            $score += $stabilityScores[$this->income_type];
        }
        
        // Formal employment indicators
        if ($this->employer_email && filter_var($this->employer_email, FILTER_VALIDATE_EMAIL)) {
            $score += 10; // Has company email
            
            // Check if email matches workplace domain
            if ($this->workplace) {
                $workplaceDomain = strtolower($this->workplace);
                $emailDomain = strtolower(explode('@', $this->employer_email)[1] ?? '');
                if (str_contains($emailDomain, $workplaceDomain) || 
                    str_contains($workplaceDomain, $emailDomain)) {
                    $score += 5; // Email matches company domain
                }
            }
        }
        
        // Job title indicates position level
        if ($this->job_title) {
            $seniorityKeywords = ['manager', 'director', 'head', 'chief', 'lead', 'senior', 'executive'];
            $jobTitleLower = strtolower($this->job_title);
            
            foreach ($seniorityKeywords as $keyword) {
                if (str_contains($jobTitleLower, $keyword)) {
                    $score += 8;
                    break;
                }
            }
        }
        
        // Industry stability (simplified - in real app, use industry classification)
        $stableIndustries = ['government', 'banking', 'education', 'healthcare', 'insurance'];
        $unstableIndustries = ['construction', 'retail', 'hospitality', 'entertainment'];
        
        if ($this->workplace) {
            $workplaceLower = strtolower($this->workplace);
            foreach ($stableIndustries as $industry) {
                if (str_contains($workplaceLower, $industry)) {
                    $score += 10;
                    break;
                }
            }
            foreach ($unstableIndustries as $industry) {
                if (str_contains($workplaceLower, $industry)) {
                    $score -= 5;
                    break;
                }
            }
        }
        
        // Department indicates specialization
        if ($this->department) {
            $score += 5;
        }
        
        // Salary consistency (check if salary is reasonable for job type)
        if ($this->net_salary && $this->job_title) {
            // Very basic check - in real app, use market data
            if ($this->net_salary > 50000 && 
                (str_contains(strtolower($this->job_title), 'junior') || 
                 str_contains(strtolower($this->job_title), 'assistant'))) {
                $score -= 5; // Possibly inflated salary
            }
        }
        
        return max(20, min(100, $score));
    }
}