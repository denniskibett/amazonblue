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

    protected array|false|null $assessmentCache = false;

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
        // owner, 23 Oct 2026: pay schedule (borrower-entered) and when they completed their profile
        'pay_frequency',
        'pay_day',
        'profile_completed_at',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'client_type' => 'string',
        'status' => 'integer',
        'pay_day' => 'integer',
        'profile_completed_at' => 'datetime',
        'behaviour_score' => 'integer',
        'on_time_rate' => 'decimal:1',
        'comfort_amount' => 'decimal:2',
        'suggested_limit' => 'integer',
        'behaviour_flags' => 'array',
        'behaviour_at' => 'datetime',
    ];

    public const PAY_FREQUENCIES = ['monthly' => 'Monthly', 'fortnightly' => 'Every two weeks', 'weekly' => 'Weekly', 'daily' => 'Daily', 'irregular' => 'Irregular (business or casual)'];

    /** What a borrower still needs to tell us so their limit and reminders are right (owner, 23 Oct 2026). */
    public function missing(): array
    {
        return array_keys(array_filter([
            'income type' => empty($this->income_type),
            'net income' => ! ((float) $this->net_salary > 0),
            'employer or business' => empty($this->employer_name) && empty($this->workplace),
            'pay schedule' => empty($this->pay_frequency) || ($this->pay_frequency === 'monthly' && empty($this->pay_day)),
        ]));
    }

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

    // The helpers below follow the live rules in config/lending.php through App\Services\CreditAssessment
    // (4Cs score, risk category, ladder limit); there is no separate 300-850 score any more.

    public function getRiskCategoryAttribute()
    {
        $current = $this->currentAssessment();
        if ($current && $current['category']) {
            return $current['category']->name;
        }

        $overall = $this->calculate4csScore()['overall'];
        if ($overall >= 80) return 'Low Risk';
        if ($overall >= 65) return 'Medium Risk';
        if ($overall >= 50) return 'High Risk';
        return 'Very High Risk';
    }

    /**
     * Most a month's repayment can take: declared net income x lending.income_multiple (the same income
     * check the limit uses).
     */
    public function getMonthlyDebtCapacityAttribute()
    {
        if (!$this->net_salary) return 0;

        return (float) $this->net_salary * (float) config('lending.income_multiple', 1.0);
    }

    /**
     * Today's self-service borrowing limit (BorrowingEligibility / CreditAssessment ladder, risk and income caps).
     */
    public function getRecommendedLoanLimitAttribute()
    {
        return $this->currentAssessment()['limit']['limit'] ?? (int) config('lending.first_time_limit');
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
        // same income check as the limit: repayments may take up to lending.income_multiple x net income
        return $this->getDebtToIncomeRatio() > (float) config('lending.income_multiple', 1.0) * 100;
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
    /**
     * Today's CreditAssessment for this borrower (scores, category, limit), worked out once per request.
     */
    protected function currentAssessment(): ?array
    {
        if ($this->assessmentCache === false) {
            $this->assessmentCache = $this->user ? app(\App\Services\CreditAssessment::class)->current($this->user) : null;
        }

        return $this->assessmentCache;
    }

    public function calculate4csScore()
    {
        if (! isset($this->fourCsCache)) {
            $current = $this->currentAssessment();
            $this->fourCsCache = $current
                ? $current['scores'] + ['overall' => $current['overall'], 'weights' => \App\Services\CreditAssessment::WEIGHTS]
                : ['character' => 0, 'capacity' => 0, 'capital' => 0, 'conditions' => 0, 'overall' => 0, 'weights' => \App\Services\CreditAssessment::WEIGHTS];
        }

        return $this->fourCsCache;
    }
}