<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A guarantor's signed guarantee and release for one loan (App\Services\Loans\Guarantees). */
class LoanGuarantee extends Model
{
    public const WAITING = 'waiting';
    public const SIGNED = 'signed';
    public const WAIVED = 'waived';

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'dob' => 'date',
        'link_sent_at' => 'datetime',
        'link_expires_at' => 'datetime',
        'signed_at' => 'datetime',
        'waived_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function waiver()
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    /** Signed by the guarantor, or waived by a manager: the money may go. */
    public function released(): bool
    {
        return in_array($this->status, [self::SIGNED, self::WAIVED], true);
    }

    public function firstName(): string
    {
        return strtok((string) $this->name, ' ') ?: (string) $this->name;
    }
}
