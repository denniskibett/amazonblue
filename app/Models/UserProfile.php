<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'current_residence',
        'current_residence_from',
        'current_residence_to',
        'residence_type_id',
        'residence_notes',
        'general_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_residence_from' => 'date',
        'current_residence_to' => 'date',
    ];

    protected $appends = [
        'residence_type_name',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function residenceType()
    {
        return $this->belongsTo(ResidenceType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id', 'user_id');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'user_id', 'user_id');
    }

    public function employments()
    {
        return $this->hasMany(Employment::class, 'user_id', 'user_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'user_id', 'user_id');
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class, 'user_id', 'user_id');
    }

    // ============ ACCESSORS ============

    public function getResidenceTypeNameAttribute()
    {
        return $this->residenceType ? $this->residenceType->name : null;
    }

    // ============ HELPERS ============

    public function getPrimaryAddress()
    {
        return $this->addresses()->where('is_primary', true)->first();
    }

    public function getCurrentAddress()
    {
        return $this->addresses()
            ->whereHas('addressType', function ($q) {
                $q->where('slug', 'current');
            })
            ->where('is_primary', true)
            ->first();
    }

    public function getContactsByType($typeSlug)
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) use ($typeSlug) {
                $q->where('slug', $typeSlug);
            })
            ->get();
    }

    public function getParents()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->whereIn('slug', ['parent']);
            })
            ->orderBy('priority', 'desc')
            ->get();
    }

    public function getSpouse()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->where('slug', 'spouse');
            })
            ->where('is_primary_contact', true)
            ->first();
    }

    public function getSiblings()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->where('slug', 'sibling');
            })
            ->get();
    }

    public function getChildren()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->where('slug', 'child');
            })
            ->get();
    }

    public function getBusinessPartners()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->where('slug', 'business_partner');
            })
            ->get();
    }

    public function getLandlord()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->where('slug', 'landlord');
            })
            ->first();
    }

    public function getSupervisor()
    {
        return $this->contacts()
            ->whereHas('contactType', function ($q) {
                $q->where('slug', 'supervisor');
            })
            ->first();
    }

    public function getCurrentEmployment()
    {
        return $this->employments()->where('is_current', true)->first();
    }

    public function getAllContacts()
    {
        return $this->contacts()->orderBy('priority', 'desc')->get();
    }
}