<?php

namespace App\Models\Funding;

use Illuminate\Database\Eloquent\Model;

/** A cold email to every eligible prospect in a segment, sent in batches (AIM-C-001). */
class FundingCampaign extends Model
{
    protected $fillable = ['name', 'segment', 'subject', 'body', 'status', 'sent_count', 'created_by'];

    protected static function booted(): void
    {
        static::creating(fn (self $c) => $c->reference ??= 'tmp-' . \Illuminate\Support\Str::random(12));
        static::created(function (self $c) {
            $c->reference = 'AIM-C-' . str_pad((string) $c->id, 3, '0', STR_PAD_LEFT);
            $c->saveQuietly();
        });
    }
}
