<?php

namespace App\Services\FinanceJourney;

use App\Models\FinanceApplication;
use App\Models\FinanceApplicationDocument;
use App\Models\Funding\FundingEmail;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The business finance journey (20 Nov 2026). A business starts an asset or trade finance application, every step is
 * saved as it goes, and the applicant comes back through a private link (only the link's SHA-256 is stored; a link
 * lapses `link_days` after the last visit). Steps: business → need (the asset or the deal) → terms (amount, term and
 * an estimate) → documents → consent and submit. Staff then review it (FinanceReview).
 *
 * This is regulated credit: the estimate is labelled indicative, and nothing here approves or promises finance.
 */
class Journey
{
    public const DISK = 'local';

    /** @return array{0: FinanceApplication, 1: string} the application and its plain link token */
    public function start(string $product, array $input, ?string $track = null): array
    {
        if (! config('finance_journey.products.' . $product)) {
            throw new \DomainException('Choose asset finance or trade finance.');
        }
        $input['phone'] = preg_replace('/[\s\-()]/', '', (string) ($input['phone'] ?? ''));
        $d = Validator::make($input, [
            'contact_name' => 'required|string|max:120',
            'email' => 'required|email:filter|max:190',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+?254|0)[17]\d{8}$/'],
            'legal_name' => 'required|string|max:190',
            'contact_ok' => 'accepted',
        ], ['phone.regex' => 'Enter a Kenyan mobile number, e.g. 0712 345 678.', 'contact_ok.accepted' => 'Tick the box so we can contact you about this application.'])->validate();

        $token = Str::random(48);
        $prospect = FundingEmail::byTrack($track)?->prospect;

        $app = DB::transaction(function () use ($product, $d, $token, $prospect) {
            $prefix = config('finance_journey.products.' . $product . '.prefix');
            $n = (int) FinanceApplication::where('product', $product)->whereYear('created_at', now()->year)->lockForUpdate()->count() + 1;
            $app = new FinanceApplication([
                'reference' => $prefix . '-' . now()->format('y') . '-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
                'product' => $product, 'status' => 'draft', 'steps_done' => [], 'token_hash' => hash('sha256', $token), 'last_seen_at' => now(),
                'contact_name' => $d['contact_name'], 'email' => strtolower($d['email']), 'phone' => preg_replace('/\s+/', '', $d['phone']),
                'business' => ['legal_name' => $d['legal_name']], 'prospect_id' => $prospect?->id,
            ]);
            $app->log('draft', 'Started');
            $app->save();

            return $app;
        });

        if ($prospect) {
            $prospect->update(['application_id' => $app->id]);
            if (in_array($prospect->stage, ['new', 'contacted', 'interested'], true)) {
                $prospect->setStage('applied', null, 'Started ' . $app->reference);
            }
        }
        AuditTrail::record('finance_applications', $app->id, 'create', null, ['reference' => $app->reference, 'product' => $product]);

        return [$app, $token];
    }

    /** The application behind a link, or null if the link is wrong or has lapsed. Touches last_seen_at. */
    public function find(?string $token): ?FinanceApplication
    {
        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{48}$/', $token)) {
            return null;
        }
        $app = FinanceApplication::where('token_hash', hash('sha256', $token))->first();
        if (! $app || ($app->last_seen_at && $app->last_seen_at->lt(now()->subDays((int) config('finance_journey.link_days', 30))))) {
            return null;
        }
        $app->forceFill(['last_seen_at' => now()])->saveQuietly();

        return $app;
    }

    /** A fresh link (the old one stops working). Used when the applicant asks again and when staff write to them. */
    public function rotate(FinanceApplication $app): string
    {
        $token = Str::random(48);
        $app->forceFill(['token_hash' => hash('sha256', $token), 'last_seen_at' => now()])->saveQuietly();

        return $token;
    }

    /** Validation rules for one step (documents are checked in saveDocuments). */
    public function rules(FinanceApplication $app, string $step, array $input = []): array
    {
        $p = $app->productConfig();

        return match ($step) {
            'business' => [
                'legal_name' => 'required|string|max:190',
                'registration_number' => 'required|string|max:40',
                'kra_pin' => ['required', 'string', 'regex:/^[AP]\d{9}[A-Z]$/i'],
                'sector' => 'required|in:' . implode(',', array_merge(array_keys(config('funding_crm.segments')), ['other'])),
                'county' => 'required|string|max:60',
                'years_trading' => 'required|integer|min:0|max:150',
                'annual_turnover' => 'required|numeric|min:0|max:100000000000',
                'directors' => 'required|integer|min:1|max:50',
            ],
            'need' => $app->product === 'asset' ? [
                'category' => 'required|in:' . implode(',', array_keys($p['categories'])),
                'description' => 'required|string|max:255',
                'condition' => 'required|in:new,used',
                'supplier' => 'required|string|max:190',
                'supplier_phone' => 'nullable|string|max:40',
                'price' => 'required|numeric|min:' . $p['min_amount'] . '|max:' . ($p['max_amount'] * 2),
                'deposit' => 'required|numeric|min:0|lt:price',
            ] : [
                'kind' => 'required|in:' . implode(',', array_keys($p['kinds'])),
                'supplier' => 'required|string|max:190',
                'supplier_country' => 'required|string|max:60',
                'goods' => 'required|string|max:255',
                'buyer' => (in_array($input['kind'] ?? null, ['lpo', 'invoice'], true) ? 'required' : 'nullable') . '|string|max:190',
                'currency' => 'required|in:KES,USD',
                'order_value' => 'required|numeric|min:1|max:1000000000',
                'buyer_pays_on' => 'nullable|date|after:today',
            ],
            'terms' => [
                'amount' => 'required|numeric|min:' . ($app->currency() === 'USD' ? 1000 : $p['min_amount']) . '|max:' . $p['max_amount'],
                'term_months' => 'required|integer|in:' . implode(',', $p['terms']),
                'purpose' => 'nullable|string|max:500',
            ],
            'review' => [
                'confirm_true' => 'accepted',
                'consent_crb' => 'accepted',
                'consent_data' => 'accepted',
            ],
            default => throw new \DomainException('That step is not part of the application.'),
        };
    }

    /** Save one step's fields. Throws ValidationException with field errors, DomainException for flow errors. */
    public function saveStep(FinanceApplication $app, string $step, array $input, ?string $ip = null): FinanceApplication
    {
        if (! $app->isEditable()) {
            throw new \DomainException($app->reference . ' is ' . strtolower($app->statusLabel()) . '; it can only be changed if we ask you for more.');
        }
        if ($step === 'documents') {
            throw new \DomainException('Documents are saved by uploading them.');
        }
        $d = Validator::make($input, $this->rules($app, $step, $input), [
            'kra_pin.regex' => 'A KRA PIN is a letter, 9 digits and a letter, e.g. P051234567X.',
            'deposit.lt' => 'The deposit must be less than the price.',
            'confirm_true.accepted' => 'Confirm that the details are true.',
            'consent_crb.accepted' => 'We need your consent to check the business and its directors with a credit reference bureau.',
            'consent_data.accepted' => 'We need your consent to process this information.',
        ])->validate();

        match ($step) {
            'business' => $app->business = array_merge($app->business ?? [], $d, ['kra_pin' => strtoupper($d['kra_pin'])]),
            'need' => $this->need($app, $d),
            'terms' => $this->terms($app, $d),
            'review' => $this->submit($app, $ip),
        };
        if ($step !== 'review') {
            $app->steps_done = array_values(array_unique(array_merge($app->steps_done ?? [], [$step])));
        }
        $app->save();

        return $app;
    }

    /** A changed asset or deal means the amount and term are asked again. */
    private function need(FinanceApplication $app, array $d): void
    {
        if ($app->stepDone('terms') && $app->details != $d) {
            $app->steps_done = array_values(array_diff($app->steps_done ?? [], ['terms']));
        }
        $app->details = $d;
    }

    private function terms(FinanceApplication $app, array $d): void
    {
        if (! $app->stepDone('need')) {
            throw new \DomainException('Tell us about the ' . ($app->product === 'asset' ? 'asset' : 'deal') . ' first.');
        }
        $amount = (float) $d['amount'];
        if ($app->product === 'asset') {
            $price = (float) ($app->details['price'] ?? 0);
            $minDeposit = $price * (float) $app->productConfig('min_deposit_pct') / 100;
            $max = $price - max($minDeposit, (float) ($app->details['deposit'] ?? 0));
            if ($amount > $max + 0.01) {
                throw ValidationException::withMessages(['amount' => 'For this asset you can ask for up to KES ' . number_format($max) . ' (the price less your deposit).']);
            }
        } elseif ($amount > (float) ($app->details['order_value'] ?? 0)) {
            throw ValidationException::withMessages(['amount' => 'The amount cannot be more than the order value.']);
        }
        $app->amount = $amount;
        $app->term_months = (int) $d['term_months'];
        $app->estimate = self::estimate($app->product, $amount, (int) $d['term_months']) + ['purpose' => $d['purpose'] ?? null];
    }

    /**
     * An indicative repayment, not an offer. Asset: equal monthly instalments on a reducing balance. Trade: one
     * repayment at the end of the term, a monthly fee on the amount.
     *
     * @return array{rate: float, instalment: float, count: int, total: float, cost: float, basis: string}
     */
    public static function estimate(string $product, float $amount, int $months): array
    {
        $rate = (float) config('finance_journey.products.' . $product . '.monthly_rate') / 100;
        if ($product === 'asset') {
            $instalment = $rate > 0 ? $amount * $rate / (1 - (1 + $rate) ** -$months) : $amount / $months;
            $total = round($instalment * $months, 2);

            return ['rate' => $rate * 100, 'instalment' => round($instalment, 2), 'count' => $months, 'total' => $total, 'cost' => round($total - $amount, 2), 'basis' => 'monthly'];
        }
        $cost = round($amount * $rate * $months, 2);

        return ['rate' => $rate * 100, 'instalment' => round($amount + $cost, 2), 'count' => 1, 'total' => round($amount + $cost, 2), 'cost' => $cost, 'basis' => 'end'];
    }

    /**
     * Upload documents: docs[type][] files. Checks kind and size per file, how many each type may hold and the total
     * per application. Marks the step done once every required type has a file.
     *
     * @param  array<string, UploadedFile|array<UploadedFile>>  $docs
     */
    public function saveDocuments(FinanceApplication $app, array $docs): int
    {
        if (! $app->isEditable()) {
            throw new \DomainException($app->reference . ' can no longer be changed.');
        }
        $types = config('finance_journey.documents');
        $u = config('finance_journey.uploads');
        $docs = collect($docs)->map(fn ($f) => is_array($f) ? array_values(array_filter($f)) : array_filter([$f]))->filter();
        if ($docs->isEmpty()) {
            if ($this->documentsComplete($app)) {
                $this->markDocuments($app);

                return 0;
            }
            throw ValidationException::withMessages(['docs' => 'Upload the documents marked required.']);
        }
        $rules = [];
        foreach ($docs as $type => $files) {
            if (! isset($types[$type])) {
                throw ValidationException::withMessages(['docs' => 'Unknown document type.']);
            }
            foreach (array_keys($files) as $i) {
                $rules["docs.$type.$i"] = 'file|mimes:' . implode(',', $u['mimes']) . '|max:' . $u['max_kb'];
            }
        }
        Validator::make(['docs' => $docs->all()], $rules, [
            'docs.*.*.mimes' => 'Upload a PDF, JPG or PNG.',
            'docs.*.*.max' => 'Each file can be up to ' . round($u['max_kb'] / 1024) . ' MB.',
        ])->validate();

        $have = $app->documents()->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');
        $total = (int) $have->sum();
        foreach ($docs as $type => $files) {
            if ((int) ($have[$type] ?? 0) + count($files) > $types[$type][2]) {
                throw ValidationException::withMessages(["docs.$type" => $types[$type][0] . ': up to ' . $types[$type][2] . ' file' . ($types[$type][2] > 1 ? 's' : '') . '. Remove one first.']);
            }
            $total += count($files);
        }
        if ($total > $u['max_files']) {
            throw ValidationException::withMessages(['docs' => 'An application can hold up to ' . $u['max_files'] . ' files.']);
        }

        $n = 0;
        foreach ($docs as $type => $files) {
            foreach ($files as $file) {
                $path = $file->storeAs('finance-applications/' . $app->id, Str::random(40) . '.' . ($file->guessExtension() ?: 'bin'), self::DISK);
                $app->documents()->create(['type' => $type, 'path' => $path, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 190),
                    'mime' => $file->getMimeType(), 'size' => (int) $file->getSize()]);
                $n++;
            }
        }
        if ($this->documentsComplete($app)) {
            $this->markDocuments($app);
        }

        return $n;
    }

    public function removeDocument(FinanceApplication $app, FinanceApplicationDocument $doc): void
    {
        if (! $app->isEditable() || $doc->finance_application_id !== $app->id) {
            throw new \DomainException('That document cannot be removed now.');
        }
        Storage::disk(self::DISK)->delete($doc->path);
        $doc->delete();
        if (! $this->documentsComplete($app)) {
            $app->steps_done = array_values(array_diff($app->steps_done ?? [], ['documents']));
            $app->save();
        }
    }

    /** @return array<string> required document types still missing */
    public function missingDocuments(FinanceApplication $app): array
    {
        $have = $app->documents()->pluck('type')->unique()->all();

        return collect(config('finance_journey.documents'))->filter(fn ($d) => in_array($app->product, $d[3], true))
            ->keys()->reject(fn ($t) => in_array($t, $have, true))->values()->all();
    }

    public function documentsComplete(FinanceApplication $app): bool
    {
        return $this->missingDocuments($app) === [];
    }

    private function markDocuments(FinanceApplication $app): void
    {
        $app->steps_done = array_values(array_unique(array_merge($app->steps_done ?? [], ['documents'])));
        $app->save();
    }

    private function submit(FinanceApplication $app, ?string $ip): void
    {
        $missing = collect(array_keys(config('finance_journey.steps')))->reject(fn ($s) => $s === 'review' || $app->stepDone($s));
        if ($missing->isNotEmpty()) {
            throw new \DomainException('Finish these first: ' . $missing->map(fn ($s) => config('finance_journey.steps.' . $s))->implode(', ') . '.');
        }
        if (! $this->documentsComplete($app)) {
            throw new \DomainException('Some required documents are missing.');
        }
        $again = $app->status === 'more_info';
        $app->forceFill(['status' => 'submitted', 'consent_at' => now(), 'consent_ip' => $ip, 'submitted_at' => now()]);
        $app->steps_done = array_values(array_unique(array_merge($app->steps_done ?? [], ['review'])));
        $app->log('submitted', $again ? 'Sent back with what we asked for' : null);
        AuditTrail::record('finance_applications', $app->id, 'update', ['status' => $again ? 'more_info' : 'draft'], ['status' => 'submitted']);
    }
}
