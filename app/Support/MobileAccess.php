<?php

namespace App\Support;

use App\Models\User;

/**
 * Who may do what in the Aimzone app (owner, 21 Nov 2026: "the mobile app doesn't RBAC"). The server is the only judge:
 * every mobile route names its capability (mobile.can) and the app builds its tabs from the list /me returns.
 *
 *   loans, borrow, repay, statement   borrowers: their own loans only (LoansController::own)
 *   portfolio                         partners: their own partner record
 *   desk                              staff who may view loans: queue counts, read only
 *
 * Anyone with none of these (collectors, staff without loan access) cannot sign in to the app.
 */
class MobileAccess
{
    public const LABELS = [
        'loans' => 'Loans', 'borrow' => 'Borrow', 'repay' => 'Pay', 'statement' => 'Statement',
        'portfolio' => 'Portfolio', 'desk' => 'Loan desk',
    ];

    /** @return list<string> */
    public static function for(User $user): array
    {
        $can = [];
        if ($user->hasRole('borrower')) {
            array_push($can, 'loans', 'borrow', 'repay', 'statement');
        }
        if ($user->hasRole('partner')) {
            $can[] = 'portfolio';
        }
        if ($user->isStaff() && $user->can('view loans')) {
            $can[] = 'desk';
        }

        return array_values(array_unique($can));
    }

    public static function allows(?User $user, string $capability): bool
    {
        return $user !== null && in_array($capability, self::for($user), true);
    }

    /** What /me and sign-in tell the app: the capabilities and the role names, for its tabs. */
    public static function card(User $user): array
    {
        return ['can' => self::for($user), 'roles' => $user->getRoleNames()->values()->all()];
    }
}
