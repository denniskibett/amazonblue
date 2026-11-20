<?php

namespace App\Models\Funding;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/** A business that may need float, working capital, asset or trade finance (funding CRM, 20 Nov 2026). */
class FundingProspect extends Model
{
    protected $fillable = ['name', 'segment', 'need', 'county', 'town', 'website', 'email', 'phone', 'contact_name', 'contact_role',
        'source', 'source_url', 'est_need', 'stage', 'owner_id', 'next_on', 'next_step', 'last_emailed_at', 'opted_out_at', 'application_id', 'notes'];

    protected function casts(): array
    {
        return ['est_need' => 'decimal:2', 'next_on' => 'date', 'last_emailed_at' => 'datetime', 'opted_out_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->email = $p->email ? strtolower(trim($p->email)) : null;
            $p->stage ??= 'new';
            $p->reference ??= 'tmp-' . \Illuminate\Support\Str::random(12);
        });
        // the reference needs the id: AIM-P-00001
        static::created(function (self $p) {
            $p->reference = 'AIM-P-' . str_pad((string) $p->id, 5, '0', STR_PAD_LEFT);
            $p->saveQuietly();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(FundingEmail::class, 'prospect_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(FundingActivity::class, 'prospect_id');
    }

    public function segmentLabel(): string
    {
        return config('funding_crm.segments.' . $this->segment . '.label', ucfirst((string) $this->segment));
    }

    public function stageLabel(): string
    {
        return config('funding_crm.stages.' . $this->stage, ucfirst((string) $this->stage));
    }

    /** On the suppression list, or opted out: never emailed again. */
    public function suppressed(): bool
    {
        return $this->opted_out_at !== null || ($this->email && Suppression::has($this->email));
    }

    public function setStage(string $stage, ?User $by = null, ?string $why = null): void
    {
        if ($stage === $this->stage || ! array_key_exists($stage, config('funding_crm.stages'))) {
            return;
        }
        $from = $this->stageLabel();
        $this->update(['stage' => $stage]);
        $this->activities()->create(['user_id' => $by?->id, 'type' => 'stage', 'title' => $from . ' → ' . $this->stageLabel(), 'body' => $why, 'done_at' => now()]);
    }

    /** Placeholders for the playbook and campaign emails. */
    public function merge(string $text, ?User $manager): string
    {
        return strtr($text, [
            '{company}' => $this->name,
            '{town}' => $this->town ?: ($this->county ?: 'Kenya'),
            '{manager}' => $manager?->name ?: 'The Aimzone Ventures team',
            '{manager_phone}' => $manager?->phone ?: '+254 701 607 959',
        ]);
    }

    public static function counts(): array
    {
        return DB::table('funding_prospects')->selectRaw('stage, count(*) as n')->groupBy('stage')->pluck('n', 'stage')->map(fn ($n) => (int) $n)->all();
    }
}
