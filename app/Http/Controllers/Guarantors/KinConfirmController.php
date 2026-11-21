<?php

namespace App\Http\Controllers\Guarantors;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\KinConfirmation;
use App\Models\User;
use App\Services\Kyc\KinConfirmations;
use App\Support\PrivateFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** The next of kin confirms they know the client (owner, 21 Nov 2026). Public side: the texted link is the key. */
class KinConfirmController extends Controller
{
    private const FILES = ['id-front' => 'id_front_path', 'id-back' => 'id_back_path'];

    public function __construct(private KinConfirmations $kin)
    {
    }

    public function show(string $token): View
    {
        return view('guarantor.kin', ['row' => $this->kin->find($token), 'token' => $token,
            'relations' => array_values(HomeController::RELATIONSHIPS), 'done' => session('kin_done')]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $row = $this->kin->find($token);
        abort_unless($row, 404);
        $data = $request->validate([
            'answer' => ['required', Rule::in(array_keys(KinConfirmations::ANSWERS))],
            'relation' => ['required_if:answer,yes', 'nullable', Rule::in(array_values(HomeController::RELATIONSHIPS))],
            'id_number' => ['required_if:answer,yes', 'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9 ]{5,20}$/'],
            'id_front' => ['required_if:answer,yes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
            'id_back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'],
            'agree' => ['accepted_if:answer,yes'],
        ], [
            'answer.required' => 'Tell us whether you know them.',
            'relation.required_if' => 'Choose how you know them.',
            'id_number.required_if' => 'Enter your ID or passport number.',
            'id_number.regex' => 'Enter your ID or passport number as printed on it.',
            'id_front.required_if' => 'Add a photo of the front of your ID, or your passport photo page.',
            'agree.accepted_if' => 'Tick the box to confirm.',
        ]);
        try {
            $this->kin->complete($row, $data, $request->only(['id_front', 'id_back']), $request->ip(), $request->userAgent());
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['kin' => $e->getMessage()]);
        }

        return redirect()->route('kin.confirm', $token)->with('kin_done', ['name' => $row->firstName(), 'yes' => $data['answer'] === 'yes']);
    }

    /** Staff, from the KYC review: text the link (again). */
    public function send(Request $request, User $user): RedirectResponse
    {
        try {
            $row = $this->kin->open($user, $request->user(), false) ?? throw new \DomainException('Add the next of kin\'s name and phone first.');
            $this->kin->send($row, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Link texted to ' . $row->name . '.');
    }

    public function file(Request $request, User $user, string $kind): BinaryFileResponse
    {
        abort_unless(isset(self::FILES[$kind]), 404);
        $row = KinConfirmations::available() ? KinConfirmation::where('user_id', $user->id)->whereNotNull(self::FILES[$kind])->latest('id')->first() : null;
        $path = $row ? PrivateFiles::absolute($row->{self::FILES[$kind]}) : null;
        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
