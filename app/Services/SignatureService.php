<?php

namespace App\Services;

use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Support\Facades\Log;

/**
 * Drawn signatures (PNG data URLs from the signature pad). Saved on the private disk as
 * signatures/{user id}_{random}.png, so names can't be guessed and two people with the same name
 * never overwrite each other. Shown only through the authorised files.user route.
 */
class SignatureService
{
    public function saveSignature($signatureData, User $user)
    {
        try {
            $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', (string) $signatureData), true);
            if ($decoded === false || $decoded === '') {
                throw new \Exception('The signature image is not valid.');
            }

            $old = $user->signature;
            $path = PrivateFiles::putNew('signatures', $user, 'png', $decoded);
            $user->update(['signature' => $path]);
            if ($old && $old !== $path) {
                PrivateFiles::delete($old);
            }

            return [
                'success' => true,
                'filename' => basename($path),
                'path' => $path,
                'url' => $user->documentUrl('signature'),
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('SignatureService: Error saving signature', [
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'filename' => null,
                'path' => null,
                'url' => null,
            ];
        }
    }

    public function getUserSignatureUrl($userId)
    {
        $user = User::find($userId);

        return $user && PrivateFiles::exists($user->signature) ? $user->documentUrl('signature') : null;
    }

    public function checkSignature(User $user)
    {
        try {
            $hasSignature = ! empty($user->signature);
            $fileExists = $hasSignature && PrivateFiles::exists($user->signature);

            return [
                'hasSignature' => $hasSignature,
                'signatureExists' => $fileExists,
                'signatureUrl' => $fileExists ? $user->documentUrl('signature') : null,
                'filename' => $user->signature,
                'path' => $hasSignature ? $user->signature : null,
            ];
        } catch (\Exception $e) {
            Log::error('SignatureService: Error checking signature', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'hasSignature' => false,
                'signatureExists' => false,
                'signatureUrl' => null,
                'filename' => null,
                'path' => null,
            ];
        }
    }

    public function deleteSignature(User $user)
    {
        try {
            $path = $user->signature;
            PrivateFiles::delete($path);
            $user->update(['signature' => null]);

            return [
                'success' => true,
                'deleted_path' => $path,
                'user_id' => $user->id,
            ];
        } catch (\Exception $e) {
            Log::error('SignatureService: Error deleting signature', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
