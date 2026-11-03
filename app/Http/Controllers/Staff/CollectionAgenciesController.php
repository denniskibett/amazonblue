<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CollectionAgency;
use App\Models\CollectionPlacement;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\Collections\AgencyPlacements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Outside debt collection agencies (owner, 3 Nov 2026): agencies, their collector logins, and placing late loans. */
class CollectionAgenciesController extends Controller
{
    public function __construct(private AgencyPlacements $placements) {}

    public function index(Request $request): View
    {
        $agencies = CollectionAgency::withCount(['collectors', 'placements as open_count' => fn ($q) => $q->open()])->orderBy('name')->get();
        $agency = $agencies->firstWhere('id', (int) $request->query('agency')) ?? $agencies->first();

        return view('staff.desk.agencies', [
            'agencies' => $agencies,
            'agency' => $agency,
            'collectors' => $agency ? $agency->collectors()->get(['users.id', 'name', 'email', 'phone']) : collect(),
            'placed' => $agency ? CollectionPlacement::open()->where('agency_id', $agency->id)->with('loan.user:id,name,phone')->withCount('contacts')->latest('placed_at')->get() : collect(),
            'placeable' => $this->placements->placeable(),
            'noticeDays' => (int) config('lending.collections.agency_notice_days', 7),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120', 'contact_name' => 'nullable|string|max:120', 'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30', 'agreement_ref' => 'required|string|max:60', 'commission_rate' => 'required|numeric|min:0|max:100',
            'coverage' => 'nullable|string|max:160', 'cross_border' => 'nullable|boolean',
        ]);
        $agency = CollectionAgency::create($data + ['cross_border' => $request->boolean('cross_border'), 'status' => 'active', 'created_by' => $request->user()->id]);
        AuditTrail::record('collection_agencies', $agency->id, 'create', null, ['name' => $agency->name, 'agreement_ref' => $agency->agreement_ref]);

        return redirect()->route('staff.agencies', ['agency' => $agency->id])->with('success', $agency->name . ' added. Now add its collectors.');
    }

    public function toggle(CollectionAgency $agency): RedirectResponse
    {
        $old = $agency->status;
        $agency->update(['status' => $old === 'active' ? 'paused' : 'active']);
        AuditTrail::record('collection_agencies', $agency->id, 'update', ['status' => $old], ['status' => $agency->status]);

        return back()->with('success', $agency->name . ($agency->status === 'active' ? ' can work its accounts again.' : ' is paused: its collectors see nothing until you switch it back on.'));
    }

    public function storeCollector(Request $request, CollectionAgency $agency): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email|max:160|unique:users,email', 'phone' => 'required|string|max:30|unique:users,phone',
            'password' => ['required', Password::min(10)],
        ]);
        $user = DB::transaction(function () use ($agency, $data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'password' => Hash::make($data['password']), 'role' => 'collector', 'status' => 0]);
            $user->forceFill(['email_verified_at' => now()])->save();   // made by staff: no email confirmation step
            $user->assignRole('collector');
            $agency->collectors()->attach($user->id);

            return $user;
        });
        AuditTrail::record('users', $user->id, 'create', null, ['role' => 'collector', 'agency' => $agency->name]);

        return back()->with('success', $user->name . ' can now sign in at ' . route('login') . ' and works only ' . $agency->name . '\'s accounts. Give them the password yourself.');
    }

    public function removeCollector(CollectionAgency $agency, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('collector'), 404);
        $agency->collectors()->detach($user->id);
        $user->removeRole('collector');
        AuditTrail::record('users', $user->id, 'update', ['role' => 'collector', 'agency' => $agency->name], ['role' => null]);

        return back()->with('success', $user->name . ' no longer has access.');
    }

    public function place(Request $request, CollectionAgency $agency): RedirectResponse
    {
        $data = $request->validate([
            'loan_ids' => 'required|array|min:1', 'loan_ids.*' => 'integer', 'ends_on' => 'required|date|after:today',
            'notice_sent_on' => 'nullable|date|before_or_equal:today', 'note' => 'nullable|string|max:500',
        ]);
        abort_unless($agency->status === 'active', 422, 'This agency is paused.');
        $n = $this->placements->place($agency, $data['loan_ids'], Carbon::parse($data['ends_on']),
            ! empty($data['notice_sent_on']) ? Carbon::parse($data['notice_sent_on']) : null, $request->user(), $data['note'] ?? null);

        return back()->with('success', "{$n} loan(s) placed with {$agency->name}.");
    }

    public function notice(Request $request, CollectionPlacement $placement): RedirectResponse
    {
        $data = $request->validate(['notice_sent_on' => 'required|date|before_or_equal:today']);
        $old = $placement->notice_sent_on?->toDateString();
        $placement->update($data);
        AuditTrail::record('collection_placements', $placement->id, 'update', ['notice_sent_on' => $old], $data);

        return back()->with('success', 'Notice date saved for loan #' . $placement->loan_id . '.');
    }

    public function takeBack(Request $request, CollectionPlacement $placement): RedirectResponse
    {
        $this->placements->takeBack($placement, $request->user());

        return back()->with('success', 'Loan #' . $placement->loan_id . ' taken back from the agency.');
    }
}
