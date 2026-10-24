<?php

namespace App\Services\Business;

use App\Models\Business;
use App\Models\BusinessDocument;
use App\Models\BusinessMember;
use App\Models\User;
use App\Notifications\DirectorVerificationLink;
use App\Services\AuditTrail;
use App\Services\Sms\SmsSender;
use App\Support\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Director self-verification links (owner, 23 Oct 2026; plan default "director KYC before approval").
 *
 * Every owner, director and signatory of a business verifies themselves before the business is approved for loans.
 * Someone with an approved individual KYC on our system already counts. Everyone else gets a one-off link (by email
 * and SMS) that works for lending.business_loans.link_days days: they confirm their ID number and date of birth and
 * upload their ID and a selfie, which are stored as business documents tied to them (staff only). Only a hash of the
 * link's token is kept (business_members.verify_token_hash).
 */
class DirectorVerification
{
    public const ROLES = ['owner', 'director'];

    /** Members who must be verified: owners, directors and anyone who signs. */
    public function required(Business $business): Collection
    {
        return $business->members()->with('user')->get()->filter(fn (BusinessMember $m) => in_array($m->role, self::ROLES, true) || $m->is_signatory)->values();
    }

    public function isVerified(BusinessMember $m): bool
    {
        return $m->verified_at !== null || ($m->user && $m->user->kyc_status === 'approved');
    }

    public function unverified(Business $business): Collection
    {
        return $this->required($business)->reject(fn ($m) => $this->isVerified($m))->values();
    }

    /** Make a new link for the member and send it; returns the link (staff and the owner can also copy it). */
    public function send(BusinessMember $m, ?User $by = null): string
    {
        if ($this->isVerified($m)) {
            throw new \DomainException($m->full_name . ' is already verified.');
        }
        if (! $m->email && ! $m->phone) {
            throw new \DomainException('Add an email or phone number for ' . $m->full_name . ' first.');
        }
        $token = Str::random(48);
        $expires = now()->addDays((int) config('lending.business_loans.link_days', 7));
        $m->forceFill(['verify_token_hash' => hash('sha256', $token), 'verify_sent_at' => now(), 'verify_expires_at' => $expires])->save();
        $url = route('directors.verify', $token);
        $business = $m->business;

        if ($m->email) {
            Notification::route('mail', $m->email)->notify(new DirectorVerificationLink($m->full_name, $business->legal_name, $url, $expires->format('j M Y')));
        }
        if ($m->phone) {
            app(SmsSender::class)->send($m->phone, 'Aimzone: ' . $business->legal_name . ' listed you as a director. Verify yourself here: ' . $url);
        }
        AuditTrail::record('business_members', $m->id, 'update', null, ['event' => 'verification_link_sent', 'by' => $by?->id, 'expires' => $expires->toDateString()]);

        return $url;
    }

    /** The member a link belongs to, if it is still good. */
    public function find(string $token): ?BusinessMember
    {
        if (strlen($token) < 40) {
            return null;
        }
        $m = BusinessMember::with('business')->where('verify_token_hash', hash('sha256', $token))->first();

        return $m && $m->verify_expires_at && $m->verify_expires_at->isFuture() && ! $m->verified_at ? $m : null;
    }

    /**
     * The director finishes: their ID number must match what the business entered (when it entered one).
     *
     * @param  array{id_number: string, dob: string}  $data
     * @param  array<string, UploadedFile>  $files  director_id_front, director_id_back (optional for a passport), director_selfie
     */
    public function complete(BusinessMember $m, array $data, array $files, ?string $ip = null): void
    {
        $id = preg_replace('/\s+/', '', (string) $data['id_number']);
        if ($m->id_number && strcasecmp(preg_replace('/\s+/', '', $m->id_number), $id) !== 0) {
            throw new \DomainException('That ID number does not match what the business gave us. Check it, or ask them to correct it.');
        }
        foreach (['director_id_front', 'director_selfie'] as $required) {
            if (empty($files[$required])) {
                throw new \DomainException('Add a photo of your ID and a selfie.');
            }
        }

        DB::transaction(function () use ($m, $id, $data, $files, $ip) {
            foreach ($files as $type => $file) {
                if (! $file instanceof UploadedFile || ! array_key_exists($type, BusinessDocument::DIRECTOR_TYPES)) {
                    continue;
                }
                $path = $file->storeAs('business-documents/' . $m->business_id, 'director_' . $m->id . '_' . Str::random(24) . '.' . ($file->guessExtension() ?: 'jpg'), PrivateFiles::DISK);
                BusinessDocument::create(['business_id' => $m->business_id, 'business_member_id' => $m->id, 'type' => $type, 'path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200), 'mime' => $file->getMimeType(), 'size' => $file->getSize()]);
            }
            $m->forceFill(['id_number' => $m->id_number ?: $id, 'verified_at' => now(), 'verify_token_hash' => null, 'verify_expires_at' => null])->save();
            AuditTrail::record('business_members', $m->id, 'update', ['verified_at' => null], ['verified_at' => now()->toDateTimeString(), 'how' => 'self-verification link', 'dob' => $data['dob'], 'ip' => $ip]);
        });
    }
}
