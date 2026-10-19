<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Personal documents (KYC ID images, passport photos, signatures, signed loan agreements) live on the
 * private disk (storage/app/private), never under /storage. They are served only through authorised
 * routes (files.user, account.agreement.file, loans.agreement.*).
 *
 * Paths in the database are relative to the disk. Files saved before 18 Oct 2026 may still be on the
 * public disk until `php artisan files:make-private` has moved them, so reads check both.
 */
class PrivateFiles
{
    public const DISK = 'local';

    // users columns that hold personal documents, by the name used in files.user URLs
    public const USER_DOCUMENTS = [
        'id-front' => 'id_front_path',
        'id-back' => 'id_back_path',
        'photo' => 'avatar',
        'signature' => 'signature',
    ];

    /**
     * Save an upload privately as {folder}/{user id}_{random}.{ext}: unguessable, and two people never share a file.
     */
    public static function storeUpload(UploadedFile $file, string $folder, User $user): string
    {
        $name = $user->id . '_' . Str::random(32) . '.' . ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');

        return $file->storeAs($folder, $name, self::DISK);
    }

    /**
     * Save raw content (a drawn signature, a generated PDF) privately under a fresh unguessable name.
     */
    public static function putNew(string $folder, User $user, string $extension, string $contents): string
    {
        $path = $folder . '/' . $user->id . '_' . Str::random(32) . '.' . $extension;
        if (! Storage::disk(self::DISK)->put($path, $contents)) {
            throw new \RuntimeException('Could not save the file.');
        }

        return $path;
    }

    /**
     * Absolute path of a stored document, private disk first, then the public disk (not yet moved). Null if missing.
     */
    public static function absolute(?string $path): ?string
    {
        if (! $path || str_contains($path, '..') || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }
        $path = ltrim(preg_replace('#^storage/#', '', $path), '/');

        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->path($path);
            }
        }

        return null;
    }

    public static function exists(?string $path): bool
    {
        return self::absolute($path) !== null;
    }

    /**
     * Remove a replaced document from whichever disk holds it.
     */
    public static function delete(?string $path): void
    {
        if (! $path || str_contains($path, '..')) {
            return;
        }
        foreach ([self::DISK, 'public'] as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    /**
     * URL of one of a user's documents (through the authorised route), or null when none is on record.
     */
    public static function userUrl(User $user, string $document): ?string
    {
        $column = self::USER_DOCUMENTS[$document] ?? null;
        $path = $column ? $user->{$column} : null;
        if (! $path) {
            return null;
        }

        // a short fingerprint so a replaced file isn't served from the browser cache
        return route('files.user', ['user' => $user->id, 'document' => $document, 'v' => substr(md5($path), 0, 8)]);
    }
}
