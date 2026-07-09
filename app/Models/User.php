<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'password', 
        'phone', 
        'role',
        'avatar', 
        'gender',
        'dob',
        'pob',
        'nationality',
        'marital_status',
        'religion',
        'disability',
        'education',
        'kin_name',
        'kin_email', 
        'kin_phone',
        'kin_occupation',
        'kin_relation',
        'kin_id_type',
        'kin_id_number',
        'signature',
        'id_type',
        'id_number', 
        'id_front_path',
        'id_back_path',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'disability' => 'boolean',
        ];
    }

    // ============ SOCIAL LINKS ============
    
    public function getSocialLinksAttribute()
    {
        $social = $this->social ?: [];
        
        $links = [];
        
        if (!empty($social['facebook'])) {
            $links['facebook'] = $this->getSocialUrl($social['facebook'], 'https://facebook.com/');
        }
        
        if (!empty($social['twitter'])) {
            $links['twitter'] = $this->getSocialUrl($social['twitter'], 'https://twitter.com/');
        }
        
        if (!empty($social['instagram'])) {
            $links['instagram'] = $this->getSocialUrl($social['instagram'], 'https://instagram.com/');
        }
        
        if (!empty($social['linkedin'])) {
            $links['linkedin'] = $this->getSocialUrl($social['linkedin'], 'https://linkedin.com/in/');
        }
        
        return $links;
    }

    public function getSocialUsernamesAttribute()
    {
        $social = $this->social ?: [];
        $usernames = [];
        
        foreach ($social as $platform => $value) {
            $usernames[$platform] = $this->extractUsername($value);
        }
        
        return $usernames;
    }

    private function extractUsername($url)
    {
        if (empty($url)) {
            return '';
        }
        
        if (!str_contains($url, '.') && !str_contains($url, '/')) {
            return $url;
        }
        
        $patterns = [
            'facebook' => [
                '/^https?:\/\/(www\.)?facebook\.com\//',
                '/^https?:\/\/fb\.com\//'
            ],
            'twitter' => [
                '/^https?:\/\/(www\.)?twitter\.com\//',
                '/^https?:\/\/x\.com\//'
            ],
            'instagram' => [
                '/^https?:\/\/(www\.)?instagram\.com\//'
            ],
            'linkedin' => [
                '/^https?:\/\/(www\.)?linkedin\.com\/in\//'
            ]
        ];
        
        foreach ($patterns as $platformPatterns) {
            foreach ($platformPatterns as $pattern) {
                if (preg_match($pattern, $url)) {
                    return preg_replace($pattern, '', $url);
                }
            }
        }
        
        return $url;
    }

    private function getSocialUrl($value, $baseUrl)
    {
        if (empty($value)) {
            return null;
        }
        
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        
        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }

    public function prepareSocialData($data)
    {
        $social = [];
        
        foreach ($data as $platform => $value) {
            if (empty($value)) {
                continue;
            }
            
            $value = trim($value);
            
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $social[$platform] = $value;
            } else {
                $social[$platform] = $this->cleanUsername($value);
            }
        }
        
        return $social;
    }

    private function cleanUsername($username)
    {
        return ltrim(trim($username), '@');
    }

    // ============ RELATIONSHIPS ============

    public function borrower()
    {
        return $this->hasOne(Borrower::class, 'user_id');
    }

    public function broker()
    {
        return $this->hasOne(Broker::class);
    }

    public function teller()
    {
        return $this->hasOne(Teller::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    /**
     * Partner relationship - A user can be a partner
     */
    public function partner()
    {
        return $this->hasOne(Partner::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'accountable');
    }

    public function borrowerProfile()
    {
        return $this->hasOne(Borrower::class, 'user_id');
    }

    public function disbursements()
    {
        return $this->hasManyThrough(
            Disbursement::class,
            Loan::class,
            'user_id',
            'loan_id', 
            'id',
            'id'
        );
    }
    
    public function repayments()
    {
        return $this->hasManyThrough(
            Repayment::class,
            Loan::class,
            'user_id',
            'loan_id',
            'id',
            'id'
        );
    }

    public function guarantorLoans()
    {
        return $this->hasMany(Loan::class, 'guarantor_id');
    }

    // ============ HELPER METHODS ============

    public function isPartner(): bool
    {
        return $this->role === 'partner' && $this->partner;
    }

    public function getPartnerDashboardAttribute()
    {
        if (!$this->isPartner()) {
            return null;
        }

        return [
            'partner' => $this->partner,
            'transactions' => $this->partner->transactions()->latest()->limit(10)->get(),
            'investments' => $this->partner->investments,
            'loan_allocations' => $this->partner->loanAllocations
        ];
    }

    // ============ SCOPES ============

    public function scopeBorrowers($query)
    {
        return $query->where('role', 'borrower');
    }

    public function scopeBrokers($query)
    {
        return $query->where('role', 'broker');
    }

    public function scopeTellers($query)
    {
        return $query->where('role', 'teller');
    }

    public function scopePartners($query)
    {
        return $query->where('role', 'partner');
    }

    // ============ ACCESSORS ============

    public function getAgeAttribute()
    {
        return $this->dob ? $this->dob->age : null;
    }

    public function hasCompletePersonalInfo()
    {
        $requiredFields = ['gender', 'dob', 'nationality', 'marital_status', 'id_type', 'id_number'];
        
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }
        
        return true;
    }

    public function getNextOfKinAttribute()
    {
        return [
            'name' => $this->kin_name,
            'email' => $this->kin_email,
            'phone' => $this->kin_phone,
            'occupation' => $this->kin_occupation,
            'relation' => $this->kin_relation,
            'id_type' => $this->kin_id_type,
            'id_number' => $this->kin_id_number
        ];
    }

    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    public function getIdFrontUrlAttribute()
    {
        return $this->id_front_path ? Storage::url($this->id_front_path) : null;
    }

    public function getIdBackUrlAttribute()
    {
        return $this->id_back_path ? Storage::url($this->id_back_path) : null;
    }

    public function getSignatureUrlAttribute()
    {
        return $this->signature ? Storage::url($this->signature) : null;
    }

    // ============ BIODATA COMPLETION ============

    public function getBiodataCompletionBySections()
    {
        $sections = [
            'personal' => [
                'name' => 'Personal Info',
                'fields' => ['name', 'email', 'phone', 'gender', 'dob', 'nationality'],
                'required' => ['name', 'email', 'phone'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'identification' => [
                'name' => 'Identification',
                'fields' => ['id_type', 'id_number', 'id_front_path', 'id_back_path'],
                'required' => ['id_type', 'id_number', 'id_front_path'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'employment' => [
                'name' => 'Employment',
                'fields' => $this->role === 'borrower' ? ['income_type', 'job_title', 'net_salary', 'employer_name'] : [],
                'required' => $this->role === 'borrower' ? ['income_type'] : [],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'next_of_kin' => [
                'name' => 'Next of Kin',
                'fields' => ['kin_name', 'kin_relation', 'kin_phone'],
                'required' => ['kin_name', 'kin_relation'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'documents' => [
                'name' => 'Documents',
                'fields' => ['avatar', 'signature'],
                'required' => ['avatar'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ]
        ];

        $totalCompleted = 0;
        $totalFields = 0;

        foreach ($sections as $sectionName => &$section) {
            if (empty($section['fields'])) {
                $section['percentage'] = 100;
                $section['missing'] = [];
                continue;
            }

            $section['total'] = count($section['fields']);
            $section['completed'] = 0;

            foreach ($section['fields'] as $field) {
                if ($this->$field) {
                    $section['completed']++;
                } else {
                    if (in_array($field, $section['required'])) {
                        $section['missing'][] = str_replace('_', ' ', $field);
                    }
                }
            }

            if ($section['total'] > 0) {
                $section['percentage'] = round(($section['completed'] / $section['total']) * 100);
            } else {
                $section['percentage'] = 100;
            }

            $totalCompleted += $section['completed'];
            $totalFields += $section['total'];
        }

        $overallPercentage = $totalFields > 0 ? round(($totalCompleted / $totalFields) * 100) : 100;

        return [
            'sections' => $sections,
            'overall_percentage' => $overallPercentage,
            'total_completed' => $totalCompleted,
            'total_fields' => $totalFields
        ];
    }

    public function hasCompleteBiodata($threshold = 80)
    {
        $completionData = $this->getBiodataCompletionBySections();
        return $completionData['overall_percentage'] >= $threshold;
    }

    public function getBiodataCompletionPercentage()
    {
        $completionData = $this->getBiodataCompletionBySections();
        return $completionData['overall_percentage'];
    }

    public function getMissingBiodataFields()
    {
        $missingFields = [];
        $completionData = $this->getBiodataCompletionBySections();
        
        foreach ($completionData['sections'] as $section) {
            if (!empty($section['missing'])) {
                $missingFields = array_merge($missingFields, $section['missing']);
            }
        }
        
        return array_unique($missingFields);
    }
}