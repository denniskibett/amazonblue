<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PrivateFiles;
use Illuminate\Http\Request;

/**
 * Serves a user's personal documents (ID images, passport photo, signature) from the private disk.
 * Only the owner and staff who handle customers may see them; anyone else gets a 404.
 */
class PrivateFileController extends Controller
{
    public function user(Request $request, User $user, string $document)
    {
        $viewer = $request->user();
        abort_unless($viewer->id === $user->id || self::isStaffReviewer($viewer), 404);

        $column = PrivateFiles::USER_DOCUMENTS[$document] ?? null;
        $path = PrivateFiles::absolute($column ? $user->{$column} : null);
        abort_unless($path, 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="' . $document . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"',
        ]);
    }

    /**
     * Staff who work with customer records (users, KYC reviews or loans).
     */
    public static function isStaffReviewer(User $viewer): bool
    {
        return $viewer->hasAnyRole(User::STAFF_ROLES)
            && ($viewer->hasRole('super-admin') || $viewer->canAny(['view users', 'edit users', 'view loans']));
    }
}
