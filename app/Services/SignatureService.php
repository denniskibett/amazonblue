<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SignatureService
{
    public function saveSignature($signatureData, User $user)
    {
        try {
            Log::debug('SignatureService: Starting signature save process', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'signature_data_length' => strlen($signatureData),
                'signature_data_preview' => substr($signatureData, 0, 50) . '...'
            ]);

            // Generate consistent filename: signature_{user_name}.png
            $filename = 'signature_' . preg_replace('/[^a-zA-Z0-9]/', '_', $user->name) . '.png';
            $path = 'images/signatures/' . $filename;

            Log::debug('SignatureService: Generated filename and path', [
                'filename' => $filename,
                'path' => $path
            ]);

            // Check if file exists and delete it (overwrite behavior)
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::debug('SignatureService: Existing signature file deleted', ['filename' => $filename]);
            }

            // Save the signature image
            $signatureData = preg_replace('#^data:image/\w+;base64,#i', '', $signatureData);
            $decodedData = base64_decode($signatureData);
            
            Log::debug('SignatureService: Decoded signature data', [
                'decoded_data_length' => strlen($decodedData),
                'is_valid_base64' => base64_decode($signatureData, true) !== false
            ]);

            // Save to storage
            $saveResult = Storage::disk('public')->put($path, $decodedData);
            
            Log::debug('SignatureService: Storage save result', [
                'save_result' => $saveResult,
                'file_exists' => Storage::disk('public')->exists($path),
                'file_size' => $saveResult ? Storage::disk('public')->size($path) : 0
            ]);

            if (!$saveResult) {
                throw new \Exception('Failed to save signature file to storage');
            }

            // Update user's signature reference
            $updateResult = $user->update(['signature' => $path]);
            
            Log::debug('SignatureService: User update result', [
                'update_result' => $updateResult,
                'new_signature_field' => $user->fresh()->signature,
                'user_updated' => $user->wasChanged('signature')
            ]);

            // Get full URL for the saved signature
            $signatureUrl = asset('storage/' . $path);
            
            Log::debug('SignatureService: Signature saved successfully', [
                'filename' => $filename,
                'full_url' => $signatureUrl,
                'storage_path' => $path
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $path,
                'url' => $signatureUrl,
                'user_id' => $user->id,
                'timestamp' => now()->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error('SignatureService: Error saving signature', [
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'filename' => null,
                'path' => null,
                'url' => null
            ];
        }
    }

public function getUserSignatureUrl($userId)
{
    try {
        $user = User::find($userId);
        if (!$user || !$user->signature) {
            Log::debug('SignatureService: No signature found for user', ['user_id' => $userId]);
            return null;
        }

        $path = $user->signature; // Already includes 'images/signatures/filename.png'
        if (Storage::disk('public')->exists($path)) {
            $url = Storage::disk('public')->url($path);
            Log::debug('SignatureService: Retrieved signature URL', [
                'user_id' => $userId,
                'path' => $path,
                'url' => $url
            ]);
            return $url;
        }

        Log::warning('SignatureService: Signature file not found in storage', [
            'user_id' => $userId,
            'path' => $path
        ]);

        return null;
    } catch (\Exception $e) {
        Log::error('SignatureService: Error getting signature URL', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

public function checkSignature(User $user)
{
    try {
        $hasSignature = !empty($user->signature);
        $signatureUrl = null;

        if ($hasSignature) {
            $path = $user->signature;

            Log::debug('SignatureService: Checking signature file', [
                'user_id' => $user->id,
                'path' => $path,
                'file_exists' => Storage::disk('public')->exists($path),
                'all_signature_files' => collect(Storage::disk('public')->files('images/signatures'))->toArray()
            ]);

            if (Storage::disk('public')->exists($path)) {
                $signatureUrl = Storage::disk('public')->url($path);
                Log::debug('SignatureService: Signature file found', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'url' => $signatureUrl
                ]);
            } else {
                Log::warning('SignatureService: Signature file missing, clearing DB record', [
                    'user_id' => $user->id,
                    'path' => $path
                ]);
                $user->update(['signature' => null]);
                $hasSignature = false;
            }
        }

        return [
            'hasSignature' => $hasSignature,
            'signatureUrl' => $signatureUrl,
            'filename' => $user->signature
        ];
    } catch (\Exception $e) {
        Log::error('SignatureService: Error checking signature', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        return [
            'hasSignature' => false,
            'signatureUrl' => null,
            'filename' => null
        ];
    }
}

public function deleteSignature(User $user)
{
    try {
        $path = $user->signature;

        if ($path && Storage::disk('public')->exists($path)) {
            $deleteResult = Storage::disk('public')->delete($path);
            Log::debug('SignatureService: Signature file deleted', [
                'user_id' => $user->id,
                'path' => $path,
                'delete_result' => $deleteResult
            ]);
        } elseif ($path) {
            Log::warning('SignatureService: Signature file not found for deletion', [
                'user_id' => $user->id,
                'path' => $path
            ]);
        }

        $user->update(['signature' => null]);

        Log::debug('SignatureService: Signature DB record cleared', [
            'user_id' => $user->id
        ]);

        return [
            'success' => true,
            'deleted_path' => $path,
            'user_id' => $user->id
        ];
    } catch (\Exception $e) {
        Log::error('SignatureService: Error deleting signature', [
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

}