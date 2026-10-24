<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Business\DirectorVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A director verifies themselves from their link (owner, 23 Oct 2026). No login: the link is the key. */
class DirectorVerificationController extends Controller
{
    public function __construct(private DirectorVerification $directors)
    {
    }

    public function show(string $token): View
    {
        $member = $this->directors->find($token);

        return view('business.director-verify', ['member' => $member, 'token' => $token, 'done' => session('director_done')]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $member = $this->directors->find($token);
        abort_unless($member, 404);
        $data = $request->validate([
            'id_number' => ['required', 'string', 'max:20'],
            'dob' => ['required', 'date', 'before:-18 years'],
            'director_id_front' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'director_id_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'director_selfie' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'consent' => ['accepted'],
        ], [
            'dob.before' => 'Directors must be 18 or older.',
            'consent.accepted' => 'Tick the box to confirm.',
        ], ['id_number' => 'ID number', 'dob' => 'date of birth', 'director_id_front' => 'ID photo', 'director_selfie' => 'selfie']);
        try {
            $this->directors->complete($member, $data, $request->only(['director_id_front', 'director_id_back', 'director_selfie']), $request->ip());
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['id_number' => $e->getMessage()]);
        }

        return redirect()->route('directors.verify', $token)->with('director_done', ['name' => $member->full_name, 'business' => $member->business->legal_name]);
    }
}
