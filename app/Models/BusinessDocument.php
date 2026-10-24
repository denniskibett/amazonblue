<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A company document on the private disk (App\Support\PrivateFiles), served only by files.business.
 */
class BusinessDocument extends Model
{
    public const TYPES = [
        'certificate' => ['Registration certificate', 'Certificate of registration or incorporation (BRS)'],
        'cr12' => ['CR12', 'Directors and shareholders, dated within the last 6 months'],
        'kra_pin_certificate' => ['Business KRA PIN certificate', 'From iTax'],
        'bank_statement' => ['Bank statements', 'Last 6 months, business account (PDF)'],
        'business_permit' => ['Business permit', 'County single business permit'],
        'other' => ['Other document', 'e.g. board resolution or partnership deed'],
    ];

    // what a director uploads from their verification link (owner, 24 Oct 2026); kept apart from the company documents
    public const DIRECTOR_TYPES = [
        'director_id_front' => ['ID front (director)', 'National ID front or passport bio page'],
        'director_id_back' => ['ID back (director)', 'National ID back'],
        'director_selfie' => ['Selfie (director)', 'A clear photo of their face'],
    ];

    protected $fillable = ['business_id', 'business_member_id', 'type', 'path', 'original_name', 'mime', 'size', 'uploaded_by'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(BusinessMember::class, 'business_member_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function label(): string
    {
        return (self::TYPES + self::DIRECTOR_TYPES)[$this->type][0] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function url(): string
    {
        return route('files.business', ['business' => $this->business_id, 'document' => $this->id]);
    }
}
