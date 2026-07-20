<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected $appends = [
        'formatted_action',
        'user_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedActionAttribute()
    {
        $actions = [
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'restore' => 'Restored',
            'force_delete' => 'Permanently Deleted',
        ];
        return $actions[$this->action] ?? ucfirst($this->action);
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'System';
    }

    public function scopeForTable($query, $table)
    {
        return $query->where('table_name', $table);
    }

    public function scopeForRecord($query, $table, $recordId)
    {
        return $query->where('table_name', $table)
            ->where('record_id', $recordId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }
}