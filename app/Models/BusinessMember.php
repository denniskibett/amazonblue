<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A director, owner, shareholder or signatory of a business. user_id is set for the owner and for
 * anyone whose ID number matches an approved individual KYC; verified_at is when that was confirmed.
 */
class BusinessMember extends Model
{
    public const ROLES = [
        'owner' => 'Owner / proprietor',
        'director' => 'Director',
        'shareholder' => 'Shareholder',
        'signatory' => 'Authorised signatory',
    ];

    protected $fillable = [
        'business_id', 'user_id', 'full_name', 'role', 'id_number', 'kra_pin', 'phone', 'email',
        'ownership_pct', 'is_signatory', 'verified_at', 'verify_sent_at', 'verify_expires_at',
    ];

    protected $hidden = ['verify_token_hash'];

    protected function casts(): array
    {
        return [
            'ownership_pct' => 'decimal:2',
            'is_signatory' => 'boolean',
            'verified_at' => 'datetime',
            'verify_sent_at' => 'datetime',
            'verify_expires_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Their ID pictures from the self-verification link (owner, 24 Oct 2026). */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BusinessDocument::class, 'business_member_id');
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->full_name)))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }
}
