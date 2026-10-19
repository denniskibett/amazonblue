<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A business added by a verified borrower (business KYC). The owner fills it in from /account
 * (HomeController + Concerns\BusinessOnboarding); staff approve or reject it in Business reviews
 * (BusinessReviewController). Audited by AuditObserver.
 *
 * Status: draft -> submitted -> approved | rejected (rejected reopens for editing, then submitted again).
 * Business loans, trade finance and asset-backed lending hang off this record in Stage 4.
 */
class Business extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Waiting for review',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public const REGISTRATION_TYPES = [
        'sole_proprietorship' => 'Sole proprietorship',
        'partnership' => 'Partnership',
        'limited_company' => 'Limited company',
        'other' => 'Other',
    ];

    public const INDUSTRIES = [
        'Agriculture & commodities', 'Wholesale & retail', 'Transport & logistics', 'Manufacturing',
        'Construction & real estate', 'Hospitality & food', 'Professional services', 'Health & education',
        'Technology', 'Other',
    ];

    // what staff tick on the review (all required to approve)
    public const CHECKS = [
        'registration' => 'Registration number and name match the certificate (BRS search done)',
        'kra_pin' => 'Business KRA PIN matches the PIN certificate',
        'members' => 'Directors and shareholders match the CR12 or registration documents',
        'applicant' => 'Spoke to the applicant and confirmed they act for the business',
    ];

    protected $fillable = [
        'owner_user_id', 'legal_name', 'trading_name', 'registration_number', 'registration_type', 'kra_pin',
        'industry', 'date_registered', 'address_line_1', 'address_line_2', 'city', 'county', 'postal_code',
        'country', 'phone', 'email', 'website', 'monthly_revenue', 'employees', 'bank_name', 'bank_account',
        'status', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes', 'checks', 'handle',
    ];

    protected function casts(): array
    {
        return [
            'date_registered' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'monthly_revenue' => 'decimal:2',
            'employees' => 'integer',
            'checks' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class)->orderByRaw("role = 'owner' desc")->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class)->orderBy('id');
    }

    public function displayName(): string
    {
        return $this->trading_name && $this->trading_name !== $this->legal_name
            ? $this->legal_name . ' (t/a ' . $this->trading_name . ')'
            : $this->legal_name;
    }

    public function reference(): string
    {
        return 'KYB-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** The owner can change it: a draft, or a rejected one being fixed. */
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function isLimitedCompany(): bool
    {
        return $this->registration_type === 'limited_company';
    }

    /** Document types this business must upload (a CR12 only for limited companies). */
    public function requiredDocumentTypes(): array
    {
        return array_values(array_filter(['certificate', $this->isLimitedCompany() ? 'cr12' : null, 'kra_pin_certificate']));
    }

    public function missingDocumentTypes(): array
    {
        $have = $this->documents->pluck('type')->unique()->all();

        return array_values(array_diff($this->requiredDocumentTypes(), $have));
    }

    public function ownershipTotal(): float
    {
        return round((float) $this->members->sum('ownership_pct'), 2);
    }

    /**
     * Who may see the business and its files: the owner, a member with a linked user account, or staff
     * who may open KYC documents (PrivateFileController::isStaffReviewer).
     */
    public function isVisibleTo(User $viewer): bool
    {
        if ($viewer->id === $this->owner_user_id) {
            return true;
        }
        if ($this->members()->where('user_id', $viewer->id)->exists()) {
            return true;
        }

        return \App\Http\Controllers\PrivateFileController::isStaffReviewer($viewer);
    }
}
