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
        'profile_photo_path',
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
        'status',
        'social',
        'password_changed_at'
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
            'password_changed_at' => 'datetime',
        ];
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

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function bankAccounts()
    {
        return $this->morphMany(BankAccount::class, 'accountable');
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

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 1);
    }

    // ============ ACCESSORS ============

    public function getAgeAttribute()
    {
        return $this->dob ? $this->dob->age : null;
    }

    public function getAvatarUrlAttribute()
    {
        return $this->getAvatarUrl();
    }

    public function getInitialsAttribute()
    {
        return $this->getInitials();
    }

    public function getSocialLinksAttribute()
    {
        $social = $this->social ? json_decode($this->social, true) : [];
        
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
        $social = $this->social ? json_decode($this->social, true) : [];
        $usernames = [];
        
        foreach ($social as $platform => $value) {
            $usernames[$platform] = $this->extractUsername($value);
        }
        
        return $usernames;
    }

    // ============ HELPER METHODS ============

    public function getAvatarUrl()
    {
        // Check profile_photo_path first
        if ($this->profile_photo_path) {
            if (filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $this->profile_photo_path;
            }
            
            $path = $this->profile_photo_path;
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }
            return asset('storage/' . $path);
        }
        
        // Fallback to avatar field
        if ($this->avatar) {
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            
            $path = $this->avatar;
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }
            if (!str_starts_with($path, 'avatars/') && !str_starts_with($path, 'profile-photos/')) {
                $path = 'avatars/' . $path;
            }
            return asset('storage/' . $path);
        }
        
        // Ultimate fallback - UI Avatars
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF&bold=true';
    }

    public function getInitials($limit = 2)
    {
        if (!$this->name) {
            return 'U';
        }
        
        $nameParts = explode(' ', trim($this->name));
        $initials = '';
        
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        
        return substr($initials, 0, $limit) ?: 'U';
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
        
        return json_encode($social);
    }

    private function cleanUsername($username)
    {
        return ltrim(trim($username), '@');
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

    // ============ PROFILE COMPLETION ============

    public function getBiodataCompletionBySections()
    {
        $sections = [
            'personal' => [
                'name' => 'Personal Info',
                'fields' => ['name', 'email', 'phone', 'gender', 'dob', 'nationality', 'marital_status'],
                'required' => ['name', 'email', 'phone', 'gender', 'dob', 'nationality', 'marital_status'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'identification' => [
                'name' => 'Identification',
                'fields' => ['id_type', 'id_number', 'id_front_path', 'id_back_path'],
                'required' => ['id_type', 'id_number'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'next_of_kin' => [
                'name' => 'Next of Kin',
                'fields' => ['kin_name', 'kin_email', 'kin_phone', 'kin_occupation', 'kin_relation', 'kin_id_type', 'kin_id_number'],
                'required' => ['kin_name', 'kin_relation', 'kin_phone'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ],
            'documents' => [
                'name' => 'Documents',
                'fields' => ['profile_photo_path', 'signature'],
                'required' => ['profile_photo_path'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ]
        ];

        // Add employment section for borrowers
        if ($this->role === 'borrower' && $this->borrower) {
            $sections['employment'] = [
                'name' => 'Employment',
                'fields' => ['income_type', 'net_salary', 'job_title', 'employer_name'],
                'required' => ['income_type', 'net_salary', 'job_title', 'employer_name'],
                'percentage' => 0,
                'completed' => 0,
                'total' => 0,
                'missing' => []
            ];
        }

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
                $value = null;
                
                // Check if field is in borrower relationship
                if ($this->role === 'borrower' && $this->borrower && isset($this->borrower->$field)) {
                    $value = $this->borrower->$field;
                } elseif (isset($this->$field)) {
                    $value = $this->$field;
                }
                
                if (!empty($value)) {
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

    // ============ PROFILE LOCKING ============

    /**
     * Check if user has any active or pending loan applications
     * 
     * @return bool
     */
    public function hasActiveLoanApplication(): bool
    {
        return $this->loans()
            ->whereIn('status', ['pending', 'approved', 'processing', 'disbursed'])
            ->exists();
    }

    /**
     * Check if user has any loan in the application pipeline
     * 
     * @return bool
     */
    public function hasLoanInPipeline(): bool
    {
        return $this->loans()
            ->whereIn('status', ['pending', 'approved', 'processing', 'disbursed', 'active'])
            ->exists();
    }

    /**
     * Check if user's profile is locked (has active loans or applications)
     * 
     * @return bool
     */
    public function isProfileLocked(): bool
    {
        return $this->hasLoanInPipeline();
    }

    /**
     * Get the user's current loan status message
     * 
     * @return string|null
     */
    public function getLoanStatusMessage(): ?string
    {
        if (!$this->hasLoanInPipeline()) {
            return null;
        }
        
        $loan = $this->loans()
            ->whereIn('status', ['pending', 'approved', 'processing', 'disbursed', 'active'])
            ->latest()
            ->first();
        
        if (!$loan) {
            return null;
        }
        
        $statusMessages = [
            'pending' => 'Your loan application is being reviewed. Profile changes are temporarily locked.',
            'approved' => 'Your loan has been approved. Profile changes are locked during the disbursement process.',
            'processing' => 'Your loan is being processed. Profile changes are temporarily locked.',
            'disbursed' => 'Your loan has been disbursed. Profile changes are locked.',
            'active' => 'You have an active loan. Profile changes are locked.'
        ];
        
        return $statusMessages[$loan->status] ?? 'You have a loan in progress. Profile changes are temporarily locked.';
    }

    /**
     * Check if a specific field is locked
     * 
     * @param string $field
     * @return bool
     */
    public function isFieldLocked(string $field): bool
    {
        // If no active loans, nothing is locked
        if (!$this->hasLoanInPipeline()) {
            return false;
        }
        
        // Fields that should be locked when a loan is active
        $lockedFields = [
            // Basic personal info
            'name', 'email', 'phone', 'gender', 'dob', 'nationality', 'marital_status',
            // Identification
            'id_type', 'id_number', 'id_front_path', 'id_back_path',
            // Next of kin
            'kin_name', 'kin_email', 'kin_phone', 'kin_occupation', 'kin_relation', 
            'kin_id_type', 'kin_id_number',
            // Employment (borrower fields)
            'income_type', 'gross_salary', 'net_salary', 'job_title', 'workplace',
            'employer_name', 'employer_email', 'employer_title', 'department',
            // Borrower account
            'client_type', 'status',
            // Signature
            'signature',
            // Religion, education, disability
            'religion', 'education', 'disability',
            // Profile photo
            'profile_photo_path', 'avatar'
        ];
        
        return in_array($field, $lockedFields);
    }

    /**
     * Get the locked field names with user-friendly labels
     * 
     * @return array
     */
    public function getLockedFields(): array
    {
        $allLockedFields = [
            'name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'gender' => 'Gender',
            'dob' => 'Date of Birth',
            'nationality' => 'Nationality',
            'marital_status' => 'Marital Status',
            'id_type' => 'ID Type',
            'id_number' => 'ID Number',
            'id_front_path' => 'ID Front Photo',
            'id_back_path' => 'ID Back Photo',
            'kin_name' => 'Kin Full Name',
            'kin_email' => 'Kin Email',
            'kin_phone' => 'Kin Phone',
            'kin_occupation' => 'Kin Occupation',
            'kin_relation' => 'Kin Relationship',
            'kin_id_type' => 'Kin ID Type',
            'kin_id_number' => 'Kin ID Number',
            'income_type' => 'Income Type',
            'gross_salary' => 'Gross Salary',
            'net_salary' => 'Net Salary',
            'job_title' => 'Job Title',
            'workplace' => 'Workplace',
            'employer_name' => 'Employer Name',
            'employer_email' => 'Employer Email',
            'employer_title' => 'Employer Title',
            'department' => 'Department',
            'client_type' => 'Client Type',
            'status' => 'Account Status',
            'signature' => 'Digital Signature',
            'religion' => 'Religion',
            'education' => 'Education Level',
            'disability' => 'Disability Status',
            'profile_photo_path' => 'Profile Photo',
            'avatar' => 'Avatar'
        ];
        
        $lockedFields = [];
        foreach ($allLockedFields as $field => $label) {
            if ($this->isFieldLocked($field)) {
                $lockedFields[$field] = $label;
            }
        }
        
        return $lockedFields;
    }

    /**
     * Get the current loan that's causing the profile lock
     * 
     * @return \App\Models\Loan|null
     */
    public function getLockingLoan()
    {
        return $this->loans()
            ->whereIn('status', ['pending', 'approved', 'processing', 'disbursed', 'active'])
            ->latest()
            ->first();
    }

    /**
     * Check if profile lock should be shown
     * 
     * @return bool
     */
    public function shouldShowLock(): bool
    {
        return $this->role === 'borrower' && $this->isProfileLocked();
    }
}