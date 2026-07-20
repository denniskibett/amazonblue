<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'industry',
        'notes',
    ];

    // ============ RELATIONSHIPS ============

    public function employments()
    {
        return $this->hasMany(Employment::class);
    }

    public function employees()
    {
        return $this->hasManyThrough(
            User::class,
            Employment::class,
            'employer_id',
            'id',
            'id',
            'user_id'
        )->where('employments.is_current', true);
    }

    public function currentEmployees()
    {
        return $this->employments()
            ->where('is_current', true)
            ->with('user');
    }

    // ============ SCOPES ============

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }
}