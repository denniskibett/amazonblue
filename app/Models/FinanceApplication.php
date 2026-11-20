<?php

namespace App\Models;

use App\Models\Funding\FundingProspect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A business's asset or trade finance application (20 Nov 2026), filled in step by step from a private link.
 * Reference AIM-AF-26-0001 (asset) or AIM-TF-26-0001 (trade). See App\Services\FinanceJourney\Journey.
 */
class FinanceApplication extends Model
{
    protected $fillable = ['reference', 'product', 'status', 'steps_done', 'token_hash', 'last_seen_at', 'contact_name', 'email', 'phone',
        'business', 'details', 'amount', 'term_months', 'estimate', 'consent_at', 'consent_ip', 'submitted_at', 'prospect_id', 'loan_id',
        'reviewer_id', 'reviewed_at', 'staff_note', 'history'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['steps_done' => 'array', 'business' => 'array', 'details' => 'array', 'estimate' => 'array', 'history' => 'array',
            'amount' => 'decimal:2', 'term_months' => 'integer', 'last_seen_at' => 'datetime', 'consent_at' => 'datetime',
            'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FinanceApplicationDocument::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(FundingProspect::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function productConfig(?string $key = null): mixed
    {
        $c = config('finance_journey.products.' . $this->product, []);

        return $key ? ($c[$key] ?? null) : $c;
    }

    public function statusLabel(): string
    {
        return config('finance_journey.statuses.' . $this->status, ucfirst((string) $this->status));
    }

    /** The applicant may still change it: before submitting, or when we asked for more. */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'more_info'], true);
    }

    public function stepDone(string $step): bool
    {
        return in_array($step, $this->steps_done ?? [], true);
    }

    /** The first step not saved yet (the review step last). */
    public function nextStep(): string
    {
        foreach (array_keys(config('finance_journey.steps')) as $step) {
            if ($step !== 'review' && ! $this->stepDone($step)) {
                return $step;
            }
        }

        return 'review';
    }

    public function currency(): string
    {
        return $this->product === 'trade' ? ($this->details['currency'] ?? 'KES') : 'KES';
    }

    public function businessName(): string
    {
        return (string) ($this->business['legal_name'] ?? 'Your business');
    }

    public function log(string $status, ?string $note = null, ?User $by = null): void
    {
        $this->history = array_merge($this->history ?? [], [['at' => now()->toDateTimeString(), 'status' => $status, 'note' => $note, 'by' => $by?->name]]);
    }
}
