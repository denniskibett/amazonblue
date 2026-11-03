<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A loan placed with an agency until ends_on, or until staff take it back (owner, 3 Nov 2026). */
class CollectionPlacement extends Model
{
    protected $fillable = ['agency_id', 'loan_id', 'placed_by', 'placed_at', 'notice_sent_on', 'ends_on', 'status', 'returned_at', 'returned_by', 'note'];

    protected $casts = ['placed_at' => 'datetime', 'notice_sent_on' => 'date', 'ends_on' => 'date', 'returned_at' => 'datetime'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(CollectionAgency::class, 'agency_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CollectionContact::class, 'placement_id');
    }

    /** Open today: active and not past its end date. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', 'active')->whereDate('ends_on', '>=', now()->toDateString());
    }

    /**
     * The agency may contact the borrower once the borrower was told, and the notice period has run
     * (config lending.collections.agency_notice_days, 7 by default).
     */
    public function contactAllowed(): bool
    {
        $days = (int) config('lending.collections.agency_notice_days', 7);

        return $this->notice_sent_on !== null && $this->notice_sent_on->copy()->addDays($days)->lte(now()->startOfDay());
    }

    public function contactFrom(): ?\Illuminate\Support\Carbon
    {
        return $this->notice_sent_on?->copy()->addDays((int) config('lending.collections.agency_notice_days', 7));
    }
}
