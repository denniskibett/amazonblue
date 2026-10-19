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

    protected $fillable = ['business_id', 'type', 'path', 'original_name', 'mime', 'size', 'uploaded_by'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function label(): string
    {
        return self::TYPES[$this->type][0] ?? ucfirst(str_replace('_', ' ', $this->type));
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
