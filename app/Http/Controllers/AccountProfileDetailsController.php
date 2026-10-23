<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use App\Services\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Complete your profile" (owner, 23 Oct 2026): a borrower fills in what we are missing so their limit, reminders and
 * payday analytics are right. Empty details can be filled in here; details already on file (checked by our team at
 * verification) are shown read-only, so income can't be raised from the account. The pay schedule can always change.
 */
class AccountProfileDetailsController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $b = $user->borrower ?? new Borrower(['user_id' => $user->id, 'client_type' => 'individual', 'status' => 1]);

        return view('home.account.details', ['user' => $user, 'b' => $b, 'missing' => $b->missing(), 'types' => HomeController::INCOME_TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $b = Borrower::firstOrCreate(['user_id' => $user->id], ['client_type' => 'individual', 'status' => 1]);
        $data = $request->validate([
            'income_type' => ['nullable', Rule::in(array_keys(HomeController::INCOME_TYPES))],
            'net_salary' => ['nullable', 'numeric', 'min:1', 'max:999999999'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'pay_frequency' => ['required', Rule::in(array_keys(Borrower::PAY_FREQUENCIES))],
            'pay_day' => ['nullable', 'required_if:pay_frequency,monthly', 'integer', 'min:1', 'max:31'],
        ], [
            'pay_day.required_if' => 'Which day of the month are you paid?',
            'pay_frequency.required' => 'How often are you paid?',
        ]);
        $before = $b->only(['income_type', 'net_salary', 'employer_name', 'job_title', 'pay_frequency', 'pay_day']);

        // only what is empty can be filled from the account; the pay schedule can always be updated
        $fill = ['pay_frequency' => $data['pay_frequency'], 'pay_day' => $data['pay_frequency'] === 'monthly' ? (int) $data['pay_day'] : null];
        foreach (['income_type', 'employer_name', 'job_title'] as $f) {
            if (empty($b->{$f}) && ! empty($data[$f])) {
                $fill[$f] = $data[$f];
            }
        }
        if (! ((float) $b->net_salary > 0) && ! empty($data['net_salary'])) {
            $fill['net_salary'] = round((float) $data['net_salary'], 2);
        }
        $b->fill($fill);
        if (! $b->missing() && ! $b->profile_completed_at) {
            $b->profile_completed_at = now();
        }
        $b->save();
        AuditTrail::record('borrowers', $b->id, 'update', $before, $fill + ['event' => 'profile_details']);

        return redirect()->route('account.home')->with('alert', ['type' => 'success', 'title' => 'Thank you',
            'message' => $b->missing() ? 'Saved. A few details are still missing: ' . implode(', ', $b->missing()) . '.' : 'Your profile is complete. We use it to set your limit and time your reminders.']);
    }
}
