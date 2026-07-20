<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'document_type_id',
        'document_status_id',
        'document_name',
        'file_path',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'formatted_size',
        'file_url',
    ];

    // ============ RELATIONSHIPS ============

    public function case()
    {
        return $this->belongsTo(DebtRecoveryCase::class, 'case_id');
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function documentStatus()
    {
        return $this->belongsTo(DocumentStatus::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ============ ACCESSORS ============

    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $this->file_size > 0 ? floor(log($this->file_size, 1024)) : 0;
        return number_format($this->file_size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    public function getFileUrlAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }
}