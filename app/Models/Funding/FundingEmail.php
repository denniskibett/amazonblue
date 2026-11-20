<?php

namespace App\Models\Funding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One cold email to a prospect, its AIM-E- reference and what happened to it (sent, opened, clicked, replied, bounced). */
class FundingEmail extends Model
{
    protected $fillable = ['prospect_id', 'campaign_id', 'reference', 'track', 'to', 'subject', 'body', 'sent_by', 'message_id', 'sent_at',
        'opens', 'opened_at', 'clicked_at', 'replied_at', 'bounced_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'opened_at' => 'datetime', 'clicked_at' => 'datetime', 'replied_at' => 'datetime', 'bounced_at' => 'datetime', 'opens' => 'integer'];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(FundingProspect::class, 'prospect_id');
    }

    public function status(): string
    {
        return match (true) {
            $this->replied_at !== null => 'Replied',
            $this->bounced_at !== null => 'Bounced',
            $this->clicked_at !== null => 'Clicked',
            $this->opened_at !== null => 'Opened',
            default => 'Sent',
        };
    }

    public static function byTrack(?string $track): ?self
    {
        return $track && preg_match('/^[A-Za-z0-9]{32}$/', $track) ? self::where('track', $track)->first() : null;
    }
}
