<?php

namespace App\Services\Partners;

use App\Models\Investment;
use App\Models\Loan;
use App\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every partner's numbers in one place (owner, 15 Nov 2026), from the partner journal: principal they put in,
 * interest and penalty shares and introducer fees they earned, principal that came back to them, what was paid out to
 * them, what they hold now (journal balance per wallet) and what is out on loans; and the loans they are in.
 */
class PartnerNumbers
{
    /** One row per partner, the house last. */
    public function all(): Collection
    {
        $sums = DB::table('partner_transactions')->selectRaw('partner_id, type, wallet_slug, sum(amount) as total')
            ->groupBy('partner_id', 'type', 'wallet_slug')->get()->groupBy('partner_id');
        $loanCounts = $this->loanRoles()->groupBy('partner_id')->map(fn ($rows) => $rows->pluck('loan_id')->unique()->count());
        $lines = app(PartnerLines::class);

        return Partner::orderBy('is_house_account')->orderBy('name')->get()->map(function (Partner $p) use ($sums, $loanCounts, $lines) {
            $rows = $sums->get($p->id, collect());
            $s = fn (callable $f) => round((float) $rows->filter($f)->sum('total'), 2);
            $out = Investment::whereIn('return_type', ['loan_share', 'fixed_return', 'fixed_rate'])
                ->whereHas('fundings', fn ($q) => $q->where('partner_id', $p->id))->get()
                ->sum(fn ($l) => $lines->status($l)['out']);

            return (object) [
                'partner' => $p,
                'put_in' => $s(fn ($r) => $r->type === 'contribution'),
                'interest' => $s(fn ($r) => $r->type === 'profit_distribution'),
                'penalties' => $s(fn ($r) => $r->type === 'penalty_share'),
                'fees' => $s(fn ($r) => $r->type === 'broker_fee_earned'),
                'principal_back' => $s(fn ($r) => $r->type === 'principal_returned'),
                'paid_out' => abs($s(fn ($r) => $r->type === 'withdrawal')),
                'held' => $s(fn ($r) => true),
                'out_on_loans' => round($out, 2),
                'loans' => (int) ($loanCounts[$p->id] ?? 0),
            ];
        });
    }

    /** The loans one partner is in: [loan, roles[], earned on it]. */
    public function loansFor(Partner $p): Collection
    {
        $roles = $this->loanRoles($p->id)->groupBy('loan_id');
        $earned = DB::table('partner_transactions')->where('partner_id', $p->id)->whereNotNull('loan_id')->where('type', '!=', 'withdrawal')
            ->selectRaw('loan_id, sum(amount) as total')->groupBy('loan_id')->pluck('total', 'loan_id');

        return Loan::withTrashed()->with('user:id,name')->whereIn('id', $roles->keys())->orderByDesc('id')->get()
            ->map(fn (Loan $l) => (object) ['loan' => $l, 'roles' => $roles[$l->id]->pluck('role')->unique()->values()->all(),
                'earned' => round((float) ($earned[$l->id] ?? 0), 2)]);
    }

    /** [partner_id, loan_id, role] for every way a partner is in a loan. */
    private function loanRoles(?int $partnerId = null): Collection
    {
        $lineOwners = DB::table('investment_fundings as f')->join('partners as p', 'p.id', '=', 'f.partner_id')->where('p.is_house_account', false)
            ->when($partnerId, fn ($q) => $q->where('f.partner_id', $partnerId))->pluck('f.partner_id', 'f.investment_id');
        $rows = collect();

        DB::table('loans')->where('broker_status', 1)->whereNotNull('partner_id')->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
            ->get(['id', 'partner_id'])->each(fn ($l) => $rows->push((object) ['partner_id' => (int) $l->partner_id, 'loan_id' => (int) $l->id, 'role' => 'introduced']));
        DB::table('loan_fundings')->get(['loan_id', 'partner_id', 'investment_id'])->each(function ($f) use ($rows, $lineOwners, $partnerId) {
            $owner = $f->partner_id ?: ($f->investment_id ? ($lineOwners[$f->investment_id] ?? null) : null);
            if ($owner && (! $partnerId || (int) $owner === $partnerId)) {
                $rows->push((object) ['partner_id' => (int) $owner, 'loan_id' => (int) $f->loan_id, 'role' => 'funded part']);
            }
        });
        DB::table('disbursements')->whereNull('deleted_at')->whereIn('investment_id', $lineOwners->keys())->get(['loan_id', 'investment_id'])
            ->each(fn ($d) => $rows->push((object) ['partner_id' => (int) $lineOwners[$d->investment_id], 'loan_id' => (int) $d->loan_id, 'role' => 'funded']));
        if (Schema::hasTable('facility_split_parties')) {
            DB::table('facility_split_parties as fp')->join('facility_splits as fs', 'fs.id', '=', 'fp.facility_split_id')
                ->when($partnerId, fn ($q) => $q->where('fp.partner_id', $partnerId))->get(['fp.partner_id', 'fs.loan_id'])
                ->each(fn ($r) => $rows->push((object) ['partner_id' => (int) $r->partner_id, 'loan_id' => (int) $r->loan_id, 'role' => 'facility share']));
        }
        DB::table('partner_transactions')->whereNotNull('loan_id')->when($partnerId, fn ($q) => $q->where('partner_id', $partnerId))
            ->where('type', '!=', 'withdrawal')->select('partner_id', 'loan_id')->distinct()->get()
            ->each(fn ($t) => $rows->push((object) ['partner_id' => (int) $t->partner_id, 'loan_id' => (int) $t->loan_id, 'role' => 'earned on']));

        return $rows;
    }
}
