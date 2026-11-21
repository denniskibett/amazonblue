<?php

namespace App\Services\Loans;

use App\Http\Controllers\HomeController;
use App\Models\Loan;
use App\Models\LoanGuarantee;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Sms\SmsSender;
use App\Support\Phone;
use App\Support\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarantor sign-off (owner, 21 Nov 2026): "add extra details for guarantors such as an open sign area for release doc
 * for borrower to be given money and upload documents such as id. streamline it".
 *
 * When a borrower applies, the guarantor they gave at KYC is texted a link (no account, no login: the link is the key,
 * only its hash is kept). On their phone they read the guarantee and release for this loan, add their ID number, date
 * of birth, where they live and what they do, photograph their ID and sign on screen. Until they sign, no money goes
 * out on the loan (disbursement, M-Pesa and Sharet payouts all refuse). A manager who can approve loans may waive it,
 * with a reason (e.g. signed on paper in the office); it is audited.
 *
 * Loans from before this existed, and borrowers with no guarantor on file, have no row and are not held.
 * A guarantor who signed for this borrower before keeps their ID on file: next time they only sign.
 */
class Guarantees
{
    /** Open loans the guarantee still matters for: once paid out, rejected or closed it is no longer asked for. */
    public const OPEN = [Loan::STATUS_PENDING, Loan::STATUS_APPROVED];

    /** Off until database/sql/2026_11_21_guarantors_and_kin.sql has been run: nothing is held or shown before then. */
    public static function available(): bool
    {
        static $ok = null;

        return $ok ??= \Illuminate\Support\Facades\Schema::hasTable('loan_guarantees');
    }

    public function forLoan(Loan $loan): ?LoanGuarantee
    {
        return self::available() ? LoanGuarantee::where('loan_id', $loan->id)->first() : null;
    }

    /**
     * Start the guarantee for a new application from the borrower's KYC guarantor, and text them the link.
     * Nothing happens when the borrower has no guarantor on file. Never breaks the application.
     */
    public function open(Loan $loan, ?User $by = null, bool $send = true): ?LoanGuarantee
    {
        try {
            if (! self::available() || ! in_array($loan->status, self::OPEN, true) || ($row = $this->forLoan($loan))) {
                return $row ?? null;
            }
            $contact = $loan->user?->contacts()->where('relationship_specific', HomeController::GUARANTOR)->latest('id')->first();
            if (! $contact || ! Phone::normal((string) $contact->phone)) {
                return null;
            }
            $row = LoanGuarantee::create([
                'loan_id' => $loan->id,
                'borrower_id' => $loan->user_id,
                'contact_id' => $contact->id,
                'status' => LoanGuarantee::WAITING,
                'name' => $contact->name,
                'phone' => Phone::normal((string) $contact->phone),
                'email' => $contact->email ?: null,
                'relationship' => $contact->contact_type_id ? DB::table('contact_types')->where('id', $contact->contact_type_id)->value('name') : null,
            ]);
            AuditTrail::record('loan_guarantees', $row->id, 'create', null, ['loan_id' => $loan->id, 'by' => $by?->id]);
            if ($send) {
                $this->send($row, $by);
            }

            return $row;
        } catch (\Throwable $e) {
            Log::error('Guarantee could not be opened for loan #' . $loan->id, ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** A fresh link (the old one stops working), texted to the guarantor. Returns the link. */
    public function send(LoanGuarantee $row, ?User $by = null): string
    {
        if ($row->released()) {
            throw new \DomainException($row->name . ' has already signed for this loan.');
        }
        if (! in_array($row->loan?->status, self::OPEN, true)) {
            throw new \DomainException('This loan is no longer waiting for its money.');
        }
        $token = Str::random(48);
        $expires = now()->addDays((int) config('lending.guarantors.link_days', 14));
        $row->forceFill(['token_hash' => hash('sha256', $token), 'link_sent_at' => now(), 'link_expires_at' => $expires])->save();
        $url = route('guarantor.sign', $token);
        $loan = $row->loan;

        $borrower = $loan->user?->name ?? 'A borrower';
        app(SmsSender::class)->send($row->phone, 'Aimzone: ' . $borrower . ' named you guarantor for a loan of KES '
            . number_format((float) $loan->amount) . '. Please read and sign before we pay it out: ' . $url);
        // the same link by email when we have one (owner, 21 Nov 2026); a mail failure never stops the SMS
        if ($row->email) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $row->email)->notify(new \App\Notifications\GuarantorSignLink(
                    $row->firstName(), $borrower, (float) $loan->amount, $url, $expires->format('j M Y')));
            } catch (\Throwable $e) {
                Log::warning('Guarantor link email not sent for guarantee #' . $row->id, ['error' => $e->getMessage()]);
            }
        }
        AuditTrail::record('loan_guarantees', $row->id, 'update', null, ['event' => 'link_sent', 'by' => $by?->id, 'expires' => $expires->toDateString()]);

        return $url;
    }

    /** The guarantee a link belongs to, while it still works and the loan is still waiting. */
    public function find(string $token): ?LoanGuarantee
    {
        if (strlen($token) < 40 || ! self::available()) {
            return null;
        }
        $row = LoanGuarantee::with(['loan.user', 'loan.loanType'])->where('token_hash', hash('sha256', $token))->first();

        return $row && $row->status === LoanGuarantee::WAITING && $row->link_expires_at?->isFuture()
            && in_array($row->loan?->status, self::OPEN, true) ? $row : null;
    }

    /** The last guarantee this person signed for the same borrower: their ID is reused, so they only sign. */
    public function previous(LoanGuarantee $row): ?LoanGuarantee
    {
        return LoanGuarantee::where('borrower_id', $row->borrower_id)->where('phone', $row->phone)->where('status', LoanGuarantee::SIGNED)
            ->where('id', '!=', $row->id)->whereNotNull('id_front_path')->latest('signed_at')->first();
    }

    /**
     * The guarantee and release, in plain words: what the guarantor reads and signs. Its text and hash are kept.
     *
     * @return array<int, string>
     */
    public function terms(LoanGuarantee $row, ?string $idNumber = null): array
    {
        $loan = $row->loan;
        $borrower = $loan->user?->name ?? 'the borrower';
        $first = strtok($borrower, ' ') ?: $borrower;
        $cycle = $loan->cycles()->orderBy('cycle_number')->first();
        $total = (float) ($cycle->new_balance ?? $loan->amount);
        $due = $cycle?->due_date ?? $loan->due_date;
        $ref = 'Loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT);

        return [
            'I, ' . $row->name . ($idNumber ? ', ID or passport ' . $idNumber : '') . ', guarantee ' . $ref . ': KES ' . number_format((float) $loan->amount, 2)
                . ' that Aimzone Ventures is lending to ' . $borrower . ', to be repaid in full (KES ' . number_format($total, 2) . ' with interest)'
                . ($due ? ' by ' . Carbon::parse($due)->format('j F Y') : '') . '.',
            'If ' . $first . ' does not repay on time, Aimzone may contact me and ask me to pay what is still owed on this loan, including any late charges in its loan agreement.',
            'I know ' . $first . ', I am guaranteeing this loan freely, and the details and ID I give here are my own and true.',
            'I agree that Aimzone may now release the money to ' . $first . '.',
            'Aimzone may keep my details, ID and signature to confirm who I am and to recover this loan, as the Data Protection Act, 2019 allows.',
        ];
    }

    /**
     * The guarantor signs. $data: id_number, dob, residence, occupation, email (optional).
     * $files: id_front, id_back (optional for a passport); both may be skipped when a previous guarantee has them.
     */
    public function complete(LoanGuarantee $row, array $data, array $files, string $signaturePng, ?string $ip = null, ?string $agent = null): void
    {
        $previous = $this->previous($row);
        if (empty($files['id_front']) && ! $previous) {
            throw new \DomainException('Add a photo of your ID (front) or passport.');
        }
        $png = $this->decodeSignature($signaturePng);
        $idNumber = strtoupper(preg_replace('/\s+/', '', (string) $data['id_number']));
        if ($previous && $previous->id_number && strcasecmp($previous->id_number, $idNumber) !== 0 && empty($files['id_front'])) {
            throw new \DomainException('That ID number is not the one you gave last time. Add a photo of your ID again.');
        }
        $borrower = User::find($row->borrower_id);
        if ($borrower && $borrower->id_number && strcasecmp(preg_replace('/\s+/', '', (string) $borrower->id_number), $idNumber) === 0) {
            throw new \DomainException('A guarantor must be someone other than the borrower.');
        }
        $terms = implode("\n\n", $this->terms($row, $idNumber));

        DB::transaction(function () use ($row, $data, $files, $png, $previous, $idNumber, $terms, $ip, $agent) {
            $folder = 'guarantors/' . $row->loan_id;
            $store = fn (UploadedFile $f, string $kind) => $f->storeAs($folder, $kind . '_' . Str::random(32) . '.' . ($f->guessExtension() ?: 'jpg'), PrivateFiles::DISK);
            $sig = $folder . '/signature_' . Str::random(32) . '.png';
            Storage::disk(PrivateFiles::DISK)->put($sig, $png);

            $row->forceFill([
                'status' => LoanGuarantee::SIGNED,
                'id_number' => $idNumber,
                'dob' => $data['dob'],
                'residence' => $data['residence'],
                'occupation' => $data['occupation'],
                'email' => $data['email'] ?? null,
                'id_front_path' => ! empty($files['id_front']) ? $store($files['id_front'], 'id_front') : $previous->id_front_path,
                'id_back_path' => ! empty($files['id_back']) ? $store($files['id_back'], 'id_back') : (! empty($files['id_front']) ? null : $previous->id_back_path),
                'signature_path' => $sig,
                'terms' => $terms,
                'terms_sha256' => hash('sha256', $terms),
                'signed_at' => now(),
                'signed_ip' => $ip,
                'signed_agent' => mb_substr((string) $agent, 0, 255),
                'token_hash' => null,
                'link_expires_at' => null,
            ])->save();
            AuditTrail::record('loan_guarantees', $row->id, 'update', ['status' => LoanGuarantee::WAITING],
                ['status' => LoanGuarantee::SIGNED, 'how' => 'guarantor link', 'ip' => $ip, 'terms_sha256' => $row->terms_sha256]);
        });
    }

    /** A manager lets the money go without the online signature (e.g. signed on paper in the office). */
    public function waive(LoanGuarantee $row, User $by, string $reason): void
    {
        if ($row->released()) {
            throw new \DomainException('This guarantee is already ' . $row->status . '.');
        }
        $row->forceFill(['status' => LoanGuarantee::WAIVED, 'waived_by' => $by->id, 'waived_reason' => $reason, 'waived_at' => now(),
            'token_hash' => null, 'link_expires_at' => null])->save();
        AuditTrail::record('loan_guarantees', $row->id, 'update', ['status' => LoanGuarantee::WAITING],
            ['status' => LoanGuarantee::WAIVED, 'by' => $by->id, 'reason' => $reason]);
    }

    /** Refuses to pay out a loan whose guarantor has not signed. Loans with no guarantee row are not held. */
    public function assertReleased(Loan $loan): void
    {
        $row = $this->forLoan($loan);
        if ($row && ! $row->released()) {
            throw new \DomainException('The guarantor, ' . $row->name . ', has not signed yet. Send them the link from the loan page, or a manager can waive it.');
        }
    }

    /** For the borrower's screens and the app. */
    public function summary(Loan $loan): ?array
    {
        $row = $this->forLoan($loan);
        if (! $row) {
            return null;
        }

        return [
            'status' => $row->status,
            'name' => $row->name,
            'phone' => self::masked($row->phone),
            'emailed' => (bool) $row->email,
            'signed_at' => $row->signed_at?->toIso8601String(),
            'link_sent_at' => $row->link_sent_at?->toIso8601String(),
            'waiting' => $row->status === LoanGuarantee::WAITING && in_array($loan->status, self::OPEN, true),
        ];
    }

    /** +254712345678 → +2547•••678: enough for the borrower to recognise, not to copy. */
    public static function masked(?string $phone): string
    {
        $phone = (string) $phone;

        return strlen($phone) > 7 ? substr($phone, 0, 5) . '•••' . substr($phone, -3) : $phone;
    }

    private function decodeSignature(string $data): string
    {
        if (! str_starts_with($data, 'data:image/png;base64,')) {
            throw new \DomainException('Sign in the box with your finger or mouse.');
        }
        $png = base64_decode(substr($data, 22), true);
        $size = $png !== false ? @getimagesizefromstring($png) : false;
        if (! $size || ($size['mime'] ?? '') !== 'image/png' || strlen($png) > 1024 * 1024) {
            throw new \DomainException('We could not read your signature. Clear the box and sign again.');
        }

        return $png;
    }
}
