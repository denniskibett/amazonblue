<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff mail (owner, 16 Nov 2026) is only for staff whose account email is on the mail domain; for anyone else
 * the pages do not exist (404). Mail pages are never stored by the browser or a proxy.
 */
class EnsureStaffMail
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canUseStaffMail()) {
            abort(404);
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
