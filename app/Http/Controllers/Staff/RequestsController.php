<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Borrower and partner requests to decide (Stage 4 Phase 5). */
class RequestsController extends Controller
{
    public function index(): View
    {
        return view('staff.desk.requests', [
            'borrower' => DB::table('borrower_requests as r')->join('users as u', 'u.id', '=', 'r.user_id')
                ->where('r.status', 'open')->orderBy('r.id')->get(['r.*', 'u.name as borrower']),
            'partner' => DB::table('partner_requests as r')->join('partners as p', 'p.id', '=', 'r.partner_id')
                ->whereIn('r.status', ['pending', 'prepared'])->orderBy('r.id')->get(['r.*', 'p.name as partner']),
            'on' => Requests::enabled(),
        ]);
    }

    public function borrower(Request $request, int $id, Requests $requests): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:approve,decline', 'note' => 'required_if:decision,decline|nullable|string|max:500',
            'date' => 'nullable|date|before_or_equal:today', 'interest_rate' => 'nullable|numeric|min:0|max:100']);
        try {
            $requests->decideBorrower($id, $request->user(), $data['decision'] === 'approve', $data['note'] ?? null,
                array_intersect_key($data, array_flip(['date', 'interest_rate'])));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $data['decision'] === 'approve' ? 'Extension booked. The borrower sees it on their account.' : 'Declined. The borrower sees your note.');
    }

    /** What approving an extension would book, from the day chosen (default: the cycle's due date). JSON for the modal. */
    public function preview(Request $request, int $id, Requests $requests): JsonResponse
    {
        $data = $request->validate(['date' => 'nullable|date', 'interest_rate' => 'nullable|numeric|min:0|max:100']);
        $r = DB::table('borrower_requests')->where('id', $id)->first();
        abort_unless($r && $r->type === 'extension' && $r->loan_id, 404);
        $loan = \App\Models\Loan::with('loanType', 'user:id,name')->findOrFail($r->loan_id);
        $cycle = $loan->cycles()->orderByDesc('cycle_number')->first();
        $base = ['loan' => $loan->id, 'borrower' => $loan->user?->name, 'product' => $loan->loanType?->name, 'status' => $loan->status,
            'cycle' => $cycle ? ['n' => $cycle->cycle_number, 'start' => $cycle->start_date->toDateString(), 'due' => $cycle->due_date->toDateString(),
                'rate' => (float) $cycle->interest_rate,
                'owed_today' => round(\App\Services\Loans\CycleBalance::at($cycle)['final_outstanding'], 2),
                'owed_on_due' => round(\App\Services\Loans\CycleBalance::at($cycle, $cycle->due_date->toDateString())['final_outstanding'], 2)] : null,
            'rate_from_cycle' => (int) config('lending.rollover.same_rate_cycles', 4) + 1, 'today' => now()->toDateString()];
        try {
            $plan = app(\App\Services\Loans\Rollover::class)->preview($loan, $requests->extensionOptions($loan, $data));
        } catch (\DomainException $e) {
            return response()->json($base + ['ok' => false, 'message' => $e->getMessage(),
                'needs_rate' => $cycle && $cycle->cycle_number + 1 >= $base['rate_from_cycle']]);
        }

        return response()->json($base + ['ok' => true, 'needs_rate' => $plan['new_cycle'] >= $base['rate_from_cycle'],
            'plan' => array_intersect_key($plan, array_flip(['rollover_date', 'new_cycle', 'principal', 'interest_rate', 'interest', 'new_balance', 'start_date', 'due_date']))
                + ['penalty' => round((float) ($plan['balance']['penalty'] ?? 0), 2)]]);
    }

    public function partner(Request $request, int $id, Requests $requests): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $data = $request->validate(['decision' => 'required|in:yes,no', 'note' => 'required_if:decision,no|nullable|string|max:500']);
        $type = DB::table('partner_requests')->where('id', $id)->value('type');
        try {
            $type === 'funding'
                ? $requests->confirmFunding($id, $request->user(), $data['decision'] === 'yes', $data['note'] ?? null)
                : $requests->prepareWithdrawal($id, $request->user(), $data['decision'] === 'yes', $data['note'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        $status = DB::table('partner_requests')->where('id', $id)->value('status');

        return back()->with('success', match ($status) {
            'done' => $type === 'funding' ? 'Capital added to the partner\'s account.' : 'Paid out and posted to the partner\'s statement.',
            'prepared' => 'Prepared. A second person approves the payout on Second sign-offs.',
            default => 'Declined.',
        });
    }
}
