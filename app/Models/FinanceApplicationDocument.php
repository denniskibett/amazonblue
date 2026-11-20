<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A document on a finance application, on the private disk; served only to the applicant's link and to staff. */
class FinanceApplicationDocument extends Model
{
    protected $fillable = ['finance_application_id', 'type', 'path', 'original_name', 'mime', 'size'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(FinanceApplication::class, 'finance_application_id');
    }

    public function label(): string
    {
        return config('finance_journey.documents.' . $this->type . '.0', ucfirst($this->type));
    }
}
