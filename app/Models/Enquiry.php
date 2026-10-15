<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    // value => [label, desk inbox]; the form's "Enquiry type" options
    public const TOPICS = [
        'bridge' => ['Emergency Bridge Finance', 'clients@aimzoneventures.com'],
        'asset' => ['Asset-Backed Lending', 'clients@aimzoneventures.com'],
        'crb' => ['CRB Resolution', 'clients@aimzoneventures.com'],
        'trade' => ['Trade Finance', 'clients@aimzoneventures.com'],
        'investment' => ['Investment opportunities', 'investors@aimzoneventures.com'],
        'partnership' => ['Partnership', 'partnerships@aimzoneventures.com'],
        'other' => ['Media or general enquiry', 'hello@aimzoneventures.com'],
    ];

    protected $fillable = [
        'topic',
        'name',
        'organisation',
        'email',
        'phone',
        'investor_type',
        'allocation',
        'message',
        'route_to',
        'status',
        'consented_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
    ];

    public function getReferenceAttribute(): string
    {
        return 'ENQ-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getTopicLabelAttribute(): string
    {
        return self::TOPICS[$this->topic][0] ?? ucfirst($this->topic);
    }

    /**
     * Phone numbers are stored in one format so the same number can't slip past the
     * duplicate check: Kenyan 07xx / 01xx / 2547xx all become 2547xx…, others keep their digits.
     */
    public static function normalisePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (preg_match('/^0([17]\d{8})$/', $digits, $m)) {
            return '254' . $m[1];
        }

        return $digits;
    }
}
