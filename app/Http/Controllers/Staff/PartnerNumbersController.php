<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\Partners\PartnerNumbers;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Every partner's numbers and loans on one page (owner, 15 Nov 2026). */
class PartnerNumbersController extends Controller
{
    public function index(Request $request, PartnerNumbers $numbers): View
    {
        $rows = $numbers->all();

        return view('staff.partners.numbers', [
            'rows' => $rows,
            'loans' => $rows->reject(fn ($r) => $r->partner->is_house_account)->mapWithKeys(fn ($r) => [$r->partner->id => $numbers->loansFor($r->partner)]),
        ]);
    }
}
