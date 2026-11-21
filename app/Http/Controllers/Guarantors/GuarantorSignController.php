<?php

namespace App\Http\Controllers\Guarantors;

use App\Http\Controllers\Controller;
use App\Services\Loans\Guarantees;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The guarantor reads, fills in, uploads their ID and signs (owner, 21 Nov 2026). No login: the texted link is the key. */
class GuarantorSignController extends Controller
{
    public function __construct(private Guarantees $guarantees)
    {
    }

    public function show(string $token): View
    {
        $row = $this->guarantees->find($token);

        return view('guarantor.sign', [
            'row' => $row,
            'token' => $token,
            'terms' => $row ? $this->guarantees->terms($row) : [],
            'previous' => $row ? $this->guarantees->previous($row) : null,
            'done' => session('guarantor_done'),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $row = $this->guarantees->find($token);
        abort_unless($row, 404);
        $previous = $this->guarantees->previous($row);
        $data = $request->validate([
            'id_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9 ]{5,20}$/'],
            'dob' => ['required', 'date', 'before:-18 years', 'after:-100 years'],
            'residence' => ['required', 'string', 'max:120'],
            'occupation' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_front' => [$previous ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
            'id_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
            'signature_data' => ['required', 'string', 'max:1500000'],
            'agree' => ['accepted'],
        ], [
            'id_number.regex' => 'Enter your ID or passport number as printed on it.',
            'dob.before' => 'A guarantor must be 18 or older.',
            'id_front.required' => 'Add a photo of the front of your ID, or your passport bio page.',
            'signature_data.required' => 'Sign in the box with your finger or mouse.',
            'agree.accepted' => 'Tick the box to agree to the guarantee.',
        ], ['id_number' => 'ID number', 'dob' => 'date of birth', 'residence' => 'where you live', 'occupation' => 'what you do']);

        try {
            $this->guarantees->complete($row, $data, $request->only(['id_front', 'id_back']), $data['signature_data'], $request->ip(), $request->userAgent());
        } catch (\DomainException $e) {
            return back()->withInput($request->except('signature_data'))->withErrors(['guarantee' => $e->getMessage()]);
        }

        return redirect()->route('guarantor.sign', $token)->with('guarantor_done', [
            'name' => $row->firstName(),
            'borrower' => strtok((string) $row->loan->user?->name, ' '),
        ]);
    }
}
