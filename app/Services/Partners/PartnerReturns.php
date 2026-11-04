<?php

namespace App\Services\Partners;

use App\Models\Investment;
use App\Models\Partner;
use App\Services\PartnerAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A partner's returns in investor terms (owner, 4 Nov 2026): net asset value, deployed capital and dry powder,
 * realised (paid out) and accrued (earned, not paid) returns, MOIC, net yield, run-rate, utilisation, the tranches
 * and where each is lent, the distributions paid and the next one, and how a distribution is built. Read-only,
 * from the partner journal (partner_transactions), the lines (PartnerLines) and the payout requests.
 */
class PartnerReturns
{
    public const CORPORATE_TYPES = ['corporate', 'company', 'business', 'sacco', 'group', 'institution'];

    public function __construct(private PartnerLines $lines) {}

    public function summary(Partner $partner): array
    {
        $j = DB::table('partner_transactions')->where('partner_id', $partner->id);
        $sum = fn (array $types) => round((float) (clone $j)->whereIn('type', $types)->sum('amount'), 2);
        $capitalIn = $sum(['contribution']);
        $earned = $sum(PartnerAccount::EARNING_TYPES);
        $paidOut = abs($sum(PartnerAccount::PAYOUT_TYPES));
        $wallet = fn (string $slug) => round((float) (clone $j)->where('wallet_slug', $slug)->sum('amount'), 2);
        $principal = $wallet('principal');
        $accrued = max(0, $wallet('interest') + $wallet('broker_fee'));
        $deployed = $partner->lentOut();
        $dry = max(0, round($principal - $deployed, 2));
        $nav = round($principal + $accrued, 2);
        $first = (clone $j)->where('type', 'contribution')->min('transaction_date');
        $years = $first ? max(1 / 12, Carbon::parse($first)->diffInDays(now()) / 365) : null;
        $recent = round((float) (clone $j)->whereIn('type', PartnerAccount::EARNING_TYPES)->where('transaction_date', '>=', now()->subDays(90)->toDateString())->sum('amount'), 2);

        return [
            'capital_in' => $capitalIn, 'earned' => $earned, 'paid_out' => $paidOut, 'accrued' => $accrued, 'deployed' => $deployed, 'dry_powder' => $dry, 'nav' => $nav,
            'gain' => round($nav + $paidOut - $capitalIn, 2),
            // multiple on invested capital: what they hold plus what they took out, per shilling put in
            'moic' => $capitalIn > 0 ? round(($nav + $paidOut) / $capitalIn, 2) : null,
            // earnings per year on the capital put in, since the first deposit (annualised only after 3 months: a few
            // weeks would overstate it); before that, the return to date
            'net_yield' => $capitalIn > 0 && $years && $years >= 0.25 ? round($earned / $capitalIn / $years * 100, 1) : null,
            'return_to_date' => $capitalIn > 0 ? round($earned / $capitalIn * 100, 1) : null,
            'run_rate' => round($recent / 3, 2),
            'utilisation' => $deployed + $dry > 0 ? round($deployed / ($deployed + $dry) * 100) : null,
            'since' => $first,
            'corporate' => in_array(strtolower((string) $partner->type), self::CORPORATE_TYPES, true),
        ];
    }

    /** Net asset value and capital put in at the end of each of the last $months months. */
    public function navByMonth(Partner $partner, int $months = 8): array
    {
        $rows = DB::table('partner_transactions')->where('partner_id', $partner->id)
            ->whereIn('wallet_slug', ['principal', 'interest', 'broker_fee'])
            ->selectRaw("date_format(transaction_date, '%Y-%m') as m, sum(amount) as total, sum(case when type = 'contribution' then amount else 0 end) as capital")
            ->groupBy('m')->orderBy('m')->get()->keyBy('m');
        $out = [];
        $nav = $cap = 0.0;
        $start = now()->startOfMonth()->subMonths($months - 1);
        foreach ($rows as $m => $r) {
            if ($m < $start->format('Y-m')) {
                $nav += (float) $r->total;
                $cap += (float) $r->capital;
            }
        }
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i)->format('Y-m');
            $nav += (float) ($rows[$m]->total ?? 0);
            $cap += (float) ($rows[$m]->capital ?? 0);
            $out[] = ['m' => Carbon::createFromFormat('Y-m-d', $m . '-01')->format('M'), 'nav' => round($nav, 2), 'capital' => round($cap, 2)];
        }

        return $out;
    }

    /** Each tranche (partner line): what is in it, what is out on loans, what came back and what it earned. */
    public function tranches(Partner $partner): array
    {
        $ids = DB::table('investment_fundings')->where('partner_id', $partner->id)->pluck('investment_id');

        return Investment::whereIn('id', $ids)->where('return_type', '!=', 'house')->orderByDesc('id')->get()->map(function (Investment $line) {
            $s = $this->lines->status($line);
            $rate = $line->partner_share_rate !== null ? rtrim(rtrim((string) $line->partner_share_rate, '0'), '.') . '% of interest' : ($line->return_type === 'fixed' ? 'fixed rate' : 'profit share');

            return ['id' => $line->id, 'name' => $line->name, 'book' => $line->facility_type === 'long_term' ? 'Long-term book' : 'Short-term loans', 'committed' => $s['committed'],
                'out' => $s['out'], 'back' => $s['back'], 'earned' => $s['earned'], 'yield' => $rate, 'matures' => $line->maturity_date, 'loans' => $s['loans'], 'open' => $s['open_loans'],
                'status' => $line->status];
        })->all();
    }

    /**
     * Distributions: paid (journal payouts with their reference), being paid (requests), and the latest repayment
     * split that paid this partner (how a distribution is built).
     */
    public function distributions(Partner $partner, int $limit = 12): array
    {
        $paid = DB::table('partner_transactions')->where('partner_id', $partner->id)->whereIn('type', PartnerAccount::PAYOUT_TYPES)
            ->orderByDesc('transaction_date')->orderByDesc('id')->limit($limit)->get(['amount', 'wallet_slug', 'reference', 'transaction_date', 'notes']);
        // a payout's bank or M-Pesa reference is on its request (PAYOUT-PR{id})
        $refs = DB::table('partner_requests')->whereIn('id', $paid->map(fn ($p) => preg_match('/^PAYOUT-PR(\d+)/', (string) $p->reference, $m) ? (int) $m[1] : 0)->filter())
            ->pluck('bank_reference', 'id');
        $paid->each(function ($p) use ($refs) {
            $p->bank_reference = preg_match('/^PAYOUT-PR(\d+)/', (string) $p->reference, $m) ? ($refs[(int) $m[1]] ?? null) : null;
        });
        $requests = DB::table('partner_requests')->where('partner_id', $partner->id)->where('type', 'withdrawal')
            ->whereIn('status', ['pending', 'prepared'])->orderBy('id')->get(['amount', 'wallet_slug', 'status', 'created_at', 'paid_on']);
        $last = DB::table('partner_transactions')->where('partner_id', $partner->id)->whereNotNull('repayment_id')
            ->whereIn('type', PartnerAccount::EARNING_TYPES)->orderByDesc('transaction_date')->orderByDesc('id')->first(['repayment_id', 'loan_id', 'transaction_date']);
        $built = null;
        if ($last) {
            $rows = DB::table('partner_transactions as t')->join('partners as p', 'p.id', '=', 't.partner_id')->where('t.repayment_id', $last->repayment_id)
                ->where('t.reference', 'like', 'SPLIT-R' . $last->repayment_id . '-%')
                ->selectRaw('p.id as pid, p.is_house_account as house, t.type, sum(t.amount) as amount')->groupBy('p.id', 'p.is_house_account', 't.type')->get();
            $mine = $rows->where('pid', $partner->id);
            $built = ['loan_id' => $last->loan_id, 'date' => $last->transaction_date, 'collected' => round((float) $rows->sum('amount'), 2),
                'house' => round((float) $rows->where('house', 1)->sum('amount'), 2), 'others' => round((float) $rows->where('house', 0)->where('pid', '!=', $partner->id)->sum('amount'), 2),
                'interest' => round((float) $mine->whereIn('type', ['profit_distribution', 'interest'])->sum('amount'), 2),
                'penalty' => round((float) $mine->where('type', 'penalty_share')->sum('amount'), 2), 'fee' => round((float) $mine->where('type', 'broker_fee_earned')->sum('amount'), 2)];
        }

        return ['paid' => $paid, 'requests' => $requests, 'next' => round((float) $requests->sum('amount'), 2), 'built' => $built,
            'paid_total' => round(abs((float) DB::table('partner_transactions')->where('partner_id', $partner->id)->whereIn('type', PartnerAccount::PAYOUT_TYPES)->sum('amount')), 2),
            'count' => DB::table('partner_transactions')->where('partner_id', $partner->id)->whereIn('type', PartnerAccount::PAYOUT_TYPES)->count()];
    }

    /** Corporate view: where the money is by book, and the credit risk of the loans the partner funds. */
    public function treasury(Partner $partner, PartnerAccount $account): array
    {
        $loans = $account->fundedLoans($partner)->get(['loans.id', 'loans.status', 'loans.days_overdue']);
        $open = $loans->whereIn('status', ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery']);
        $late30 = $open->filter(fn ($l) => (int) $l->days_overdue > 30)->count();

        return ['loans' => $loans->count(), 'open' => $open->count(), 'par30' => $open->count() ? round($late30 / $open->count() * 100, 1) : 0.0,
            'written_off' => $loans->where('status', 'written_off')->count()];
    }
}
