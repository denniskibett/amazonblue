<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    public function index()
    {
        if (auth()->user()->role === 'admin') {
            // Load all disbursements with their associated loans and users
            $disbursements = Disbursement::with(['loan.user'])->get();
            return view('disbursements.index', [
                'disbursements' => $disbursements,
            ]);
        } else {
            // Load loans for the authenticated user with their disbursements
            $loans = auth()->user()->loans()->with('disbursements')->get();
            return view('disbursements.index', [
                'loans' => $loans,
            ]);
        }
    }

    public function show($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        return view('disbursements.show', compact('disbursement'));
    }

    public function create(Request $request)
    {
        $loan = Loan::findOrFail($request->loan_id);
      
        $loans = Loan::all();
        return view('disbursements.create', compact('loans','loan'));
    }

    public function store(Request $request, Loan $loan)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'disburse_date' => 'required|date',
            'payment_date' => 'required|date',
            'mode' => 'required|string',
        ]);
        Disbursement::create($request->all());
        $user = $loan->user->id;
        // /users/{user}/loans/{loan}
        return redirect()->to("/users/$user/loans/$loan->id");
    }

    public function destroy($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        $disbursement->delete();

        return redirect()->back();
    }

    public function edit(Disbursement $disbursement)
    {
        // Load related loan data
        $disbursement->load('loan');
        return view('disbursements.edit', compact('disbursement'));
    }

    public function update(Request $request, Disbursement $disbursement)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'disburse_date' => 'required|date',
            'payment_date' => 'required|date',
            'mode' => 'required|string',
        ]);

        $disbursement->update($request->only([
            'amount', 'disburse_date', 'payment_date', 'mode'
        ]));

        // Redirect back to loan details
        $loan = $disbursement->loan;
        return redirect()->to("/users/{$loan->user_id}/loans/{$loan->id}");
    }
}
