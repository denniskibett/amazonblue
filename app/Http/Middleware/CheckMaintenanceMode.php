<?php

namespace App\Http\Middleware;

use App\Helpers\SystemHelper;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin "maintenance mode" switch (System settings, systems.maintenance_mode). While it is on,
 * visitors and customers get the branded 503 page (or JSON 503); staff keep working, and the login /
 * logout / password pages stay open so staff can sign in. Runs in the web group (bootstrap/app.php).
 * `php artisan down` is separate and blocks everyone (use `--secret` to let yourself through).
 */
class CheckMaintenanceMode
{
    // reachable while the switch is on, so staff can still sign in
    private const OPEN = ['mpesa/*', 'login', 'logout', 'forgot-password', 'reset-password', 'reset-password/*', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->enabled() || $request->is(...self::OPEN)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->hasAnyRole(User::STAFF_ROLES)) {
            return $next($request);
        }

        abort(503);
    }

    private function enabled(): bool
    {
        try {
            return SystemHelper::isMaintenanceMode();
        } catch (\Throwable $e) {
            // settings table unavailable: never lock everyone out because of it
            return false;
        }
    }
}
