<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Loan;
use App\Services\DisbursementService;
use App\Models\Investment;
use App\Models\InvestmentFunding;   
use App\Models\Partner;
use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    protected $disbursementService;

    public function __construct(DisbursementService $disbursementService)
    {
        $this->disbursementService = $disbursementService;
    }

    /**
     * Disbursements register (owner, 22 Oct 2026): every disbursement row, newest first, with date, search and
     * kind filters, totals for the filter, and a CSV download. Staff see all; anyone else only their own loans'.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $f = $request->validate([
            'from' => 'nullable|date', 'to' => 'nullable|date', 'q' => 'nullable|string|max:100',
            'kind' => 'nullable|in:cash,non_cash', 'mode' => 'nullable|string|max:20',
        ]);

        $q = Disbursement::query()->with(['loan:id,user_id,loan_type_id,status', 'loan.user:id,name,phone', 'loan.loanType:id,name']);
        if (! $user->hasAnyRole([...\App\Models\User::BACK_OFFICE_ROLES, 'teller'])) {
            $ids = [$user->id];
            if ($user->hasRole('broker') && ($broker = $user->broker()->first())) {
                $ids = array_merge($ids, \App\Models\Borrower::where('broker_id', $broker->id)->pluck('user_id')->all());
            }
            $q->whereHas('loan', fn ($l) => $l->whereIn('user_id', $ids));
        }
        if (! empty($f['from'])) {
            $q->where('disburse_date', '>=', $f['from']);
        }
        if (! empty($f['to'])) {
            $q->where('disburse_date', '<=', $f['to'] . ' 23:59:59');
        }
        if (! empty($f['mode'])) {
            $q->where('mode', $f['mode']);
        }
        if (($f['kind'] ?? null) === 'cash') {
            \App\Services\CustomerWallets::whereCash($q);
        } elseif (($f['kind'] ?? null) === 'non_cash') {
            $q->whereNotIn('id', \App\Services\CustomerWallets::whereCash(\Illuminate\Support\Facades\DB::table('disbursements')->whereNull('deleted_at'))->select('id'));
        }
        if (! empty($f['q'])) {
            $term = '%' . $f['q'] . '%';
            $q->where(fn ($w) => $w->where('transaction', 'like', $term)->when(\App\Models\Disbursement::hasRefColumns(), fn ($x) => $x->orWhere('bank_ref', 'like', $term)->orWhere('mpesa_ref', 'like', $term))->orWhere('loan_id', ltrim($f['q'], '#'))
                ->orWhereHas('loan.user', fn ($u) => $u->where('name', 'like', $term)->orWhere('phone', 'like', $term)));
        }

        $totals = (clone $q)->toBase()->selectRaw('count(*) as n, sum(amount) as amount, sum(coalesce(processing_fee,0)) as fees')->first();
        $q->orderByDesc('disburse_date')->orderByDesc('id');

        if ($request->query('export') === 'csv') {
            $name = 'disbursements-' . now()->format('Ymd-His') . '.csv';

            return response()->streamDownload(function () use ($q) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Date', 'Loan', 'Borrower', 'Product', 'Amount', 'Fee', 'Net sent', 'Mode', 'Kind', 'Reference']);
                $q->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $d) {
                        fputcsv($out, [$d->id, optional($d->disburse_date)->format('Y-m-d') ?? $d->disburse_date, $d->loan_id, $d->loan->user->name ?? '',
                            $d->loan->loanType->name ?? '', $d->amount, $d->processing_fee, $d->net_amount, $d->mode,
                            \App\Services\CustomerWallets::kindOf($d), $d->transaction]);
                    }
                });
                fclose($out);
            }, $name, ['Content-Type' => 'text/csv']);
        }

        $disbursements = $q->limit(1000)->get();
        $modes = Disbursement::query()->whereNotNull('mode')->distinct()->pluck('mode')->filter()->values();

        return view('disbursements.index', compact('disbursements', 'totals', 'f', 'modes'));
    }

    public function show($id)
    {
        $disbursement = Disbursement::with(['loan', 'loanCycle'])->findOrFail($id);
        return view('disbursements.show', compact('disbursement'));
    }

    public function create(Request $request)
    {
        $loan = Loan::findOrFail($request->loan_id);
        $loans = Loan::all();
        return view('disbursements.create', compact('loans', 'loan'));
    }

    public function store(Request $request)
    {
        // every disbursement is cash to a borrower on a loan; investment-only draws (loan_id NULL) are refused
        if ($request->filled('investment_id') && !$request->filled('loan_id')) {
            return response()->json([
                'message' => 'Choose the loan this disbursement is for. A disbursement from an investment must be linked to a loan.',
                'errors' => ['loan_id' => ['A disbursement from an investment must be linked to a loan.']],
            ], 422);
        }

        // bank ↔ M-Pesa keeps both references; the reference searched by is filled from them (owner, 23 Oct 2026)
        if (! $request->filled('transaction') && ($request->filled('bank_ref') || $request->filled('mpesa_ref'))) {
            $request->merge(['transaction' => $request->input('bank_ref') ?: $request->input('mpesa_ref')]);
        }
        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'disburse_date' => 'required|date',
            'transaction' => 'required|string|max:255',
            'bank_ref' => 'nullable|string|max:100',
            'mpesa_ref' => 'nullable|string|max:40',
            'mode' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'funding_source' => 'nullable|in:internal,partner,mixed',
            'kind' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\CustomerWallets::KINDS)],
            'investment_id' => 'nullable|exists:investments,id',
            'partner_transaction_id' => 'nullable|exists:partner_transactions,id',
        ];

        $rules['loan_id'] = 'required|exists:loans,id';
        $rules['loan_cycle_id'] = 'nullable|exists:loan_cycles,id';

        $validated = $request->validate($rules, [
            'loan_id.required' => 'Choose the loan this disbursement is for.',
        ]);
        $validated = array_merge($validated, \App\Support\PaymentModes::fromRequest($request, 'out', 'disbursements', (int) $validated['loan_id']));
        // who brought the client and whose money it is (owner, 23 Oct 2026)
        $links = $request->validate([
            'broker_partner_id' => ['nullable', 'integer', 'min:0'],
            'funding_mode' => ['nullable', 'in:house,line,new'],
            'new_partner_id' => ['nullable', 'required_if:funding_mode,new', 'integer', 'exists:partners,id'],
            'new_amount' => ['nullable', 'required_if:funding_mode,new', 'numeric', 'min:1'],
            'new_code' => ['nullable', 'required_if:funding_mode,new', 'string', 'max:40'],
            'new_date' => ['nullable', 'date'],
            'new_term' => ['nullable', 'required_if:funding_mode,new', 'in:short_term,long_term'],
            'new_maturity' => ['nullable', 'date'],
        ], [
            'new_partner_id.required_if' => 'Choose the partner who sent the money.',
            'new_amount.required_if' => 'Enter how much the partner sent.',
            'new_code.required_if' => 'Paste the M-Pesa message or enter its code.',
            'new_term.required_if' => 'Choose short term or long term.',
        ]);
        if (($links['funding_mode'] ?? null) === 'line' && empty($validated['investment_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['investment_id' => 'Choose the partner line this loan is funded from.']);
        }
        if (! empty($links['broker_partner_id']) && \App\Models\Partner::whereKey($links['broker_partner_id'])->where('is_house_account', false)->doesntExist()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['broker_partner_id' => 'Choose a partner, or nobody.']);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $validated, $links) {
            // new partner money: open its line first, so the loan draws on it (the whole disbursement rolls back together)
            if (($links['funding_mode'] ?? null) === 'new') {
                try {
                    $line = app(\App\Services\Partners\PartnerLines::class)->open(\App\Models\Partner::findOrFail($links['new_partner_id']),
                        (float) $links['new_amount'], $links['new_term'], $links['new_maturity'] ?? null, $links['new_code'],
                        $links['new_date'] ?? null, $request->user(), 'Opened while disbursing loan #' . $validated['loan_id']);
                } catch (\DomainException $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['new_code' => $e->getMessage()]);
                }
                $validated['investment_id'] = $line->id;
                $validated['funding_source'] = 'partner';
            }

            // ============ AUTO-DETECT FUNDING SOURCE ============
            if (empty($validated['funding_source'])) {
                if (!empty($validated['partner_transaction_id'])) {
                    $validated['funding_source'] = 'partner';
                } elseif (!empty($validated['investment_id'])) {
                    $investment = Investment::find($validated['investment_id']);
                    $validated['funding_source'] = ($investment && !empty($investment->funding_partners))
                        ? 'partner'
                        : 'internal';
                } else {
                    $validated['funding_source'] = 'internal';
                }
            }

            // ============ DEFAULT MISSING investment_id TO HOUSE PRINCIPAL ============
            if (empty($validated['investment_id']) && ($validated['funding_source'] ?? 'internal') === 'internal') {
                $houseInvestment = Investment::firstOrCreate(
                    ['name' => 'General Lending Book'],
                    [
                        'type' => 'other',
                        'facility_type' => 'long_term',
                        'country' => 'Kenya',
                        'initial_amount' => 0,
                        'current_value' => 0,
                        'expected_return' => 0,
                        'purchase_date' => now()->toDateString(),
                        'status' => 'active',
                        'funding_partners' => [],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                $validated['investment_id'] = $houseInvestment->id;
                $validated['funding_source'] = 'internal';
            }

            // ============ AUTO-LINK FUNDING ROW IF MISSING ============
            if (!empty($validated['investment_id'])) {
                $investment = Investment::find($validated['investment_id']);

                if ($investment && $investment->fundings()->count() === 0) {
                    $housePrincipal = \App\Models\Partner::where('is_house_account', true)
                        ->where('account_type', 'principal')
                        ->first();

                    if ($housePrincipal) {
                        \App\Models\InvestmentFunding::create([
                            'investment_id' => $investment->id,
                            'partner_id' => $housePrincipal->id,
                            'amount_committed' => $investment->initial_amount ?: 0,
                            'amount_disbursed' => 0,
                            'amount_returned' => 0,
                            'status' => 'active',
                            'notes' => 'Auto-linked to House Principal (fallback).',
                        ]);
                    }
                }
            }

            // ============ LOAN DISBURSEMENT ============
            $loan = Loan::find($validated['loan_id']);

            // maker-checker (G8, decision 14): the person who approved the loan doesn't also send the money
            if (\App\Services\Approvals::enabled()) {
                $approver = \App\Models\AuditLog::where('table_name', 'loans')->where('record_id', $loan->id)->where('action', 'update')
                    ->where('new_values->status', 'approved')->latest('id')->value('user_id');
                if ($approver && (int) $approver === (int) $request->user()->id) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['loan_id' => 'You approved this loan, so someone else must send the money.']);
                }
            }
            $cycle = $loan->getCurrentCycle();

            // no draw beyond what partners committed to the investment (house lines are not capped)
            $investment = !empty($validated['investment_id']) ? Investment::find($validated['investment_id']) : null;
            $fundingPartnerId = !empty($validated['partner_transaction_id'])
                ? \App\Models\PartnerTransaction::find($validated['partner_transaction_id'])?->partner_id
                : null;
            try {
                $investment?->assertCanDisburse((float) $validated['amount'], $fundingPartnerId);
            } catch (\DomainException $e) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => $e->getMessage()]);
            }

            // disbursement, its investment link and the funding draw succeed or fail together
            $disbursement = \Illuminate\Support\Facades\DB::transaction(function () use ($loan, $validated, $cycle, $investment, $fundingPartnerId) {
                $disbursement = $this->disbursementService->createDisbursement($loan, $validated, $cycle);

                // Attach investment_id and funding
                if ($disbursement && $investment) {
                    $disbursement->update([
                        'investment_id' => $investment->id,
                        'partner_transaction_id' => $validated['partner_transaction_id'] ?? $disbursement->partner_transaction_id,
                        'funding_source' => $validated['funding_source'],
                    ]);

                    // Track against pivot
                    $investment->recordFundingDisbursement((float) $validated['amount'], $fundingPartnerId);
                }

                return $disbursement;
            });

            // Update loan status if approved
            if ($loan->status === 'approved') {
                $loan->status = 'disbursed';
                $loan->save();
            }

            // brokered by: the partner chosen, else a partner borrowing for themselves is their own broker
            // (full rate, their broker share comes back on every repayment: owner, 23 Oct 2026)
            $partnerLinks = app(\App\Services\Partners\PartnerLinks::class);
            $brokerId = isset($links['broker_partner_id']) ? (int) $links['broker_partner_id'] : null;
            if ($brokerId === null && ! $loan->broker_status && ($own = $partnerLinks->ownPartner($loan))) {
                $brokerId = $own->id;
            }
            if ($brokerId !== null) {
                $partnerLinks->apply($loan, $brokerId, null, $request->user(), 'disbursement');
            }

            return response()->json([
                'message' => 'Disbursement created successfully!',
                'data' => $disbursement->fresh()
            ], 201);
        });
    }

    public function update(Request $request, Disbursement $disbursement)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'disburse_date' => 'required|date',
            'transaction' => 'required|string|max:255',
            'bank_ref' => 'nullable|string|max:100',
            'mpesa_ref' => 'nullable|string|max:40',
            'mode' => 'nullable|string|max:100',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'investment_id' => 'nullable|exists:investments,id',
            'partner_transaction_id' => 'nullable|exists:partner_transactions,id',
            'funding_source' => 'nullable|in:internal,partner,mixed',
            'kind' => ['nullable', \Illuminate\Validation\Rule::in(\App\Services\CustomerWallets::KINDS)],
        ]);

        $validated = array_merge($validated, \App\Support\PaymentModes::fromRequest($request, 'out', 'disbursements', $disbursement->loan_id, $disbursement->id));

        // broker and funding from the edit modal (owner, 23 Oct 2026). Before any payment they change straight away;
        // once payments have been split, the change goes through "Partners: broker and funding" with its preview.
        $links = null;
        if ($disbursement->loan_id && $request->filled('funding_mode')) {
            $loan = $disbursement->loan;
            $brokerId = (int) $request->input('broker_partner_id', 0);
            $mode = $request->input('funding_mode');
            $house = Investment::where('status', 'active')->get()->first(fn ($i) => $i->isHouseLine());
            $lineId = $mode === 'line' ? (int) $request->input('investment_id') : ($mode === 'house' ? (int) $house?->id : 0);
            $currentBroker = $loan->broker_status && $loan->partner_id ? (int) $loan->partner_id : 0;
            $brokerChange = $brokerId !== $currentBroker ? $brokerId : null;
            $lineChange = $lineId && $lineId !== (int) $disbursement->investment_id ? $lineId : null;
            if ($brokerChange !== null || $lineChange) {
                if (\App\Models\Repayment::where('loan_id', $loan->id)->where('kind', 'cash')->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['broker_partner_id' =>
                        'Payments on this loan are already split. Change the broker or funding under "Partners: broker and funding", which shows how every share moves first.']);
                }
                $links = [$brokerChange, $lineChange];
            }
            unset($validated['investment_id'], $validated['funding_source']);
        }

        // Recalculate processing fee for loan disbursements
        if ($disbursement->loan_id) {
            $loan = $disbursement->loan;
            $processingFeeRate = $loan->processing_fee_rate ?? 0;
            $validated['processing_fee'] = ($processingFeeRate / 100) * $validated['amount'];
            $validated['net_amount'] = $validated['amount'] - $validated['processing_fee'];
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($disbursement, $validated, $links, $request) {
            $disbursement->update($validated);
            if ($links) {
                try {
                    app(\App\Services\Partners\PartnerLinks::class)->apply($disbursement->loan, $links[0], $links[1], $request->user(), 'disbursement_edit');
                } catch (\DomainException $e) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['investment_id' => $e->getMessage()]);
                }
            }
        });

        return response()->json([
            'message' => 'Disbursement updated successfully!',
            'data' => $disbursement->fresh()
        ], 200);
    }


    public function destroy($id)
    {
        $disbursement = Disbursement::findOrFail($id);
        
        // Reverse the processing fee from loan total
        $loan = $disbursement->loan;
        $loan->total_processing_fees = max(0, ($loan->total_processing_fees ?? 0) - $disbursement->processing_fee);
        $loan->save();

        $disbursement->delete();

        return response()->json([
            'message' => 'Disbursement deleted successfully!'
        ], 200);
    }

    public function edit(Disbursement $disbursement)
    {
        $disbursement->load('loan');
        return view('disbursements.edit', compact('disbursement'));
    }
}