<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An outside debt collection agency (owner, 3 Nov 2026). Its collectors sign in to /collect. */
class CollectionAgency extends Model
{
    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'agreement_ref', 'commission_rate', 'coverage', 'cross_border', 'status', 'created_by'];

    protected $casts = ['commission_rate' => 'decimal:2', 'cross_border' => 'boolean'];

    public function collectors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'collection_agency_users', 'agency_id', 'user_id')->withTimestamps();
    }

    public function placements(): HasMany
    {
        return $this->hasMany(CollectionPlacement::class, 'agency_id');
    }

    /** The agency a collector belongs to, or null. */
    public static function of(User $user): ?self
    {
        $id = \Illuminate\Support\Facades\DB::table('collection_agency_users')->where('user_id', $user->id)->value('agency_id');

        return $id ? self::find($id) : null;
    }
}
