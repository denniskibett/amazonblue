<?php

namespace App\Services\Kyc;

use App\Models\BusinessDocument;
use App\Services\Claude\Claude;
use App\Support\PrivateFiles;
use Illuminate\Support\Str;

/**
 * Reads a business KYC document (registration certificate, CR12, KRA PIN certificate, business permit, a director's
 * ID) with Claude and keeps the fields on the document, then compares them with what the applicant typed (owner,
 * 20 Nov 2026; ported from scharge's Kyc\DocumentReader). Staff press "Read with Claude" on the business review: a
 * document goes to Anthropic only then, and only while Integrations › Claude is on with document reading allowed.
 * The result helps the reviewer; it never approves anything by itself.
 */
class DocumentReader
{
    /** Fields to read per document type (all strings, "" when not on the document). */
    public const FIELDS = [
        'certificate' => ['business_name', 'registration_number', 'date_of_registration', 'business_type'],
        'cr12' => ['company_name', 'registration_number', 'directors', 'shareholders', 'date_of_issue'],
        'kra_pin_certificate' => ['taxpayer_name', 'kra_pin', 'date_of_issue'],
        'business_permit' => ['business_name', 'permit_number', 'county', 'valid_until'],
        'director_id_front' => ['full_name', 'id_number', 'date_of_birth', 'sex'],
    ];

    private const MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function enabled(): bool
    {
        return Claude::enabled() && (bool) config('services.anthropic.read_documents', true);
    }

    public static function canRead(BusinessDocument $doc): bool
    {
        return isset(self::FIELDS[$doc->type]) && in_array($doc->mime, self::MIMES, true);
    }

    /** Read one document and store the result on it. */
    public function read(BusinessDocument $doc): BusinessDocument
    {
        if (! self::enabled()) {
            return $this->fail($doc, 'off', 'Reading documents with Claude is off (Integrations › Claude).');
        }
        if (! self::canRead($doc)) {
            return $this->fail($doc, 'failed', 'Claude reads PDFs and photos of certificates, CR12s, KRA PINs, permits and IDs only.');
        }
        $file = PrivateFiles::absolute($doc->path);
        if (! $file || ($bytes = @file_get_contents($file)) === false) {
            return $this->fail($doc, 'failed', 'That document is not on this server.');
        }

        $fields = self::FIELDS[$doc->type];
        $data = base64_encode($bytes);
        $block = $doc->mime === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'mediaType' => 'application/pdf', 'data' => $data]]
            : ['type' => 'image', 'source' => ['type' => 'base64', 'mediaType' => $doc->mime, 'data' => $data]];
        $schema = ['type' => 'object', 'additionalProperties' => false,
            'required' => array_merge($fields, ['legible', 'looks_like', 'notes']),
            'properties' => array_fill_keys($fields, ['type' => 'string']) + [
                'legible' => ['type' => 'boolean'],
                'looks_like' => ['type' => 'string', 'description' => 'What kind of document this actually is'],
                'notes' => ['type' => 'string', 'description' => 'Anything unclear, cut off, edited-looking or inconsistent; empty if none'],
            ]];

        [$json, $error] = Claude::json(
            'You read Kenyan company, tax and identity documents for a lender\'s business verification (KYB) check. '
            . 'Copy each field exactly as printed; never guess or complete partly hidden values: leave a field "" when it is '
            . 'not on the document or not readable. Dates as YYYY-MM-DD. For lists (directors, shareholders) give the names '
            . 'separated by "; " in the order printed, with each shareholder\'s shares in brackets when shown.',
            [$block, ['type' => 'text', 'text' => 'This was uploaded as: ' . $doc->label() . '. Read it and return the fields.']],
            $schema,
        );
        if ($error) {
            return $this->fail($doc, 'failed', $error);
        }

        $read = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $json);
        $doc->forceFill(['read_status' => 'read', 'extracted' => $read + ['_checks' => $this->compare($doc, $read)],
            'read_error' => null, 'read_at' => now()])->save();

        return $doc;
    }

    /**
     * What the document says against what the applicant typed. Each check: [label, ok (true/false/null = cannot tell)].
     *
     * @return array<int, array{0: string, 1: ?bool}>
     */
    public function compare(BusinessDocument $doc, array $read): array
    {
        $business = $doc->business;
        $checks = [];
        $name = $read['business_name'] ?? $read['company_name'] ?? $read['taxpayer_name'] ?? null;
        if ($name !== null && $doc->type !== 'director_id_front') {
            $checks[] = ['Name matches the business', self::sameName($name, [$business->legal_name, $business->trading_name])];
        }
        if (isset($read['registration_number'])) {
            $checks[] = ['Registration number matches', self::sameRef($read['registration_number'], $business->registration_number)];
        }
        if (isset($read['kra_pin'])) {
            $checks[] = ['KRA PIN matches', self::sameRef($read['kra_pin'], $business->kra_pin)];
        }
        if ($doc->type === 'cr12' && ($read['directors'] ?? '') !== '') {
            $listed = collect(explode(';', $read['directors']))->map(fn ($n) => trim(preg_replace('/\(.*?\)/', '', $n)))->filter();
            foreach ($business->members()->whereIn('role', ['owner', 'director'])->get() as $m) {
                $checks[] = ['Director ' . $m->full_name . ' is on the CR12', $listed->contains(fn ($n) => self::sameName($n, [$m->full_name]))];
            }
        }
        if ($doc->type === 'director_id_front' && $doc->member) {
            $checks[] = ['Name matches ' . $doc->member->full_name, self::sameName($read['full_name'] ?? '', [$doc->member->full_name])];
            $checks[] = ['ID number matches', self::sameRef($read['id_number'] ?? '', $doc->member->id_number)];
        }

        return $checks;
    }

    private function fail(BusinessDocument $doc, string $status, string $error): BusinessDocument
    {
        $doc->forceFill(['read_status' => $status, 'read_error' => mb_substr($error, 0, 250), 'read_at' => now()])->save();

        return $doc;
    }

    /** Same name, ignoring case, punctuation, word order and words like LIMITED / LTD. Null when either side is blank. */
    public static function sameName(?string $read, array $typed): ?bool
    {
        $norm = function (?string $s): string {
            $s = Str::of((string) $s)->upper()->replaceMatches('/[^A-Z0-9 ]+/', ' ')->replaceMatches('/\b(LIMITED|LTD|COMPANY|CO|THE|PLC)\b/', ' ')->squish();
            $words = explode(' ', (string) $s);
            sort($words);

            return trim(implode(' ', $words));
        };
        $typed = array_filter(array_map($norm, $typed));
        $read = $norm($read);
        if ($read === '' || ! $typed) {
            return null;
        }

        return in_array($read, $typed, true);
    }

    /** Same reference number, ignoring case, spaces and punctuation. Null when either side is blank. */
    public static function sameRef(?string $read, ?string $typed): ?bool
    {
        $norm = fn (?string $s) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $s));
        if ($norm($read) === '' || $norm($typed) === '') {
            return null;
        }

        return $norm($read) === $norm($typed);
    }
}
