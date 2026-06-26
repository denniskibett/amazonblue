<?php

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
    /**
     * Get social links with proper URLs
     */
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

    /**
     * Extract username from URL or return as-is
     */
    public function getSocialUsernamesAttribute()
    {
        $social = $this->social ?: [];
        $usernames = [];
        
        foreach ($social as $platform => $value) {
            $usernames[$platform] = $this->extractUsername($value);
        }
        
        return $usernames;
    }

    /**
     * Helper to extract username from URL
     */
    private function extractUsername($url)
    {
        if (empty($url)) {
            return '';
        }
        
        // If it's already a username (no dots, no slashes), return it
        if (!str_contains($url, '.') && !str_contains($url, '/')) {
            return $url;
        }
        
        // Remove protocol and domain
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
        
        // Try to match known patterns
        foreach ($patterns as $platformPatterns) {
            foreach ($platformPatterns as $pattern) {
                if (preg_match($pattern, $url)) {
                    return preg_replace($pattern, '', $url);
                }
            }
        }
        
        // If no match, return as-is
        return $url;
    }

    /**
     * Helper to create full URL from username
     */
    private function getSocialUrl($value, $baseUrl)
    {
        if (empty($value)) {
            return null;
        }
        
        // If it's already a URL, return it
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        
        // Otherwise, append to base URL
        return rtrim($baseUrl, '/') . '/' . ltrim($value, '/');
    }

    /**
     * Prepare social data for storage
     */
    public function prepareSocialData($data)
    {
        $social = [];
        
        foreach ($data as $platform => $value) {
            if (empty($value)) {
                continue;
            }
            
            // Clean the value
            $value = trim($value);
            
            // If it's a full URL, store as-is
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                $social[$platform] = $value;
            } else {
                // Store as username
                $social[$platform] = $this->cleanUsername($value);
            }
        }
        
        return $social;
    }

    /**
     * Clean username (remove @ symbol, trim)
     */
    private function cleanUsername($username)
    {
        return ltrim(trim($username), '@');
    }

    // Relationships
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

    // Scopes
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

    // Helper Methods
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

    /**
     * Get biodata completion percentage
     */
    public function getBiodataCompletionPercentage()
    {
        $completionData = $this->getBiodataCompletionBySections();
        return $completionData['overall_percentage'];
    }

    /**
     * Get missing biodata fields
     */
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
