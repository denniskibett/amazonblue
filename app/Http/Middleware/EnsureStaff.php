<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The dashboard is for staff and business processes only. Customers (borrowers and partners)
 * are sent to their own account area, which uses the website design.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isCustomer()) {
            if ($request->expectsJson()) {
                abort(403, 'This area is for staff.');
            }

            return redirect()->route('account.home');
        }

        return $next($request);
    }
}
