<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_name',
        'template_type',
        'subject',
        'body',
        'variables',
        'status',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    protected $appends = [
        'formatted_type',
        'formatted_status',
        'variable_list',
    ];

    // ============ RELATIONSHIPS ============

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ============ ACCESSORS ============

    public function getFormattedTypeAttribute()
    {
        $types = [
            'email' => 'Email',
            'sms' => 'SMS',
            'letter' => 'Letter',
            'legal_notice' => 'Legal Notice',
            'whatsapp' => 'WhatsApp',
        ];
        return $types[$this->template_type] ?? ucfirst($this->template_type);
    }

    public function getFormattedStatusAttribute()
    {
        return $this->status === 'active' ? 'Active' : 'Inactive';
    }

    public function getVariableListAttribute()
    {
        return $this->variables ? implode(', ', $this->variables) : [];
    }

    // ============ SCOPES ============

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    // ============ HELPERS ============

    public function render(array $data = [])
    {
        $content = $this->body;
        
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        // Remove any unused placeholders
        $content = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $content);
        
        return $content;
    }
}