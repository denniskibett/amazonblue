<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the public website may be indexed. Every other page (customer account, KYC, staff dashboard, sign-in,
 * private files, previews, errors) answers with X-Robots-Tag: noindex, nofollow, whatever its <head> says.
 */
class NoIndexPrivatePages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $public = $request->route() && $request->routeIs(...Seo::PUBLIC_ROUTES) && $response->getStatusCode() < 400;
        if (! $public && ! $response->headers->has('X-Robots-Tag')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
