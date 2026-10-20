<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Approvals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Second sign-offs waiting for someone other than the person who asked (Stage 4 Phase 5). */
class SignOffsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $mine = fn ($a) => $user->can(Approvals::ACTIONS[$a->action]['permission'] ?? '-');
        $rows = DB::table('approvals')->orderByDesc('id')->limit(200)->get();
        $names = User::whereIn('id', $rows->pluck('requested_by')->merge($rows->pluck('decided_by'))->filter()->unique())->pluck('name', 'id');

        return view('staff.desk.sign-offs', [
            'waiting' => $rows->where('status', 'pending')->filter($mine)->values(),
            'decided' => $rows->where('status', '!=', 'pending')->take(30)->values(),
            'names' => $names,
            'on' => Approvals::enabled(),
        ]);
    }

    public function decide(Request $request, int $approval, Approvals $approvals): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject', 'reason' => 'required_if:decision,reject|nullable|string|max:500']);
        try {
            $approvals->decide($approval, $request->user(), $data['decision'] === 'approve', $data['reason'] ?? null);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $data['decision'] === 'approve' ? 'Approved and done.' : 'Rejected. Nothing was changed.');
    }
}
