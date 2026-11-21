<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BorrowingEligibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Home screen: who is signed in, the limit, what stops them borrowing and the open loan. */
class MeController extends Controller
{
    public function show(Request $request, BorrowingEligibility $eligibility): JsonResponse
    {
        $user = $request->user();
        $limit = $user->borrower ? $eligibility->limit($user) : null;
        $type = $eligibility->loanType();
        $open = $eligibility->openLoan($user);

        return response()->json([
            'user' => self::userCard($user),
            'verified' => $eligibility->isVerified($user),
            'kyc_status' => $user->kyc_status,
            // on hold until KYC is finished (owner, 21 Nov 2026): what is missing, and where to finish it (the website)
            'kyc' => self::holdCard($user),
            'limit' => $limit ? ['amount' => (int) $limit['limit'], 'tier' => $limit['tier'] ?? null, 'label' => $limit['label'] ?? null] : null,
            'min_amount' => (int) config('lending.min_amount'),
            'blockers' => array_map(fn ($b) => ['title' => $b[0], 'message' => $b[1]], $eligibility->blockers($user)),
            'product' => $type ? ['name' => $type->name, 'rate' => (float) $type->interest_rate, 'period' => $type->period . ' ' . $type->unit] : null,
            // what is out now, for the home screen (owner, 15 Nov 2026): balance, due day, how far along
            'open_loan' => $open ? LoansController::card($open) + self::progressOf($open) : null,
        ]);
    }

    private static function holdCard(User $user): array
    {
        $hold = app(\App\Services\KycHold::class);
        if (! $hold->applies($user)) {
            return ['on_hold' => false, 'state' => 'clear'];
        }
        $s = $hold->status($user);

        return [
            'on_hold' => $s['state'] !== 'clear',
            'state' => $s['state'],
            'label' => \App\Services\KycHold::label($s['state']),
            'done' => $s['done'],
            'total' => $s['total'],
            'sections' => array_map(fn ($x) => ['title' => $x['title'], 'complete' => (bool) $x['complete']], $s['sections']),
            'requirements' => $hold->requirements($user),
            'notes' => $s['notes'],
            'finish_url' => route('kyc.start'),
        ];
    }

    private static function progressOf(\App\Models\Loan $loan): array
    {
        try {
            $p = app(\App\Services\LoanProgress::class)->for($loan);
        } catch (\Throwable) {
            return [];
        }

        return ['balance' => $p['balance'] ?? null, 'is_late' => (bool) ($p['is_late'] ?? false), 'progress' => $p['progress'] ?? null,
            'due_date' => ! empty($p['due_date']) ? \Illuminate\Support\Carbon::parse($p['due_date'])->toDateString() : null];
    }

    public static function userCard(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => explode(' ', trim((string) $user->name))[0],
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->getRoleNames()->values(),
            // what this account may do in the app; the app builds its tabs from it (App\Support\MobileAccess)
            'can' => \App\Support\MobileAccess::for($user),
            // their own photo through the API (owner, 15 Nov 2026): the website's photo links need a web session
            'has_photo' => (bool) \App\Support\PrivateFiles::absolute($user->{\App\Support\PrivateFiles::USER_DOCUMENTS['photo'] ?? 'profile_photo_path'} ?? null),
        ];
    }

    /** The signed-in user's own profile photo. */
    public function photo(\Illuminate\Http\Request $request)
    {
        $user = $request->user();
        $column = \App\Support\PrivateFiles::USER_DOCUMENTS['photo'] ?? 'profile_photo_path';
        $path = \App\Support\PrivateFiles::absolute($user->{$column} ?? null);
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
