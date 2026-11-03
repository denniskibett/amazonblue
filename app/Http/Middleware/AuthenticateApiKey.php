<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** /api/v1: a key from /staff/api-keys as "Authorization: Bearer <key>" (or X-Api-Key). Records when it was last used. */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = ApiKey::findActive($request->bearerToken() ?: $request->header('X-Api-Key'));
        if (! $key) {
            return response()->json(['message' => 'A valid API key is required.'], 401);
        }
        if (! $key->last_used_at || $key->last_used_at->lt(now()->subMinute())) {
            $key->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }
        $request->attributes->set('api_key', $key);

        return $next($request);
    }
}
