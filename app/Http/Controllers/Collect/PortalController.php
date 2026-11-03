<?php

namespace App\Http\Controllers\Collect;

use App\Http\Controllers\Controller;
use App\Models\CollectionAgency;
use App\Models\CollectionContact;
use App\Services\AuditTrail;
use App\Services\Collections\AgencyPlacements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The collector portal (/collect, owner, 3 Nov 2026): an agency's open placements, and nothing else. */
class PortalController extends Controller
{
    public function __construct(private AgencyPlacements $placements) {}

    public function index(Request $request): View
    {
        $rows = $this->placements->forCollector($request->user());

        return view('collect.index', ['agency' => CollectionAgency::of($request->user()), 'rows' => $rows]);
    }

    public function show(Request $request, int $placement): View
    {
        $p = $this->placements->findFor($request->user(), $placement) ?? abort(404);

        return view('collect.show', ['p' => $p, 'profile' => $this->placements->profile($p), 'channels' => CollectionContact::CHANNELS]);
    }

    /** A printable profile ("Download profile (PDF)": the browser's Save as PDF), watermarked and logged. */
    public function profile(Request $request, int $placement): View
    {
        $p = $this->placements->findFor($request->user(), $placement) ?? abort(404);
        AuditTrail::record('collection_placements', $p->id, 'update', null, ['event' => 'profile_downloaded', 'by' => $request->user()->id, 'ip' => $request->ip()]);

        return view('collect.profile', ['p' => $p, 'profile' => $this->placements->profile($p), 'by' => $request->user()]);
    }

    public function contact(Request $request, int $placement): RedirectResponse
    {
        $p = $this->placements->findFor($request->user(), $placement) ?? abort(404);
        abort_unless($p->contactAllowed(), 403, 'You may contact this borrower from ' . ($p->contactFrom()?->format('j M Y') ?? 'the day we tell them') . '.');
        $data = $request->validate([
            'channel' => ['required', Rule::in(array_keys(CollectionContact::CHANNELS))], 'outcome' => 'required|string|max:120',
            'promise_amount' => 'nullable|numeric|min:1', 'promise_date' => 'nullable|required_with:promise_amount|date|after_or_equal:today',
            'note' => 'nullable|string|max:1000',
        ]);
        $this->placements->logContact($p, $request->user(), $data);

        return back()->with('success', 'Saved to this loan\'s contact log.');
    }
}
