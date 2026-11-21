<?php

namespace App\Http\Middleware;

use App\Services\KycHold;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * New business waits until KYC is finished (owner, 21 Nov 2026): borrowing, top-ups, partner funds, withdrawals and
 * subscriptions. Put on those routes only; repaying and reading one's own records are never behind it.
 */
class EnsureKycClear
{
    public function __construct(private KycHold $hold) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $this->hold->onHold($user)) {
            return $next($request);
        }

        $status = $this->hold->status($user);
        $message = $status['state'] === 'submitted'
            ? 'Your details are with our team. This opens as soon as you are verified.'
            : 'Finish verifying your account (KYC) first. This opens as soon as you are verified.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'on_hold' => true,
                'kyc_state' => $status['state'],
                'kyc_missing' => collect($status['sections'])->where('complete', false)->pluck('title')->values(),
            ], 403);
        }

        return redirect()->route('account.home')->with('alert', ['type' => 'info', 'title' => 'Your account is on hold', 'message' => $message]);
    }
}
