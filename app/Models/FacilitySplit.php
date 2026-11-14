<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A facility's own split rule (owner, 14 Nov 2026). See App\Services\Loans\FacilitySplitter. */
class FacilitySplit extends Model
{
    protected $fillable = ['loan_id', 'pi_target', 'total_cap', 'counted_from', 'notes', 'created_by'];

    protected $casts = ['pi_target' => 'decimal:2', 'total_cap' => 'decimal:2', 'counted_from' => 'date'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function parties(): HasMany
    {
        return $this->hasMany(FacilitySplitParty::class)->orderBy('id');
    }
}
