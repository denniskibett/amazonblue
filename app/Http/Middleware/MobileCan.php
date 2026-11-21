<?php

namespace App\Http\Middleware;

use App\Support\MobileAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** mobile.can:<capability> on every signed-in mobile route (App\Support\MobileAccess). */
class MobileCan
{
    public function handle(Request $request, Closure $next, string ...$capabilities): Response
    {
        foreach ($capabilities as $capability) {
            if (MobileAccess::allows($request->user(), $capability)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Your account cannot use this part of the app.', 'title' => 'Not for your account'], 403);
    }
}
