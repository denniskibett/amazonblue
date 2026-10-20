<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Loan;
use App\Models\Partner;
use App\Services\AuditTrail;
use App\Services\PartnerAccount;
use App\Services\PartnerPortfolio;
use App\Support\CustomerNotifier;
use App\Support\PartnerFunding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Partner self-service (/account/partner/..., role:partner, own data only). Read-only screens over what
 * staff record (PartnerPortfolio, PartnerAccount), plus two requests that only email staff and write an
 * audit row: "I've sent funds" and "withdrawal request". No money moves from here; staff post it through
 * Partners / Investments. Stage 4: requests table, maker-checker, PartnerLedger posting on approval.
 */
trait PartnerSelfService
{
    public function partnerHome(Request $request, PartnerPortfolio $portfolio, PartnerAccount $account): View
    {
        $partner = $account->partnerFor($request->user());

        return view('home.account.partner', [
            'user' => $request->user(),
            'portfolio' => $partner ? $portfolio->summary($partner) : null,
            'home' => $partner ? $account->home($partner) : null,
        ]);
    }

    public function partnerInvestments(Request $request, PartnerAccount $account): View
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return $this->partnerSetup('Investments', 'investments');
        }

        return view('home.account.partner.investments', [
            'partner' => $partner,
            'fundings' => $account->fundings($partner)->get(),
            'loanCounts' => $account->investmentIds($partner)
                ->mapWithKeys(fn ($id) => [$id => $account->fundedLoans($partner, $id)->count()]),
        ]);
    }

    public function partnerInvestment(Request $request, PartnerAccount $account, int $investment): View
    {
        $partner = $account->partnerFor($request->user());
        $model = $partner ? $account->investment($partner, $investment) : null;
        abort_unless($model, 404);

        return view('home.account.partner.investment', [
            'partner' => $partner,
            'investment' => $model,
            'fundings' => $partner->fundings()->where('investment_id', $model->id)->get(),
            'loans' => $account->fundedLoans($partner, $model->id)->with('user')->latest('id')->limit(50)->get(),
            'entries' => $partner->ledgerEntries()->where('investment_id', $model->id)->latest('transaction_date')->latest('id')->get(),
        ]);
    }

    public function partnerLoans(Request $request, PartnerAccount $account): View
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return $this->partnerSetup('Funded loans', 'funded');
        }
        $status = $request->query('status');
        $groups = ['open' => ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery'], 'repaid' => ['repaid'], 'closed' => ['written_off', 'rejected']];
        // G6: loans funded through an investment and loans brought in as a broker are separate lists
        $list = $request->query('list') === 'brokered' ? 'brokered' : 'funded';
        $query = fn () => $list === 'brokered' ? $account->brokeredLoans($partner) : $account->fundedLoans($partner);

        return view('home.account.partner.loans', [
            'partner' => $partner,
            'list' => $list,
            'status' => array_key_exists((string) $status, $groups) ? $status : null,
            'loans' => $query()
                ->when(array_key_exists((string) $status, $groups), fn ($q) => $q->whereIn('status', $groups[$status]))
                ->with('user')->latest('id')->paginate(20)->withQueryString(),
            'total' => $query()->count(),
            'counts' => ['funded' => $account->fundedLoans($partner)->count(), 'brokered' => $account->brokeredLoans($partner)->count()],
        ]);
    }

    public function partnerLoan(Request $request, PartnerAccount $account, Loan $loan): View
    {
        $partner = $account->partnerFor($request->user());
        abort_unless($partner && $account->visibleLoans($partner)->whereKey($loan->id)->exists(), 404);

        return view('home.account.partner.loan', $account->loanDetail($partner, $loan) + ['partner' => $partner]);
    }

    public function partnerEarnings(Request $request, PartnerAccount $account): View
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return $this->partnerSetup('Earnings', 'home');
        }
        $year = $request->integer('year') ?: null;

        return view('home.account.partner.earnings', ['partner' => $partner, 'year' => $year] + $account->earnings($partner, $year));
    }

    public function partnerStatement(Request $request, PartnerAccount $account): View
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return $this->partnerSetup('Statement', 'statement');
        }
        $filters = $this->statementFilters($request);

        return view('home.account.statement', [
            'user' => $request->user(),
            'partner' => $partner,
            'wallet' => $filters['wallet'],
            'from' => $filters['from'],
            'to' => $filters['to'],
            'entries' => $account->statementQuery($partner, ...array_values($filters))->paginate(25)->withQueryString(),
        ]);
    }

    public function partnerStatementCsv(Request $request, PartnerAccount $account): StreamedResponse|RedirectResponse
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return redirect()->route('account.statement');
        }
        $filters = $this->statementFilters($request);
        $rows = $account->statementQuery($partner, ...array_values($filters));
        $name = 'aimzone-statement-' . ($partner->handle ?: $partner->id) . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Entry', 'Wallet', 'Reference', 'Loan', 'Amount (KES)', 'Wallet balance after (KES)', 'Notes']);
            $rows->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $e) {
                    fputcsv($out, [
                        \Illuminate\Support\Carbon::parse($e->transaction_date)->format('Y-m-d'),
                        PartnerPortfolio::label($e),
                        PartnerPortfolio::WALLETS[$e->wallet_slug] ?? $e->wallet_slug,
                        $e->reference,
                        $e->loan_id ? '#' . str_pad((string) $e->loan_id, 5, '0', STR_PAD_LEFT) : '',
                        number_format((float) $e->amount, 2, '.', ''),
                        $e->balance_after !== null ? number_format((float) $e->balance_after, 2, '.', '') : '',
                        $e->notes,
                    ]);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ---- requests: email staff + audit row, nothing stored or posted (Stage 4) ----

    public function partnerFunds(Request $request, PartnerAccount $account): View
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return $this->partnerSetup('Add funds', 'home');
        }

        return view('home.account.partner.funds', [
            'partner' => $partner,
            'reference' => $this->partnerReference($partner),
            'cfg' => PartnerFunding::all(),
        ]);
    }

    public function partnerFundsStore(Request $request, PartnerAccount $account): RedirectResponse
    {
        $partner = $account->partnerFor($request->user());
        abort_unless($partner, 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . PartnerFunding::minAmount(), 'max:1000000000'],
            'method' => ['required', Rule::in(['bank', 'mpesa'])],
            'transaction_code' => ['required', 'string', 'max:60'],
            'sent_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['transaction_code' => 'transaction code', 'sent_on' => 'date sent']);
        $amount = round((float) $data['amount'], 2);

        app(\App\Services\Requests::class)->partner('funding', $partner, $amount, null, $data['transaction_code'], $data['note'] ?? null, $request->user());
        AuditTrail::record('partners', $partner->id, 'create', null, [
            'request' => 'partner_funds_sent', 'amount' => $amount, 'method' => $data['method'],
            'transaction_code' => $data['transaction_code'], 'sent_on' => $data['sent_on'], 'note' => $data['note'] ?? null,
            'reference' => $this->partnerReference($partner),
        ]);
        CustomerNotifier::partnerRequest($partner,
            'Partner funds sent: ' . $partner->name . ' (KES ' . number_format($amount, 2) . ')',
            $partner->name . ' says they sent KES ' . number_format($amount, 2) . ' by ' . ($data['method'] === 'bank' ? 'bank transfer' : 'M-Pesa')
                . ' on ' . $data['sent_on'] . ', code ' . $data['transaction_code'] . ', reference ' . $this->partnerReference($partner) . '.'
                . (! empty($data['note']) ? ' Note: ' . $data['note'] : '')
                . ' Check the bank/M-Pesa statement, then record the contribution on the partner page.'
        );

        return redirect()->route('account.partner.funds')->with('partner_sent', [
            'title' => 'Thanks, we have let our team know',
            'body' => 'We will check for KES ' . number_format($amount, 2) . ' and add it to your capital once it arrives. You will see it on your statement.',
        ]);
    }

    public function partnerWithdraw(Request $request, PartnerAccount $account): View
    {
        if (! $partner = $account->partnerFor($request->user())) {
            return $this->partnerSetup('Withdraw', 'home');
        }

        return view('home.account.partner.withdraw', [
            'partner' => $partner,
            'wallets' => $account->withdrawable($partner),
            'payout' => PartnerAccount::payoutMethod($partner),
            'min' => PartnerFunding::minAmount(),
        ]);
    }

    public function partnerWithdrawStore(Request $request, PartnerAccount $account): RedirectResponse
    {
        $partner = $account->partnerFor($request->user());
        abort_unless($partner, 404);
        $wallets = $account->withdrawable($partner);
        $payout = PartnerAccount::payoutMethod($partner);
        $data = $request->validate([
            'wallet' => ['required', Rule::in(array_keys($wallets))],
            'amount' => ['required', 'numeric', 'min:' . PartnerFunding::minAmount()],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $amount = round((float) $data['amount'], 2);
        $available = round($wallets[$data['wallet']]['balance'], 2);
        if ($amount > $available) {
            return back()->withInput()->withErrors(['amount' => 'You can request up to KES ' . number_format($available, 2) . ' from ' . $wallets[$data['wallet']]['label'] . '.']);
        }
        if (! $payout) {
            return back()->withInput()->withErrors(['amount' => 'We have no payout details for you yet. Message us to add a bank account or M-Pesa number first.']);
        }

        app(\App\Services\Requests::class)->partner('withdrawal', $partner, $amount, $data['wallet'], null, $data['note'] ?? null, $request->user());
        AuditTrail::record('partners', $partner->id, 'create', null, [
            'request' => 'partner_withdrawal', 'amount' => $amount, 'wallet' => $data['wallet'],
            'available' => $available, 'payout' => $payout['label'], 'note' => $data['note'] ?? null,
        ]);
        CustomerNotifier::partnerRequest($partner,
            'Partner withdrawal request: ' . $partner->name . ' (KES ' . number_format($amount, 2) . ')',
            $partner->name . ' asked to withdraw KES ' . number_format($amount, 2) . ' from their ' . $wallets[$data['wallet']]['label']
                . ' wallet (balance KES ' . number_format($available, 2) . ') to ' . $payout['label'] . '.'
                . (! empty($data['note']) ? ' Note: ' . $data['note'] : '')
                . ' Pay it out, then record the withdrawal on the partner page.'
        );

        return redirect()->route('account.partner.withdraw')->with('partner_sent', [
            'title' => 'Withdrawal request sent',
            'body' => 'Our team will pay KES ' . number_format($amount, 2) . ' to ' . $payout['label'] . ' and record it on your statement. Your balance changes once it is paid.',
        ]);
    }

    // ---- helpers ----

    private function partnerSetup(string $title, string $tab): View
    {
        return view('home.account.partner.setup', ['title' => $title, 'tab' => $tab]);
    }

    private function partnerReference(Partner $partner): string
    {
        return PartnerFunding::reference($partner);
    }

    /** wallet / from / to from the query string, in PartnerAccount::statementQuery's argument order. */
    private function statementFilters(Request $request): array
    {
        $date = function ($v) {
            try {
                return $v ? \Illuminate\Support\Carbon::parse($v)->toDateString() : null;
            } catch (\Throwable $e) {
                return null;
            }
        };
        $wallet = $request->query('wallet');

        return [
            'wallet' => array_key_exists((string) $wallet, PartnerPortfolio::WALLETS) ? $wallet : null,
            'from' => $date($request->query('from')),
            'to' => $date($request->query('to')),
        ];
    }
}
