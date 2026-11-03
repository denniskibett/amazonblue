<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\CashForecast;
use Illuminate\View\View;

/** Cash and growth (owner, 3 Nov 2026): cash now, the 12-week forecast, and lending and collections by month. */
class CashForecastController extends Controller
{
    public function index(CashForecast $forecast): View
    {
        $months = $forecast->months();

        return view('staff.desk.cash-forecast', [
            'cash' => $forecast->cashNow(),
            'forecast' => $forecast->weeks(),
            'months' => $months,
            'projection' => $forecast->projection($months),
        ]);
    }
}
