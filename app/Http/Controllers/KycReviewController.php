<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Staff verification of borrower KYC. Borrowers fill in KYC themselves; staff review the
 * documents, call the next of kin and guarantor, then approve (borrower becomes active and
 * can borrow) or reject with a note the borrower sees.
 */
class KycReviewController extends Controller
{
    /**
     * Crop one of the borrower's pictures to the width and height staff chose (owner, 22 Oct 2026). The first crop
     * keeps the upload as {file}.original; "restore" puts it back. Audited.
     */
    public function crop(Request $request, User $user): RedirectResponse
    {
        $doc = $request->validate(['document' => ['required', 'in:' . implode(',', array_keys(\App\Support\PrivateFiles::USER_DOCUMENTS))]])['document'];
        $data = $request->validate([
            'x' => ['required', 'integer', 'min:0'], 'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:40', 'max:8000'], 'height' => ['required', 'integer', 'min:40', 'max:8000'],
            'rotate' => ['nullable', 'integer', 'in:0,90,180,270'], 'restore' => ['nullable', 'boolean'],
        ]);
        $column = \App\Support\PrivateFiles::USER_DOCUMENTS[$doc];
        $file = \App\Support\PrivateFiles::absolute($user->{$column});
        if (! $file) {
            return back()->with('error', 'That picture is not on this server.');
        }
        try {
            if ($request->boolean('restore')) {
                \App\Services\Kyc\ImageCrop::restore($file);
                \App\Services\AuditTrail::record('users', $user->id, 'update', [$column => 'cropped'], [$column => 'original picture restored']);

                return back()->with('success', 'The original picture is back.');
            }
            [$w, $h] = \App\Services\Kyc\ImageCrop::crop($file, $data['x'], $data['y'], $data['width'], $data['height'], (int) ($data['rotate'] ?? 0));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        \App\Services\AuditTrail::record('users', $user->id, 'update', [$column => 'as uploaded'], [$column => "cropped to {$w} x {$h}" . (($data['rotate'] ?? 0) ? ", turned {$data['rotate']} degrees" : '')]);

        return back()->with('success', "Picture cropped to {$w} x {$h} px.");
    }

    public const STATUSES = ['submitted' => 'Waiting for review', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function index(Request $request): View
    {
        $status = array_key_exists($request->query('status'), self::STATUSES) ? $request->query('status') : 'submitted';

        $users = User::with('borrower')
            ->where('kyc_status', $status)
            ->orderBy($status === 'submitted' ? 'kyc_submitted_at' : 'kyc_reviewed_at', $status === 'submitted' ? 'asc' : 'desc')
            ->limit(1000)->get();

        $counts = User::whereIn('kyc_status', array_keys(self::STATUSES))
            ->select('kyc_status', DB::raw('count(*) as total'))->groupBy('kyc_status')->pluck('total', 'kyc_status');

        return view('kyc-reviews.index', [
            'users' => $users,
            'status' => $status,
            'statuses' => self::STATUSES,
            'counts' => $counts,
        ]);
    }

    public function show(User $user): View
    {
        abort_unless($user->kyc_status, 404);

        $user->load(['borrower', 'contacts', 'addresses', 'paymentMethods']);

        return view('kyc-reviews.show', [
            'user' => $user,
            'guarantor' => $user->contacts->where('relationship_specific', HomeController::GUARANTOR)->sortByDesc('id')->first(),
            'address' => $user->addresses->sortByDesc('is_primary')->first(),
            'mpesa' => $user->paymentMethods->whereNotNull('mobile_number')->sortByDesc('id')->first(),
            'bank' => $user->paymentMethods->whereNotNull('account_number')->sortByDesc('id')->first(),
            'reviewer' => $user->kyc_reviewed_by ? User::find($user->kyc_reviewed_by) : null,
            // existing clients re-verifying: their track record helps the decision
            'loanHistory' => \App\Models\Loan::where('user_id', $user->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->kyc_status === 'submitted', 409, 'Only submitted verifications can be approved.');

        $data = $request->validate([
            'called_next_of_kin' => ['accepted'],
            'called_guarantor' => ['accepted'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'called_next_of_kin.accepted' => 'Confirm you called the next of kin before approving.',
            'called_guarantor.accepted' => 'Confirm you called the guarantor before approving.',
        ]);

        DB::transaction(function () use ($request, $user, $data) {
            $user->update([
                'kyc_status' => 'approved',
                'kyc_reviewed_by' => $request->user()->id,
                'kyc_reviewed_at' => now(),
                'kyc_review_notes' => $data['notes'] ?? null,
                'kyc_checks' => ['next_of_kin' => true, 'guarantor' => true],
            ]);
            // an active borrower can borrow from the dashboard
            $user->borrower?->update(['status' => 1]);
        });
        \App\Support\CustomerNotifier::kycReviewed($user, true);

        return redirect()->route('kyc-reviews.index')
            ->with('alert', ['type' => 'success', 'title' => 'Verification approved', 'message' => $user->name . ' can now borrow from their dashboard.']);
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->kyc_status === 'submitted', 409, 'Only submitted verifications can be rejected.');

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'notes.required' => 'Tell the borrower what needs fixing.',
            'notes.min' => 'Tell the borrower what needs fixing (at least 10 characters).',
        ]);

        $user->update([
            'kyc_status' => 'rejected',
            'kyc_reviewed_by' => $request->user()->id,
            'kyc_reviewed_at' => now(),
            'kyc_review_notes' => $data['notes'],
            'kyc_checks' => [
                'next_of_kin' => $request->boolean('called_next_of_kin'),
                'guarantor' => $request->boolean('called_guarantor'),
            ],
        ]);
        \App\Support\CustomerNotifier::kycReviewed($user, false, $data['notes']);

        return redirect()->route('kyc-reviews.index')
            ->with('alert', ['type' => 'warning', 'title' => 'Verification rejected', 'message' => $user->name . ' will see your note and can resubmit.']);
    }
}
