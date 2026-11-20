<?php

namespace App\Http\Controllers\FinanceJourney;

use App\Http\Controllers\Controller;
use App\Mail\FinanceApplicationMail;
use App\Models\FinanceApplication;
use App\Services\FinanceJourney\Journey;
use App\Services\Sharet\SharetCollections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

/**
 * The applicant's side of the business finance journey (20 Nov 2026). No login: the private link is the key, so these
 * pages send no Referer and are never indexed. Designs: WA-E1..E5 (trade), B-1..B-3 and AF-12 (asset).
 */
class FinanceJourneyController extends Controller
{
    public function __construct(private Journey $journey)
    {
        abort_unless(config('finance_journey.enabled'), 404);
    }

    public function choose(Request $request)
    {
        return view('finance.choose', ['track' => $request->query('p')]);
    }

    public function start(Request $request, string $product)
    {
        return view('finance.start', ['product' => $product, 'p' => config('finance_journey.products.' . $product), 'track' => $request->query('p')]);
    }

    public function store(Request $request, string $product): RedirectResponse
    {
        [$app, $token] = $this->journey->start($product, $request->all(), $request->input('p'));
        $this->mail($app, 'link', $token);

        return redirect()->route('finance.step', [$token, 'business'])->with('status', 'Saved. We emailed you a private link to come back to ' . $app->reference . ' at any time.');
    }

    public function show(string $token)
    {
        $app = $this->journey->find($token) ?? $this->expired();

        return $this->private(view('finance.show', $this->shared($app, $token)));
    }

    public function step(string $token, string $step)
    {
        $app = $this->journey->find($token) ?? $this->expired();
        abort_unless(array_key_exists($step, config('finance_journey.steps')), 404);
        if (! $app->isEditable()) {
            return redirect()->route('finance.show', $token);
        }

        return $this->private(view('finance.step', $this->shared($app, $token) + ['step' => $step]));
    }

    public function save(Request $request, string $token, string $step): RedirectResponse
    {
        $app = $this->journey->find($token) ?? $this->expired();
        abort_unless(array_key_exists($step, config('finance_journey.steps')), 404);
        try {
            if ($step === 'documents') {
                $n = $this->journey->saveDocuments($app, $request->file('docs', []));
                $app->refresh();
                if ($n && ! $app->stepDone('documents')) {
                    return redirect()->route('finance.step', [$token, 'documents'])->with('status', $n . ' file' . ($n > 1 ? 's' : '') . ' saved. Add the rest marked required.');
                }
            } else {
                $wasMore = $app->status === 'more_info';
                $this->journey->saveStep($app, $step, $request->except(['_token', 'exit']), $request->ip());
                if ($step === 'review') {
                    $this->mail($app, 'submitted', $token);

                    return redirect()->route('finance.show', $token)->with('status', $wasMore ? 'Sent back to our team. Thank you.' : 'Submitted. We emailed you a receipt.');
                }
            }
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }
        if ($request->boolean('exit')) {
            return redirect()->route('finance.show', $token)->with('status', 'Saved. Come back with the link we emailed you.');
        }

        return redirect()->route('finance.step', [$token, $app->fresh()->nextStep()])->with('status', 'Saved.');
    }

    public function removeDocument(string $token, int $document): RedirectResponse
    {
        $app = $this->journey->find($token) ?? $this->expired();
        $doc = $app->documents()->findOrFail($document);
        try {
            $this->journey->removeDocument($app, $doc);
        } catch (\DomainException $e) {
            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return back()->with('status', 'Removed ' . $doc->original_name . '.');
    }

    /** Repay through Sharet Pay once the facility is booked as a loan (Sharet switched on). */
    public function pay(string $token): RedirectResponse
    {
        $app = $this->journey->find($token) ?? $this->expired();
        abort_unless($app->loan && SharetCollections::available(), 404);
        try {
            $due = max(0.0, (float) (app(\App\Services\LoanProgress::class)->for($app->loan)['balance']['due'] ?? 0));
            $link = app(SharetCollections::class)->payLink($app->loan, $due);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()->away($link->pay_url);
    }

    public function resumeForm()
    {
        return view('finance.resume');
    }

    /** Email a fresh link for every open application on this address. Always the same answer, so it reveals nothing. */
    public function resume(Request $request): RedirectResponse
    {
        $d = $request->validate(['email' => 'required|email:filter|max:190']);
        FinanceApplication::where('email', strtolower($d['email']))->whereNotIn('status', ['withdrawn'])->latest('id')->limit(5)->get()
            ->each(fn ($app) => $this->mail($app, 'link', $this->journey->rotate($app)));

        return back()->with('status', 'If we have an application for that address, a new link is on its way. Older links stop working.');
    }

    private function shared(FinanceApplication $app, string $token): array
    {
        return ['fa' => $app->load('documents'), 'token' => $token, 'missing' => $this->journey->missingDocuments($app),
            'canPay' => $app->loan_id && SharetCollections::available()];
    }

    private function mail(FinanceApplication $app, string $kind, string $token): void
    {
        try {
            Mail::to($app->email)->send(new FinanceApplicationMail($app, $kind, route('finance.show', $token)));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function expired(): never
    {
        abort(response()->view('finance.expired', [], 410));
    }

    private function private($view): Response
    {
        return response($view)->header('Referrer-Policy', 'no-referrer')->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }
}
