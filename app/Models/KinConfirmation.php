<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The next of kin's answer to "do you know this client?" (App\Services\Kyc\KinConfirmations). */
class KinConfirmation extends Model
{
    public const WAITING = 'waiting';
    public const CONFIRMED = 'confirmed';
    public const DENIED = 'denied';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'link_sent_at' => 'datetime',
        'link_expires_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function firstName(): string
    {
        return strtok((string) $this->name, ' ') ?: (string) $this->name;
    }
}
