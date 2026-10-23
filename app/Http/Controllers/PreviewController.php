<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Preview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sysadmin "view as": pick a staff role, a new-borrower stand-in or a customer's account to preview (23 Oct 2026). */
class PreviewController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403, 'Only the sysadmin can preview other roles.');
    }

    public function index(Request $request): View
    {
        $this->guard($request);
        $q = trim((string) $request->query('q', ''));
        $customers = User::role(['borrower', 'partner', 'broker'])->whereNull('deleted_at')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
            ->with('roles:id,name')->withCount('loans')->orderByDesc('loans_count')->limit(60)->get(['id', 'name', 'email', 'phone']);

        return view('preview.index', ['roles' => Preview::staffRoles(), 'customers' => $customers, 'q' => $q, 'active' => Preview::active()]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->guard($request);
        $data = $request->validate(['kind' => 'required|in:role,new,user', 'role' => 'required_if:kind,role|string', 'user_id' => 'required_if:kind,user|integer']);
        $p = match ($data['kind']) {
            'role' => ['kind' => 'role', 'role' => $data['role']],
            'new' => ['kind' => 'new'],
            'user' => ['kind' => 'user', 'user_id' => (int) $data['user_id']],
        };
        if ($p['kind'] === 'role' && ! in_array($p['role'], Preview::staffRoles(), true)) {
            return back()->with('error', 'That role cannot be previewed.');
        }
        $persona = Preview::persona($p);
        if (! $persona || ($p['kind'] === 'user' && $persona->isStaff())) {
            return back()->with('error', 'Pick a borrower, partner or broker account. Staff are previewed by role.');
        }
        session([Preview::SESSION => $p]);
        \App\Services\AuditTrail::record('users', $request->user()->id, 'update', null, ['event' => 'preview_started', 'as' => Preview::label($p)]);

        return redirect()->route($persona->isStaff() ? 'dashboard' : 'account.home')
            ->with('success', 'Previewing as ' . Preview::label($p) . '. Nothing you do is saved or sent.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $this->guard($request);
        session()->forget(Preview::SESSION);

        return redirect()->route('preview.index')->with('success', 'Preview ended. You are yourself again.');
    }
}
