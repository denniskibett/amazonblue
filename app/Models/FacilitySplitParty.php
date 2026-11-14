<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilitySplitParty extends Model
{
    protected $fillable = ['facility_split_id', 'partner_id', 'principal', 'pi_entitled', 'penalty_pct', 'received_before'];

    protected $casts = ['principal' => 'decimal:2', 'pi_entitled' => 'decimal:2', 'penalty_pct' => 'decimal:2', 'received_before' => 'decimal:2'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
