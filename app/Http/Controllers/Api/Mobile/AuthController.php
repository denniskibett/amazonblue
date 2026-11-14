<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Mobile test app sign-in (11 Nov 2026): one Sanctum token per phone install, named after the phone. */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:200'],
            'install_id' => ['required', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);
        $key = 'mobile-login:' . mb_strtolower($data['login']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Too many tries. Wait ' . RateLimiter::availableIn($key) . ' seconds.']);
        }

        $user = $this->findUser($data['login']);
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages(['login' => 'Those details do not match an account.']);
        }
        RateLimiter::clear($key);

        // one token per install: signing in again on the same phone replaces it
        $name = 'mobile:' . $data['install_id'];
        $user->tokens()->where('name', $name)->delete();
        $token = $user->createToken($name, ['mobile'], now()->addDays((int) config('mobile.token_days', 30)));
        AuditTrail::record('users', $user->id, 'update', null, ['event' => 'mobile_sign_in', 'device' => $data['device_name'] ?? null]);

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => MeController::userCard($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    private function findUser(string $login): ?User
    {
        if (str_contains($login, '@')) {
            return User::where('email', mb_strtolower(trim($login)))->first();
        }
        $digits = preg_replace('/\D/', '', $login);
        if (strlen($digits) < 9) {
            return null;
        }
        $tail = substr($digits, -9); // 07xx / 2547xx / +2547xx all end in the same nine digits

        return User::where('phone', 'like', '%' . $tail)->orderBy('id')->first();
    }
}
