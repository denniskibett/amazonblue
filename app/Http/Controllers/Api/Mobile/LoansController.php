<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Repayment;
use App\Services\LoanProgress;
use App\Services\Sharet\SharetCollections;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The borrower's loans, one loan with its balance and timeline, and the statement. */
class LoansController extends Controller
{
    public const PAYABLE = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE];
    public const RECOVERY = [Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY];

    public function index(Request $request): JsonResponse
    {
        $loans = Loan::with('loanType')->where('user_id', $request->user()->id)->latest('id')->limit(50)->get();

        return response()->json(['loans' => $loans->map(fn ($l) => self::card($l))->values()]);
    }

    public function show(Request $request, Loan $loan, LoanProgress $progress): JsonResponse
    {
        self::own($request, $loan);
        $p = $progress->for($loan);

        return response()->json([
            'loan' => self::card($loan),
            'reference' => $p['reference'],
            'balance' => $p['balance'],
            'due_date' => $p['due_date'] ? \Illuminate\Support\Carbon::parse($p['due_date'])->toDateString() : null,
            'is_late' => (bool) $p['is_late'],
            'progress' => $p['progress'],
            'timeline' => collect($p['timeline'])->map(fn ($s) => ['label' => $s['label'], 'done' => (bool) $s['done'],
                'date' => $s['date'] ? \Illuminate\Support\Carbon::parse($s['date'])->toDateString() : null, 'detail' => $s['detail'] ?? null, 'tone' => $s['tone'] ?? null])->values(),
            'repayments' => collect($p['repayments'])->map(fn ($r) => self::repayment($r))->values(),
            'pay' => [
                'can_pay' => in_array($loan->status, self::PAYABLE, true),
                'recovery' => in_array($loan->status, self::RECOVERY, true),
                'mpesa_prompt' => (bool) config('lending.mpesa.stk_enabled'),
                'sharet' => SharetCollections::available(),
                'sharet_min' => SharetCollections::MIN_AMOUNT,
                'paybill' => config('lending.mpesa.paybill'),
                'account' => (string) $loan->id,
            ],
        ]);
    }

    public function statement(Request $request): JsonResponse
    {
        $ids = Loan::where('user_id', $request->user()->id)->pluck('id');
        $rows = Repayment::whereIn('loan_id', $ids)->latest('repayment_date')->latest('id')->limit(200)->get();

        return response()->json(['repayments' => $rows->map(fn ($r) => self::repayment($r))->values(),
            'total_paid' => round((float) $rows->sum('amount'), 2)]);
    }

    public static function card(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'reference' => 'Loan #' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT),
            'amount' => (float) $loan->amount,
            'status' => $loan->status,
            'product' => $loan->loanType?->name,
            'borrow_date' => $loan->borrow_date ? \Illuminate\Support\Carbon::parse($loan->borrow_date)->toDateString() : null,
        ];
    }

    public static function repayment(Repayment $r): array
    {
        return ['id' => $r->id, 'loan_id' => $r->loan_id, 'amount' => (float) $r->amount,
            'date' => $r->repayment_date ? \Illuminate\Support\Carbon::parse($r->repayment_date)->toDateString() : null, 'reference' => $r->transaction, 'kind' => $r->kind ?? 'cash'];
    }

    public static function own(Request $request, Loan $loan): void
    {
        abort_unless((int) $loan->user_id === (int) $request->user()->id, 404);
    }
}
