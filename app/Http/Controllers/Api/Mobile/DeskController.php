<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff in the app (owner, 21 Nov 2026): the loan desk queues as counts, each only if the staff member's own
 * permissions allow it (same rules as the web Loan desk). Read only; decisions are made on the website.
 */
class DeskController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $u = $request->user();
        $queues = [];
        $add = function (string $key, string $label, int $count, string $url) use (&$queues): void {
            $queues[] = compact('key', 'label', 'count', 'url');
        };

        $add('approve', 'Loans to approve', Loan::where('status', Loan::STATUS_PENDING)->count(), route('staff.approvals'));
        if ($u->can('view disbursements')) {
            $add('disburse', 'Waiting to be sent', Loan::where('status', Loan::STATUS_APPROVED)->count(), route('staff.disbursements.queue'));
        }
        $add('overdue', 'Overdue loans', Loan::where('status', Loan::STATUS_OVERDUE)->count(), route('dashboard'));
        if ($u->can('view users')) {
            $add('kyc', 'KYC to review', User::where('kyc_status', 'submitted')->count(), route('kyc-reviews.index'));
        }

        return response()->json(['queues' => $queues, 'website' => route('dashboard'), 'as_of' => now()->toIso8601String()]);
    }
}
