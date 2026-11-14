<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * /api/v1: a key from /staff/api-keys as "Authorization: Bearer <key>" (or X-Api-Key). Records when it was last used.
 * With a client (api.key:mobile) only that kind of key is taken, from X-Api-Key, because the bearer token is the
 * signed-in user's (owner, 11 Nov 2026).
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next, ?string $client = null): Response
    {
        $key = ApiKey::findActive($client ? $request->header('X-Api-Key') : ($request->bearerToken() ?: $request->header('X-Api-Key')));
        // each key opens only its own API: plain api.key is for other systems, api.key:mobile for the app
        if ($key && $key->client !== ($client ?? 'system')) {
            $key = null;
        }
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
