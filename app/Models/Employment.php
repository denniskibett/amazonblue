<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employer_id',
        'employer_name', // For backward compatibility
        'employer_address',
        'employer_phone',
        'employer_email',
        'job_title',
        'department',
        'supervisor_name',
        'supervisor_phone',
        'supervisor_email',
        'hr_contact_name',
        'hr_contact_phone',
        'hr_contact_email',
        'employment_type_id',
        'start_date',
        'end_date',
        'is_current',
        'notes',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = [
        'duration',
        'employment_type_name',
    ];

    // ============ RELATIONSHIPS ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    // ============ ACCESSORS ============

    public function getDurationAttribute()
    {
        if (!$this->start_date) {
            return null;
        }
        $end = $this->end_date ?? now();
        return $this->start_date->diffForHumans($end);
    }

    public function getEmploymentTypeNameAttribute()
    {
        return $this->employmentType ? $this->employmentType->name : null;
    }

    public function getEmployerNameAttribute($value)
    {
        if ($this->employer) {
            return $this->employer->name;
        }
        return $value;
    }

    public function getEmployerPhoneAttribute($value)
    {
        if ($this->employer) {
            return $this->employer->phone;
        }
        return $value;
    }

    public function getEmployerEmailAttribute($value)
    {
        if ($this->employer) {
            return $this->employer->email;
        }
        return $value;
    }

    public function getEmployerAddressAttribute($value)
    {
        if ($this->employer) {
            return $this->employer->address;
        }
        return $value;
    }

    // ============ SCOPES ============

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeByEmployer($query, $employerName)
    {
        return $query->where('employer_name', 'like', '%' . $employerName . '%');
    }
}