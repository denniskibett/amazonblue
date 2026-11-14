<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** The mobile API's on/off switch (MOBILE_API_ENABLED, config/mobile.php). */
class MobileApiEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('mobile.enabled')) {
            return response()->json(['message' => 'The mobile app is not switched on yet.'], 503);
        }

        return $next($request);
    }
}
