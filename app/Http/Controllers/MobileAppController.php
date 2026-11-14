<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * /app: the test APK of the mobile app, its version and how to install it (owner, 11 Nov 2026).
 * Since 14 Nov 2026 staff upload the APK on Staff › API keys; its version and notes sit in a small file beside it
 * (outside the public disk), and the env values are only the fallback.
 */
class MobileAppController extends Controller
{
    public static function path(): string
    {
        return storage_path('app/public/app/' . basename((string) config('mobile.apk.file')));
    }

    public static function metaPath(): string
    {
        return storage_path('app/mobile-apk.json');
    }

    /** The APK as it is now: null when none is up, else size, when, checksum, version and notes. */
    public static function current(): ?array
    {
        $path = self::path();
        if (! is_file($path)) {
            return null;
        }
        $meta = is_file(self::metaPath()) ? (json_decode((string) file_get_contents(self::metaPath()), true) ?: []) : [];

        return [
            'size' => filesize($path),
            'updated' => Carbon::createFromTimestamp(filemtime($path)),
            'sha256' => $meta['sha256'] ?? hash_file('sha256', $path),
            'version' => $meta['version'] ?? config('mobile.apk.version'),
            'notes' => $meta['notes'] ?? config('mobile.apk.notes'),
        ];
    }

    /**
     * Puts $source up as the APK (owner, 15 Nov 2026: shared by the API keys page and php artisan mobile:apk). The file is
     * copied in beside the old one and swapped in, then its version, notes and checksum are written next to it.
     */
    public static function put(string $source, string $version, ?string $notes, ?int $by = null, bool $move = false): array
    {
        if (! is_file($source) || (string) file_get_contents($source, false, null, 0, 2) !== 'PK') {
            throw new \DomainException('That is not an APK file.');
        }
        if (! preg_match('/^[0-9][0-9A-Za-z.+-]{0,19}$/', $version)) {
            throw new \DomainException('Give a version like 0.1.1.');
        }
        $path = self::path();
        @mkdir(dirname($path), 0775, true);
        $tmp = $path . '.uploading';
        $move ? rename($source, $tmp) : copy($source, $tmp);
        rename($tmp, $path);
        $meta = ['version' => $version, 'notes' => $notes ?: null, 'sha256' => hash_file('sha256', $path), 'size' => filesize($path),
            'uploaded_by' => $by, 'uploaded_at' => now()->toIso8601String()];
        file_put_contents(self::metaPath(), json_encode($meta, JSON_PRETTY_PRINT));
        \App\Services\AuditTrail::record('mobile_apk', 0, 'update', null, ['event' => 'uploaded', 'version' => $version, 'sha256' => $meta['sha256'], 'size' => $meta['size'], 'by' => $by]);

        return $meta;
    }

    public function show(): View
    {
        $apk = self::current();

        return view('home.white-label', [
            'apk' => $apk,
            'version' => $apk['version'] ?? config('mobile.apk.version'),
            'notes' => $apk['notes'] ?? config('mobile.apk.notes'),
        ]);
    }

    public function download(): BinaryFileResponse
    {
        $apk = self::current();
        abort_unless($apk, 404);

        return response()->download(self::path(), 'aimzone-test-' . $apk['version'] . '.apk', ['Content-Type' => 'application/vnd.android.package-archive']);
    }
}
