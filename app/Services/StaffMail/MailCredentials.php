<?php

namespace App\Services\StaffMail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Where a staff member's mailbox password lives between requests (owner's choice, 16 Nov 2026): typed once per
 * app login, never in the database or the logs. It is split in two:
 *   - the session (our database) holds only AES-256-GCM ciphertext, bound to the user id and mailbox address;
 *   - the key is random per unlock and lives only in the browser, in an HttpOnly, SameSite=Strict session cookie
 *     scoped to /staff/mail.
 * Neither half opens the mailbox alone: a copy of our sessions table plus APP_KEY is not enough without the
 * staff member's browser, and a stolen cookie is useless without our session. It is dropped on logout, on
 * "Lock mailbox", when the browser closes, and after config('staffmail.idle_minutes') of not using mail.
 */
class MailCredentials
{
    public const COOKIE = 'staff_mail_key';
    public const SESSION = 'staff_mail';
    private const CIPHER = 'aes-256-gcm';

    public function store(Request $request, string $email, string $password): void
    {
        $key = random_bytes(32);
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($password, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, $this->aad($request, $email));

        $request->session()->put(self::SESSION, [
            'email' => $email,
            'user_id' => $request->user()->id,
            'blob' => base64_encode($iv . $tag . $ct),
            'at' => now()->getTimestamp(),
        ]);
        Cookie::queue(Cookie::make(self::COOKIE, base64_encode($key), 0, $this->path(), null,
            $request->isSecure() || config('session.secure'), true, false, 'strict'));
        sodium_memzero($key);
    }

    /** The password for the signed-in user's mailbox, or null when it is locked. Refreshes the idle timer. */
    public function password(Request $request): ?string
    {
        $s = $request->session()->get(self::SESSION);
        $key = base64_decode((string) $request->cookie(self::COOKIE), true);
        if (! is_array($s) || ! $key || strlen($key) !== 32 || ($s['user_id'] ?? null) !== $request->user()?->id
            || strcasecmp((string) ($s['email'] ?? ''), (string) $request->user()?->email) !== 0) {
            return null;
        }
        if (now()->getTimestamp() - (int) ($s['at'] ?? 0) > (int) config('staffmail.idle_minutes', 60) * 60) {
            $this->forget($request);

            return null;
        }
        $raw = base64_decode((string) $s['blob'], true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }
        $plain = openssl_decrypt(substr($raw, 28), self::CIPHER, $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $this->aad($request, $s['email']));
        if ($plain === false) {
            return null;
        }
        $s['at'] = now()->getTimestamp();
        $request->session()->put(self::SESSION, $s);

        return $plain;
    }

    public function forget(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget(self::SESSION);
        }
        Cookie::queue(Cookie::forget(self::COOKIE, $this->path()));
    }

    private function aad(Request $request, string $email): string
    {
        return 'staff-mail|' . $request->user()->id . '|' . strtolower($email);
    }

    private function path(): string
    {
        return '/' . trim(parse_url(route('staff.mail.index', [], false), PHP_URL_PATH) ?: 'staff/mail', '/');
    }
}
