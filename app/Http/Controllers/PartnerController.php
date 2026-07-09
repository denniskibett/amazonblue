<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\User;
use App\Models\PartnerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    /**
     * Display a listing of partners.
     */
    public function index()
    {
        $partners = Partner::with(['user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'phone' => $partner->phone,
                    'company_name' => $partner->company_name,
                    'type' => $partner->type,
                    'status' => $partner->status,
                    'total_contribution' => $partner->total_contribution,
                    'current_balance' => $partner->current_balance,
                    'total_invested' => $partner->total_invested,
                    'total_returned' => $partner->total_returned,
                    'net_position' => $partner->net_position,
                    'created_at' => $partner->created_at?->format('Y-m-d H:i:s'),
                    'user_name' => $partner->user?->name ?? 'No User',
                ];
            });

        $stats = [
            'total' => $partners->count(),
            'active' => $partners->where('status', 'active')->count(),
            'total_contributions' => $partners->sum('total_contribution'),
            'total_balance' => $partners->sum('current_balance'),
            'total_invested' => $partners->sum('total_invested'),
            'total_returned' => $partners->sum('total_returned'),
        ];

        $users = User::where('role', 'partner')->whereDoesntHave('partner')->get();

        return view('partners.index', compact('partners', 'stats', 'users'));
    }

    /**
     * Store a newly created partner.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:partners,email',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'type' => 'required|in:individual,corporate,institutional',
            'status' => 'required|in:active,inactive,suspended',
            'profit_share_rate' => 'nullable|numeric|min:0|max:100',
            'max_loan_to_value' => 'nullable|numeric|min:0|max:100',
            'risk_tolerance' => 'required|in:conservative,moderate,aggressive',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $partner = Partner::create([
                'user_id' => $request->user_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'registration_number' => $request->registration_number,
                'type' => $request->type,
                'status' => $request->status,
                'profit_share_rate' => $request->profit_share_rate ?? 0,
                'max_loan_to_value' => $request->max_loan_to_value ?? 75,
                'risk_tolerance' => $request->risk_tolerance,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_name' => $request->bank_name,
                'swift_code' => $request->swift_code,
                'tax_id' => $request->tax_id,
                'notes' => $request->notes,
            ]);

            // If a user was provided, update their role
            if ($request->filled('user_id')) {
                $user = User::find($request->user_id);
                if ($user && $user->role !== 'partner') {
                    $user->role = 'partner';
                    $user->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partner created successfully.',
                'partner' => $partner->fresh()
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified partner.
     */
    public function show(Partner $partner)
    {
        $partner->load(['user', 'transactions']);
        
        $data = [
            'id' => $partner->id,
            'name' => $partner->name,
            'email' => $partner->email,
            'phone' => $partner->phone,
            'company_name' => $partner->company_name,
            'registration_number' => $partner->registration_number,
            'type' => $partner->type,
            'status' => $partner->status,
            'total_contribution' => $partner->total_contribution,
            'total_withdrawn' => $partner->total_withdrawn,
            'current_balance' => $partner->current_balance,
            'profit_share_rate' => $partner->profit_share_rate,
            'max_loan_to_value' => $partner->max_loan_to_value,
            'risk_tolerance' => $partner->risk_tolerance,
            'bank_account_name' => $partner->bank_account_name,
            'bank_account_number' => $partner->bank_account_number,
            'bank_name' => $partner->bank_name,
            'swift_code' => $partner->swift_code,
            'tax_id' => $partner->tax_id,
            'notes' => $partner->notes,
            'user' => $partner->user,
            'transactions' => $partner->transactions->map(function($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type,
                    'amount' => $t->amount,
                    'balance_after' => $t->balance_after,
                    'reference' => $t->reference,
                    'transaction_date' => $t->transaction_date?->format('Y-m-d'),
                    'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'total_invested' => $partner->total_invested,
            'total_returned' => $partner->total_returned,
            'net_position' => $partner->net_position,
            'created_at' => $partner->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $partner->updated_at?->format('Y-m-d H:i:s'),
        ];

        return view('partners.show', compact('partner', 'data'));
    }

    /**
     * Update the specified partner.
     */
    public function update(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:partners,email,' . $partner->id,
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'type' => 'sometimes|required|in:individual,corporate,institutional',
            'status' => 'sometimes|required|in:active,inactive,suspended',
            'profit_share_rate' => 'nullable|numeric|min:0|max:100',
            'max_loan_to_value' => 'nullable|numeric|min:0|max:100',
            'risk_tolerance' => 'sometimes|required|in:conservative,moderate,aggressive',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $partner->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Partner updated successfully.',
                'partner' => $partner->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified partner.
     */
    public function destroy(Partner $partner)
    {
        try {
            $partner->delete();
            return response()->json([
                'success' => true,
                'message' => 'Partner deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete partner: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add contribution to partner.
     */
    public function addContribution(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = $partner->addContribution($request->amount, $request->reference, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Contribution added successfully.',
                'transaction' => $transaction,
                'current_balance' => $partner->current_balance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add contribution: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw from partner.
     */
    public function withdraw(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = $partner->withdraw($request->amount, $request->reference, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal completed successfully.',
                'transaction' => $transaction,
                'current_balance' => $partner->current_balance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Distribute profit to partner.
     */
    public function distributeProfit(Request $request, Partner $partner)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = $partner->distributeProfit($request->amount, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Profit distributed successfully.',
                'transaction' => $transaction,
                'current_balance' => $partner->current_balance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to distribute profit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get partner data for the table.
     */
    public function getData(Request $request)
    {
        $query = Partner::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        $partners = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'phone' => $partner->phone,
                    'company_name' => $partner->company_name,
                    'type' => $partner->type,
                    'status' => $partner->status,
                    'total_contribution' => $partner->total_contribution,
                    'current_balance' => $partner->current_balance,
                    'total_invested' => $partner->total_invested,
                    'total_returned' => $partner->total_returned,
                    'net_position' => $partner->net_position,
                ];
            });

        return response()->json([
            'data' => $partners,
            'count' => $partners->count()
        ]);
    }

    /**
     * Get partner statistics.
     */
    public function getStats()
    {
        $partners = Partner::all();
        
        return response()->json([
            'total' => $partners->count(),
            'active' => $partners->where('status', 'active')->count(),
            'total_contributions' => $partners->sum('total_contribution'),
            'total_balance' => $partners->sum('current_balance'),
            'total_invested' => $partners->sum('total_invested'),
            'total_returned' => $partners->sum('total_returned'),
            'by_type' => $partners->groupBy('type')->map->count(),
            'by_status' => $partners->groupBy('status')->map->count(),
        ]);
    }
}