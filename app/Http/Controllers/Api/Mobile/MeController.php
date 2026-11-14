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
            'limit' => $limit ? ['amount' => (int) $limit['limit'], 'tier' => $limit['tier'] ?? null, 'label' => $limit['label'] ?? null] : null,
            'min_amount' => (int) config('lending.min_amount'),
            'blockers' => array_map(fn ($b) => ['title' => $b[0], 'message' => $b[1]], $eligibility->blockers($user)),
            'product' => $type ? ['name' => $type->name, 'rate' => (float) $type->interest_rate, 'period' => $type->period . ' ' . $type->unit] : null,
            'open_loan' => $open ? LoansController::card($open) : null,
        ]);
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
        ];
    }
}
