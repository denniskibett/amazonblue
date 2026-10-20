<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Requests;
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
        $data = $request->validate(['decision' => 'required|in:approve,decline', 'note' => 'required_if:decision,decline|nullable|string|max:500']);
        try {
            $requests->decideBorrower($id, $request->user(), $data['decision'] === 'approve', $data['note'] ?? null);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $data['decision'] === 'approve' ? 'Extension booked. The borrower sees it on their account.' : 'Declined. The borrower sees your note.');
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
