<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Borrower extends Model
{
    use HasFactory;

    // today's 4Cs, worked out once per request (see calculate4csScore)
    protected ?array $fourCsCache = null;

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

    
    /**
     * Today's 4Cs from our repayment history (App\Services\CreditAssessment), memoised per request.
     */
    public function calculate4csScore()
    {
        if (! isset($this->fourCsCache)) {
            $current = $this->user ? app(\App\Services\CreditAssessment::class)->current($this->user) : null;
            $this->fourCsCache = $current
                ? $current['scores'] + ['overall' => $current['overall'], 'weights' => \App\Services\CreditAssessment::WEIGHTS]
                : ['character' => 0, 'capacity' => 0, 'capital' => 0, 'conditions' => 0, 'overall' => 0, 'weights' => \App\Services\CreditAssessment::WEIGHTS];
        }

        return $this->fourCsCache;
    }
}