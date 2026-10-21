<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\LedgerReports;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Portfolio report on the cycles and the ledger (Stage 4 Phase 7), with a CSV of every open loan. */
class PortfolioController extends Controller
{
    public function index(LedgerReports $reports): View
    {
        return view('staff.desk.portfolio', ['p' => $reports->portfolio()]);
    }

    public function csv(LedgerReports $reports): StreamedResponse
    {
        $p = $reports->portfolio();

        return response()->streamDownload(function () use ($p, $reports) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['loan_id', 'borrower_id', 'status', 'cash_lent', 'cash_repaid', 'unpaid_principal', 'owed_today', 'days_late', 'non_performing']);
            foreach ($p['rows'] as $r) {
                fputcsv($out, [$r['loan']->id, $r['loan']->user_id, $r['loan']->status, $reports->lent($r['loan']->id), $reports->repaid($r['loan']->id),
                    $r['principal'], $r['owed'], $r['days'], $r['loan']->is_non_performing ? 'yes' : '']);
            }
            fclose($out);
        }, 'portfolio-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
