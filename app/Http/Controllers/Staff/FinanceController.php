<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\CustomerWallets;
use App\Services\PartnerPortfolio;

/**
 * Read-only finance screens: wallet reconciliation (the same checks as `php artisan wallets:check`,
 * never the repair) and partner balances ahead of a payout flow (Stage 4).
 */
class FinanceController extends Controller
{
    public function wallets(CustomerWallets $wallets)
    {
        $borrowers = $wallets->checkBorrowers();
        $partners = $wallets->checkPartners();

        $problems = count($borrowers['issues']) + count($borrowers['integrity'])
            + count($partners['issues']) + count($partners['integrity']) + count($partners['slug_mismatches']);

        $partnerNames = Partner::pluck('name', 'id');

        return view('staff.desk.wallets', compact('borrowers', 'partners', 'problems', 'partnerNames'));
    }

    /** Partner balances are part of the partners table now (owner, 4 Nov 2026); payout requests are on Requests. */
    public function partnerPayouts()
    {
        return redirect()->route('partners.index');
    }
}
