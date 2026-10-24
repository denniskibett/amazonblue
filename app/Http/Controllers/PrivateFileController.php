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
     * A company document (business KYC): the owner, a member with a user account, or a staff KYC reviewer.
     */
    public function business(Request $request, \App\Models\Business $business, string $document)
    {
        abort_unless($business->isVisibleTo($request->user()), 404);
        // a director's ID pictures (owner, 24 Oct 2026): staff reviewers only, not the business owner
        $doc = self::isStaffReviewer($request->user())
            ? \App\Models\BusinessDocument::where('business_id', $business->id)->whereKey((int) $document)->first()
            : $business->documents()->whereKey((int) $document)->first();
        $path = PrivateFiles::absolute($doc?->path);
        abort_unless($path, 404);

        $name = \Illuminate\Support\Str::slug($doc->type) . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="' . $name . '"',
        ]);
    }

    /**
     * Staff who may open KYC documents: `edit users` or `view kyc documents`.
     * `view users` and `view loans` are not enough (brokers hold `view loans`).
     */
    public static function isStaffReviewer(User $viewer): bool
    {
        return $viewer->hasAnyRole(User::STAFF_ROLES)
            && ($viewer->hasRole('super-admin') || $viewer->canAny(['edit users', 'view kyc documents']));
    }
}
