<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * What a real borrower says about Aimzone (owner, 1 Nov 2026). One per borrower; only approved ones reach the
 * website, and any edit by the borrower sends it back to pending.
 */
class Testimonial extends Model
{
    public const CACHE_KEY = 'home.testimonials';

    // a borrower can leave one once money was actually sent on one of their loans
    public const SENT_STATUSES = [
        Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE,
        Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY, Loan::STATUS_REPAID, Loan::STATUS_WRITTEN_OFF,
    ];

    protected $fillable = ['user_id', 'loan_id', 'rating', 'body', 'display_name', 'display_role', 'status', 'approved_by', 'approved_at'];

    protected $casts = ['rating' => 'integer', 'approved_at' => 'datetime'];

    protected static function booted(): void
    {
        // the home page caches the approved list briefly; any change refreshes it
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', 'approved');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** The borrower's latest loan that had money sent, or null when they can't leave one yet. */
    public static function qualifyingLoan(User $user): ?Loan
    {
        return Loan::where('user_id', $user->id)
            ->where(fn ($q) => $q->whereIn('status', self::SENT_STATUSES)->orWhereHas('disbursements'))
            ->latest('id')->first();
    }

    /** "Sarah K." from "Sarah Wanjiku Kariuki". */
    public static function defaultName(User $user): string
    {
        $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $first = $parts[0] ?? 'Customer';

        return count($parts) > 1 ? $first . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : $first;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim(str_replace('.', '', $this->display_name))) ?: [];

        return mb_strtoupper(collect($words)->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: 'A';
    }
}
