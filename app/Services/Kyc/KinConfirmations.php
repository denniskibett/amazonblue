<?php

namespace App\Services\Kyc;

use App\Models\KinConfirmation;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Sms\SmsSender;
use App\Support\Phone;
use App\Support\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Next of kin proves they are the kin (owner, 21 Nov 2026: "and also for kin to prove they're the kin").
 *
 * When a borrower submits KYC, the next of kin they gave is texted a link (no login; only the token's hash is kept).
 * They say whether they know the client and how, give their ID number and a photo of their ID. "I don't know this
 * person" is recorded as denied and shown to staff in red. A confirmed answer counts as the next-of-kin check on
 * the KYC review, so staff need not also call.
 */
class KinConfirmations
{
    public const ANSWERS = ['yes' => 'Yes, I know them', 'no' => 'No, I don\'t know this person'];

    public static function available(): bool
    {
        static $ok = null;

        return $ok ??= Schema::hasTable('kin_confirmations');
    }

    /** The latest one for the client's current next of kin (a changed kin phone starts afresh). */
    public function latest(User $user): ?KinConfirmation
    {
        if (! self::available()) {
            return null;
        }

        return KinConfirmation::where('user_id', $user->id)->where('phone', (string) Phone::normal((string) $user->kin_phone))->latest('id')->first();
    }

    /** Start it for the client's next of kin and text the link. Never breaks the caller. */
    public function open(User $user, ?User $by = null, bool $send = true): ?KinConfirmation
    {
        try {
            $phone = Phone::normal((string) $user->kin_phone);
            if (! self::available() || ! $phone || ! $user->kin_name) {
                return null;
            }
            $row = $this->latest($user);
            if ($row && $row->status !== KinConfirmation::WAITING) {
                return $row;
            }
            $row ??= KinConfirmation::create(['user_id' => $user->id, 'status' => KinConfirmation::WAITING, 'name' => $user->kin_name, 'phone' => $phone, 'relation' => $user->kin_relation]);
            if ($send) {
                $this->send($row, $by);
            }

            return $row;
        } catch (\Throwable $e) {
            Log::error('Kin confirmation could not be opened for user #' . $user->id, ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function send(KinConfirmation $row, ?User $by = null): string
    {
        if ($row->status !== KinConfirmation::WAITING) {
            throw new \DomainException($row->name . ' has already answered.');
        }
        $token = Str::random(48);
        $expires = now()->addDays((int) config('lending.guarantors.link_days', 14));
        $row->forceFill(['token_hash' => hash('sha256', $token), 'link_sent_at' => now(), 'link_expires_at' => $expires])->save();
        $url = route('kin.confirm', $token);
        app(SmsSender::class)->send($row->phone, 'Aimzone: ' . ($row->user?->name ?? 'A client') . ' gave you as their next of kin. Please confirm you know them: ' . $url);
        AuditTrail::record('kin_confirmations', $row->id, 'update', null, ['event' => 'link_sent', 'by' => $by?->id, 'expires' => $expires->toDateString()]);

        return $url;
    }

    public function find(string $token): ?KinConfirmation
    {
        if (strlen($token) < 40 || ! self::available()) {
            return null;
        }
        $row = KinConfirmation::with('user')->where('token_hash', hash('sha256', $token))->first();

        return $row && $row->status === KinConfirmation::WAITING && $row->link_expires_at?->isFuture() ? $row : null;
    }

    /**
     * $data: answer (yes|no), relation (when yes), id_number (when yes). $files: id_front (when yes), id_back.
     */
    public function complete(KinConfirmation $row, array $data, array $files, ?string $ip = null, ?string $agent = null): void
    {
        $yes = $data['answer'] === 'yes';
        $idNumber = $yes ? strtoupper(preg_replace('/\s+/', '', (string) $data['id_number'])) : null;
        if ($yes && $row->user?->id_number && strcasecmp(preg_replace('/\s+/', '', (string) $row->user->id_number), $idNumber) === 0) {
            throw new \DomainException('Enter your own ID number, not ' . strtok((string) $row->user->name, ' ') . '\'s.');
        }

        DB::transaction(function () use ($row, $data, $files, $yes, $idNumber, $ip, $agent) {
            $store = fn (UploadedFile $f, string $kind) => $f->storeAs('kin/' . $row->user_id, $kind . '_' . Str::random(32) . '.' . ($f->guessExtension() ?: 'jpg'), PrivateFiles::DISK);
            $row->forceFill([
                'status' => $yes ? KinConfirmation::CONFIRMED : KinConfirmation::DENIED,
                'stated_relation' => $yes ? $data['relation'] : null,
                'id_number' => $idNumber,
                'id_front_path' => $yes && ! empty($files['id_front']) ? $store($files['id_front'], 'id_front') : null,
                'id_back_path' => $yes && ! empty($files['id_back']) ? $store($files['id_back'], 'id_back') : null,
                'answered_at' => now(), 'answered_ip' => $ip, 'answered_agent' => mb_substr((string) $agent, 0, 255),
                'token_hash' => null, 'link_expires_at' => null,
            ])->save();
            AuditTrail::record('kin_confirmations', $row->id, 'update', ['status' => KinConfirmation::WAITING], ['status' => $row->status, 'ip' => $ip]);
        });
    }
}
