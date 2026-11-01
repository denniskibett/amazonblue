<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Testimonials to approve or hide before they show on the website (owner, 1 Nov 2026). */
class TestimonialsController extends Controller
{
    public function index(): View
    {
        return view('staff.desk.testimonials', [
            'testimonials' => Testimonial::with(['user:id,name', 'approver:id,name'])
                ->orderByRaw("FIELD(status, 'pending', 'approved', 'hidden')")->latest('updated_at')->get(),
        ]);
    }

    public function decide(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:approve,hide']);
        $approve = $data['decision'] === 'approve';
        $testimonial->update([
            'status' => $approve ? 'approved' : 'hidden',
            'approved_by' => $approve ? $request->user()->id : null,
            'approved_at' => $approve ? now() : null,
        ]);

        return back()->with('success', $approve ? 'Approved. It shows on the website now.' : 'Hidden from the website.');
    }
}
