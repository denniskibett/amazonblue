<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ============ REGISTER MIDDLEWARE ALIASES ============
        $middleware->alias([
            // Spatie Permission Middleware
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            
            // Custom Middleware
            'check.role' => CheckRole::class,
            // dashboard = staff only; borrowers and partners use /account
            'staff' => \App\Http\Middleware\EnsureStaff::class,
        ]);
        
        // ============ ADD GLOBAL MIDDLEWARE ============
        // If you need to add global middleware, use:
        // $middleware->append(App\Http\Middleware\ApplySystemSettings::class);

        // admin "maintenance mode" switch (System settings): 503 for visitors/customers, staff carry on
        $middleware->web(append: \App\Http\Middleware\CheckMaintenanceMode::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // HTML errors use the branded pages in resources/views/errors. API / AJAX callers get JSON with
        // only a message and the status: never a trace or exception details, whatever APP_DEBUG says.
        // Validation errors (422) keep Laravel's {message, errors} shape for the forms that read it.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! ($request->expectsJson() || $request->is('api/*'))
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return null;
            }

            $status = match (true) {
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException,
                $e instanceof \Spatie\Permission\Exceptions\UnauthorizedException => 403,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Illuminate\Session\TokenMismatchException => 419,
                default => 500,
            };
            // our own abort() messages are safe to show below 500; anything converted from another
            // exception (e.g. "No query results for model ...") or any 5xx gets the standard text
            $message = $status < 500 && $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                && ! $e->getPrevious() && $e->getMessage() !== ''
                ? $e->getMessage()
                : ([419 => 'Page expired. Refresh and try again.'][$status] ?? \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Error');

            return response()->json(['message' => $message, 'status' => $status], $status);
        });
    })
    ->withCommands([
        \App\Console\Commands\BalanceHouseAccounts::class,
    ])
    ->create();