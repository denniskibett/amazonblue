<?php

namespace App\Http\Middleware;

use App\Support\Preview;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sysadmin "view as" (owner, 23 Oct 2026). While a preview is on, each request is answered as the preview user,
 * inside a database transaction that is always rolled back, with email, SMS, M-Pesa, the queue and file storage
 * switched to local fakes. So forms submit and pages react, but nothing is kept, sent or paid.
 * Only a super-admin can preview; anyone else's preview setting is dropped. The preview.* routes run as the
 * real sysadmin so they can switch or stop.
 */
class PreviewAs
{
    public function handle(Request $request, Closure $next): Response
    {
        $p = Preview::active();
        if (! $p) {
            return $next($request);
        }
        $real = Auth::user();
        if (! $real || ! $real->hasRole('super-admin')) {
            session()->forget(Preview::SESSION);

            return $next($request);
        }
        if ($request->routeIs('preview.*') || $request->is('preview', 'preview/*', 'logout')) {
            return $next($request);
        }
        $persona = Preview::persona($p);
        if (! $persona) {
            session()->forget(Preview::SESSION);

            return redirect()->route('preview.index')->with('error', 'That preview is no longer available.');
        }

        // nothing leaves the building while previewing
        config([
            'mail.default' => 'array',
            'queue.default' => 'sync',
            'lending.notifications.sms.driver' => 'log',
            'lending.mpesa.enabled' => false,
            'lending.mpesa.stk_enabled' => false,
            'filesystems.disks.local.root' => storage_path('framework/preview'),
            'filesystems.disks.public.root' => storage_path('framework/preview/public'),
        ]);
        app()->instance('az.preview', ['label' => Preview::label($p), 'by' => $real->id, 'kind' => $p['kind']]);

        $level = DB::transactionLevel();     // roll back only what the preview opened
        DB::beginTransaction();
        try {
            if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                $persona = Preview::materialize($persona);
            }
            Auth::setUser($persona);
            $response = $next($request);
        } finally {
            while (DB::transactionLevel() > $level) {
                DB::rollBack();
            }
            Auth::setUser($real);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            $note = 'Preview: that worked, but nothing was saved or sent.';
            if ($response instanceof RedirectResponse) {
                $response->with('az_preview_note', $note);
            } elseif ($request->expectsJson()) {
                $response->headers->set('X-Preview', 'nothing saved');
            }
        }

        return $response;
    }
}
