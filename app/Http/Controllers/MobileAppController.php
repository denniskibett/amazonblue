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

    public function show(): View
    {
        $apk = self::current();

        return view('home.app', [
            'apk' => $apk,
            'version' => $apk['version'] ?? config('mobile.apk.version'),
            'notes' => $apk['notes'] ?? config('mobile.apk.notes'),
        ]);
    }

    public function download(): BinaryFileResponse
    {
        $apk = self::current();
        abort_unless($apk, 404);

        return response()->download(self::path(), 'aimzone-' . $apk['version'] . '.apk', ['Content-Type' => 'application/vnd.android.package-archive']);
    }
}
