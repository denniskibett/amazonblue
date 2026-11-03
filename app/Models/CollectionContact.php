<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A call, SMS, visit or promise an agency collector logged on a placed loan (owner, 3 Nov 2026). */
class CollectionContact extends Model
{
    public const CHANNELS = ['call' => 'Call', 'sms' => 'SMS', 'visit' => 'Field visit', 'whatsapp' => 'WhatsApp', 'email' => 'Email'];

    protected $fillable = ['placement_id', 'loan_id', 'user_id', 'channel', 'outcome', 'promise_amount', 'promise_date', 'note'];

    protected $casts = ['promise_amount' => 'decimal:2', 'promise_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
