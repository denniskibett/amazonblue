<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Analytics\BusinessInsights;
use App\Services\Analytics\PeopleInsights;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Is the business working, and who and where to trust (owner, 4 Nov 2026). Read-only, "view reports". */
class InsightsController extends Controller
{
    public function pnl(Request $request, BusinessInsights $insights): View
    {
        return view('staff.insights.pnl', ['r' => $insights->report((string) $request->query('period', 'qtd'))]);
    }

    public function people(PeopleInsights $people): View
    {
        $dims = ['work' => 'How they earn', 'literacy' => 'Digital literacy (how they use the app)', 'education_band' => 'Education they told us',
            'lti_band' => 'Loan size against income', 'tenure' => 'How long with us', 'age_band' => 'Age'];

        return view('staff.insights.people', ['dims' => $dims, 'segments' => collect($dims)->map(fn ($l, $k) => $people->segment($k))->all(),
            'places' => $people->places(), 'gaps' => $people->gaps(), 'all' => $people->borrowers()]);
    }

    public function person(User $user, PeopleInsights $people): View
    {
        abort_unless($user->hasRole('borrower'), 404);

        return view('staff.insights.person', ['user' => $user] + $people->person($user));
    }
}
