<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Borrower side of testimonials (owner, 1 Nov 2026): a borrower whose loan was actually sent can leave one,
 * and edit it later. Every save goes back to pending until staff approve it (Staff\TestimonialsController).
 */
class TestimonialController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($loan = Testimonial::qualifyingLoan($user), 403, 'You can share your experience once a loan has been sent to you.');

        return view('home.account.testimonial', [
            'testimonial' => Testimonial::where('user_id', $user->id)->first(),
            'defaultName' => Testimonial::defaultName($user),
            'loan' => $loan,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($loan = Testimonial::qualifyingLoan($user), 403, 'You can share your experience once a loan has been sent to you.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:10', 'max:400'],
            'display_name' => ['required', 'string', 'max:60'],
            'display_role' => ['nullable', 'string', 'max:80'],
            'consent' => ['accepted'],
        ], [
            'body.required' => 'Tell us about your experience.',
            'consent.accepted' => 'Tick the box so we may show this on our website.',
        ]);

        $testimonial = Testimonial::firstOrNew(['user_id' => $user->id]);
        $testimonial->fill([
            'loan_id' => $testimonial->loan_id ?? $loan->id,
            'rating' => (int) $data['rating'],
            'body' => trim($data['body']),
            'display_name' => trim($data['display_name']),
            'display_role' => filled($data['display_role'] ?? null) ? trim($data['display_role']) : null,
            'status' => 'pending', 'approved_by' => null, 'approved_at' => null,
        ])->save();

        return redirect()->route('account.testimonial')->with('alert', ['type' => 'success', 'title' => 'Thank you',
            'message' => 'Our team reads it before it appears on the website.']);
    }
}
