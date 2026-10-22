<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LoanCycle;
use App\Services\Loans\RepaymentSplit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Split preview on Receive payment (owner, 23 Oct 2026): how an amount would divide on a cycle (penalty, interest,
 * principal) and who would earn what of it. Read-only: nothing is saved or posted.
 */
class SplitPreviewController extends Controller
{
    public function __invoke(Request $request, RepaymentSplit $split): JsonResponse
    {
        $data = $request->validate([
            'loan_cycle_id' => 'required|integer|exists:loan_cycles,id',
            'amount' => 'required|numeric|min:0.01|max:100000000',
            'date' => 'nullable|date|before_or_equal:today',
        ]);
        $cycle = LoanCycle::findOrFail($data['loan_cycle_id']);

        return response()->json($split->preview($cycle, (float) $data['amount'], $data['date'] ?? null));
    }
}
