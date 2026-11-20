<?php

namespace App\Models\Funding;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Everything done with a prospect: notes, calls, meetings, stage moves, and follow-ups (due_at, done_at). */
class FundingActivity extends Model
{
    public const TYPES = ['note' => 'Note', 'call' => 'Call', 'whatsapp' => 'WhatsApp', 'meeting' => 'Meeting', 'email' => 'Email', 'stage' => 'Stage', 'follow_up' => 'Follow-up'];

    protected $fillable = ['prospect_id', 'user_id', 'type', 'title', 'body', 'due_at', 'done_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'done_at' => 'datetime'];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(FundingProspect::class, 'prospect_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
