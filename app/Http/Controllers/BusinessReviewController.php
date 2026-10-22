<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\CustomerNotifier;
use App\Support\Handle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Staff verification of business KYC. Borrowers add their business from /account (Concerns\BusinessOnboarding);
 * staff check the documents, directors and shareholders, tick the checks and approve, or reject with a note
 * the borrower sees (the business then reopens for editing). Every change is audited (AuditObserver).
 * View: `view users`; decide: `edit users` (routes/web.php).
 */
class BusinessReviewController extends Controller
{
    /** Crop a company document picture (same rules as the borrower KYC crop). */
    public function crop(Request $request, Business $business, \App\Models\BusinessDocument $document): RedirectResponse
    {
        abort_unless($document->business_id === $business->id, 404);
        $data = $request->validate([
            'x' => ['required', 'integer', 'min:0'], 'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:40', 'max:8000'], 'height' => ['required', 'integer', 'min:40', 'max:8000'],
            'rotate' => ['nullable', 'integer', 'in:0,90,180,270'], 'restore' => ['nullable', 'boolean'],
        ]);
        $file = \App\Support\PrivateFiles::absolute($document->path);
        if (! $file) {
            return back()->with('error', 'That document is not on this server.');
        }
        try {
            if ($request->boolean('restore')) {
                \App\Services\Kyc\ImageCrop::restore($file);
                \App\Services\AuditTrail::record('business_documents', $document->id, 'update', ['path' => 'cropped'], ['path' => 'original picture restored']);

                return back()->with('success', 'The original picture is back.');
            }
            [$w, $h] = \App\Services\Kyc\ImageCrop::crop($file, $data['x'], $data['y'], $data['width'], $data['height'], (int) ($data['rotate'] ?? 0));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        \App\Services\AuditTrail::record('business_documents', $document->id, 'update', ['path' => 'as uploaded'], ['path' => "cropped to {$w} x {$h}"]);

        return back()->with('success', "Document cropped to {$w} x {$h} px.");
    }

    public const STATUSES = [
        Business::STATUS_SUBMITTED => 'Waiting for review',
        Business::STATUS_APPROVED => 'Approved',
        Business::STATUS_REJECTED => 'Rejected',
        Business::STATUS_DRAFT => 'In progress',
    ];

    public function index(Request $request): View
    {
        $status = array_key_exists($request->query('status'), self::STATUSES) ? $request->query('status') : Business::STATUS_SUBMITTED;

        $businesses = Business::with(['owner'])->withCount('members')
            ->where('status', $status)
            ->orderBy(match ($status) {
                Business::STATUS_SUBMITTED => 'submitted_at',
                Business::STATUS_DRAFT => 'updated_at',
                default => 'reviewed_at',
            }, $status === Business::STATUS_SUBMITTED ? 'asc' : 'desc')
            ->limit(1000)->get();

        $counts = Business::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');

        return view('business-reviews.index', compact('businesses', 'status', 'counts') + ['statuses' => self::STATUSES]);
    }

    public function show(Business $business): View
    {
        $business->load(['owner', 'members.user', 'documents', 'reviewer']);

        return view('business-reviews.show', [
            'business' => $business,
            // the same registration number on another business is worth a second look
            'duplicates' => $business->registration_number
                ? Business::with('owner')->where('registration_number', $business->registration_number)->whereKeyNot($business->id)->get()
                : collect(),
            'missing' => $business->missingDocumentTypes(),
        ]);
    }

    public function approve(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->status === Business::STATUS_SUBMITTED, 409, 'Only submitted businesses can be approved.');

        $rules = collect(Business::CHECKS)->mapWithKeys(fn ($label, $key) => ["checks.$key" => ['accepted']])->all();
        $data = $request->validate($rules + ['notes' => ['nullable', 'string', 'max:2000']], [
            'checks.*.accepted' => 'Tick every check before approving.',
        ]);
        if ($business->missingDocumentTypes()) {
            return back()->withErrors(['checks' => 'Required documents are missing. Reject it and ask the borrower to upload them.']);
        }

        DB::transaction(function () use ($request, $business, $data) {
            $business->update([
                'status' => Business::STATUS_APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => $data['notes'] ?? null,
                'checks' => array_fill_keys(array_keys(Business::CHECKS), true),
            ]);
            if (! $business->handle && ($handle = Handle::forBusiness($business))) {
                $business->update(['handle' => $handle]);
            }
            // people on the CR12 who are verified borrowers count as verified members
            $business->members()->whereNotNull('user_id')->whereNull('verified_at')->update(['verified_at' => now()]);
        });
        CustomerNotifier::businessReviewed($business, true);

        return redirect()->route('business-reviews.index')
            ->with('alert', ['type' => 'success', 'title' => 'Business approved', 'message' => $business->legal_name . ' is verified.']);
    }

    public function reject(Request $request, Business $business): RedirectResponse
    {
        abort_unless($business->status === Business::STATUS_SUBMITTED, 409, 'Only submitted businesses can be rejected.');

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'notes.required' => 'Tell the borrower what needs fixing.',
            'notes.min' => 'Tell the borrower what needs fixing (at least 10 characters).',
        ]);

        $business->update([
            'status' => Business::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['notes'],
            'checks' => collect(Business::CHECKS)->mapWithKeys(fn ($label, $key) => [$key => $request->boolean("checks.$key")])->all(),
        ]);
        CustomerNotifier::businessReviewed($business, false, $data['notes']);

        return redirect()->route('business-reviews.index')
            ->with('alert', ['type' => 'warning', 'title' => 'Business rejected', 'message' => ($business->owner?->name ?? 'The borrower') . ' will see your note and can resubmit.']);
    }
}
