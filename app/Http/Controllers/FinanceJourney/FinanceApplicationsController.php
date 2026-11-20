<?php

namespace App\Http\Controllers\FinanceJourney;

use App\Http\Controllers\Controller;
use App\Models\FinanceApplication;
use App\Models\Loan;
use App\Services\AuditTrail;
use App\Services\FinanceJourney\FinanceReview;
use App\Services\FinanceJourney\Journey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** Staff review queue for business asset and trade finance applications (20 Nov 2026). Permission "review finance applications". */
class FinanceApplicationsController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'waiting');
        $q = FinanceApplication::query()->withCount('documents')->with('reviewer:id,name')
            ->when($request->query('product'), fn ($q, $p) => $q->where('product', $p))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('reference', 'like', "%$s%")->orWhere('email', 'like', "%$s%")->orWhere('business->legal_name', 'like', "%$s%")))
            ->when($status === 'waiting', fn ($q) => $q->whereIn('status', ['submitted', 'in_review']))
            ->when($status !== 'waiting' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("field(status, 'submitted', 'in_review', 'more_info', 'draft', 'approved', 'declined', 'withdrawn')")->orderBy('submitted_at')->latest('id');
        $counts = FinanceApplication::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');

        return view('staff.finance.index', ['apps' => $q->paginate(30)->withQueryString(), 'counts' => $counts, 'status' => $status,
            'waitingAmount' => (float) FinanceApplication::whereIn('status', ['submitted', 'in_review'])->where('product', 'asset')->sum('amount')]);
    }

    public function show(FinanceApplication $application, Journey $journey): View
    {
        return view('staff.finance.show', ['fa' => $application->load('documents', 'prospect', 'reviewer', 'loan'), 'missing' => $journey->missingDocuments($application)]);
    }

    public function decide(Request $request, FinanceApplication $application, FinanceReview $review): RedirectResponse
    {
        $d = $request->validate(['decision' => 'required|in:' . implode(',', array_keys(FinanceReview::DECISIONS)), 'note' => 'nullable|string|max:2000',
            'back_to' => 'nullable|in:' . implode(',', array_keys(config('finance_journey.steps')))]);
        try {
            $review->decide($application, $d['decision'], $request->user(), $d['note'] ?? null, $d['back_to'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['decision' => $e->getMessage()])->withInput();
        }

        return back()->with('success', $application->reference . ': ' . $application->statusLabel() . ($d['decision'] === 'in_review' ? '.' : '. The applicant was emailed.'));
    }

    /** Once the facility is booked as a loan, the applicant can repay it through Sharet Pay from their page. */
    public function linkLoan(Request $request, FinanceApplication $application): RedirectResponse
    {
        abort_unless($request->user()->can('edit loans'), 403);
        $d = $request->validate(['loan_id' => 'nullable|integer|exists:loans,id']);
        if (! empty($d['loan_id']) && $application->status !== 'approved') {
            return back()->withErrors(['loan_id' => 'Link a loan only to an application approved in principle.']);
        }
        $application->update(['loan_id' => $d['loan_id'] ?? null]);
        AuditTrail::record('finance_applications', $application->id, 'update', null, ['loan_id' => $d['loan_id'] ?? null, 'by' => $request->user()->id]);

        return back()->with('success', ! empty($d['loan_id']) ? 'Linked to loan #' . $d['loan_id'] . '.' : 'Loan unlinked.');
    }

    public function document(FinanceApplication $application, int $document)
    {
        $doc = $application->documents()->findOrFail($document);
        abort_unless(Storage::disk(Journey::DISK)->exists($doc->path), 404);
        AuditTrail::record('finance_application_documents', $doc->id, 'view', null, ['by' => request()->user()->id]);

        return Storage::disk(Journey::DISK)->response($doc->path, $doc->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
