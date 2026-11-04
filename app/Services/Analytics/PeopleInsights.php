<?php

namespace App\Services\Analytics;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Who pays us back, and from where (owner, 4 Nov 2026). Read-only, from the borrower records and the behaviour
 * profiles (borrower_profiles, analytics:profiles):
 *  - how they earn: salaried (8 to 5), own business, or not told us (borrowers.income_type);
 *  - education they told us (users.education) and digital literacy: how much of the journey they did themselves
 *    in the app (KYC, signing, requests, profile);
 *  - age, how long they have been with us, and the loan size against the income they told us;
 *  - place: the city on their address or the county of their business;
 *  - income gaps: loans bigger than the income they told us, and salaried borrowers who don't pay near payday.
 * Religion, ethnicity, disability and health are never used here (Data Protection Act 2019): literacy and
 * education help us explain, not decide.
 */
class PeopleInsights
{
    public function borrowers(): Collection
    {
        static $cache = null;
        if ($cache) {
            return $cache;
        }
        $rows = DB::table('users as u')
            ->join('model_has_roles as m', fn ($j) => $j->on('m.model_id', '=', 'u.id')->where('m.model_type', User::class))
            ->join('roles as r', fn ($j) => $j->on('r.id', '=', 'm.role_id')->where('r.name', 'borrower'))
            ->leftJoin('borrowers as b', 'b.user_id', '=', 'u.id')
            ->leftJoin('borrower_profiles as p', 'p.user_id', '=', 'u.id')
            ->whereNull('u.deleted_at')->orderByDesc('b.id')
            ->get(['u.id', 'u.name', 'u.phone', 'u.dob', 'u.education', 'u.kyc_submitted_at', 'u.kyc_status', 'b.income_type', 'b.net_salary', 'b.gross_salary', 'b.pay_day',
                'b.pay_frequency', 'b.profile_completed_at', 'b.job_title', 'p.loans', 'p.on_time_rate', 'p.avg_days_late', 'p.score', 'p.bad_loans', 'p.largest']);
        $rows = $rows->unique('id')->values();
        $ids = $rows->pluck('id');
        $signed = DB::table('loans')->whereIn('user_id', $ids)->whereNotNull('agreement_signed_at')->groupBy('user_id')->pluck(DB::raw('count(*)'), 'user_id');
        $requests = DB::table('borrower_requests')->whereIn('user_id', $ids)->groupBy('user_id')->pluck(DB::raw('count(*)'), 'user_id');
        $avgLoan = DB::table('loans')->whereIn('user_id', $ids)->whereNull('deleted_at')->whereNotIn('status', ['pending', 'rejected'])->groupBy('user_id')->pluck(DB::raw('avg(amount)'), 'user_id');
        $city = DB::table('addresses')->whereIn('user_id', $ids)->whereNull('deleted_at')->whereNotNull('city')->where('city', '!=', '')->orderByDesc('is_primary')->get(['user_id', 'city'])->unique('user_id')->pluck('city', 'user_id');
        $biz = DB::table('businesses')->whereIn('owner_user_id', $ids)->whereNull('deleted_at')->orderByDesc('id')->get(['owner_user_id', 'county', 'city', 'monthly_revenue'])->unique('owner_user_id')->keyBy('owner_user_id');

        return $cache = $rows->map(function ($r) use ($signed, $requests, $avgLoan, $city, $biz) {
            $signals = (int) ! empty($r->kyc_submitted_at) + (int) (($signed[$r->id] ?? 0) > 0) + (int) (($requests[$r->id] ?? 0) > 0) + (int) ! empty($r->profile_completed_at);
            // income they told us: net salary, else gross, else their business's monthly revenue
            $income = (float) ($r->net_salary ?: $r->gross_salary ?: ($biz[$r->id]->monthly_revenue ?? 0));
            $loan = (float) ($avgLoan[$r->id] ?? 0);
            $r->work = self::work($r->income_type);
            $r->literacy = $signals >= 3 ? 'Fluent' : ($signals === 2 ? 'Functional' : 'Basic');
            $r->signals = $signals;
            $r->education_band = self::education($r->education);
            $r->age = $r->dob ? Carbon::parse($r->dob)->age : null;
            $r->age_band = $r->age === null ? 'Not told us' : ($r->age < 25 ? '18–24' : ($r->age < 35 ? '25–34' : ($r->age < 45 ? '35–44' : '45+')));
            $r->tenure = (int) $r->loans <= 1 ? 'First loan' : ((int) $r->loans <= 4 ? '2nd–4th' : '5th and after');
            $r->income = $income;
            $r->avg_loan = $loan;
            $r->loan_to_income = $income > 0 && $loan > 0 ? round($loan / $income, 2) : null;
            $r->lti_band = $r->loan_to_income === null ? 'Income not told' : ($r->loan_to_income <= 0.5 ? 'Up to half a month' : ($r->loan_to_income <= 1 ? 'Half to one month' : 'More than a month\'s income'));
            $b = $biz[$r->id] ?? null;
            $r->place = $city[$r->id] ?? ($b && ($b->county || $b->city) ? trim(($b->city ?: $b->county)) : null);

            return $r;
        });
    }

    public static function work(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'employment', 'employed', 'government', 'salaried' => 'Salaried (8 to 5)',
            'business', 'self_employed', 'self-employed' => 'Own business',
            '' => 'Not told us',
            default => ucfirst((string) $type),
        };
    }

    public static function education(?string $e): string
    {
        $e = strtolower((string) $e);

        return match (true) {
            $e === '' => 'Not told us',
            str_contains($e, 'master') || str_contains($e, 'phd') || str_contains($e, 'doctor') => 'Postgraduate',
            str_contains($e, 'bachelor') || str_contains($e, 'degree') => 'Degree',
            str_contains($e, 'diploma') || str_contains($e, 'certificate') => 'Diploma or certificate',
            str_contains($e, 'secondary') || str_contains($e, 'form') || str_contains($e, 'kcse') => 'Secondary',
            str_contains($e, 'primary') || str_contains($e, 'kcpe') => 'Primary',
            default => ucfirst($e),
        };
    }

    /**
     * On-time rate by a dimension, weighted by loans: [group => [borrowers, loans, on_time %, average score]].
     * Only borrowers with at least one finished loan count towards the rate.
     */
    public function segment(string $dimension): array
    {
        return $this->borrowers()->groupBy($dimension)->map(function ($g) {
            $rated = $g->filter(fn ($r) => $r->on_time_rate !== null && (int) $r->loans > 0);
            $loans = (int) $rated->sum('loans');

            return ['borrowers' => $g->count(), 'loans' => $loans,
                'on_time' => $loans > 0 ? round($rated->sum(fn ($r) => (float) $r->on_time_rate * (int) $r->loans) / $loans, 1) : null,
                'score' => $rated->count() ? round($rated->avg('score'), 0) : null];
        })->sortByDesc(fn ($v) => $v['loans'])->all();
    }

    /** Places, best on-time rate first, with how many borrowers we know the place for. */
    public function places(): array
    {
        $all = $this->borrowers();
        $known = $all->whereNotNull('place');

        return ['known' => $known->count(), 'total' => $all->count(), 'rows' => collect($this->segment('place'))->except([''])->filter(fn ($v, $k) => $k !== '')
            ->map(fn ($v, $k) => $v + ['place' => $k])->sortByDesc(fn ($v) => [$v['on_time'] ?? -1, $v['loans']])->values()->all()];
    }

    /**
     * Gaps: loans bigger than the income they told us; salaried borrowers who pay far from their payday;
     * people who told us no income at all but borrow.
     */
    public function gaps(): array
    {
        $b = $this->borrowers();
        $big = $b->filter(fn ($r) => $r->loan_to_income !== null && $r->loan_to_income > 1)->sortByDesc('loan_to_income')->values();
        $noIncome = $b->filter(fn ($r) => $r->income <= 0 && (int) $r->loans > 0)->count();
        // salaried with a payday: how far, on average, their repayments are from it
        $payday = $b->filter(fn ($r) => $r->work === 'Salaried (8 to 5)' && $r->pay_day)->map(function ($r) {
            $days = DB::table('repayments as x')->join('loans as l', 'l.id', '=', 'x.loan_id')->where('l.user_id', $r->id)->whereNull('x.deleted_at')->where('x.kind', 'cash')
                ->pluck('x.repayment_date')->map(fn ($d) => (int) Carbon::parse($d)->day);
            if ($days->isEmpty()) {
                return null;
            }
            $gap = round($days->avg(fn ($d) => min(abs($d - (int) $r->pay_day), 31 - abs($d - (int) $r->pay_day))), 1);
            $r->payday_gap = $gap;

            return $r;
        })->filter()->filter(fn ($r) => $r->payday_gap > 7)->values();

        return ['big' => $big, 'payday' => $payday, 'no_income' => $noIncome,
            'with_income' => $b->filter(fn ($r) => $r->income > 0)->count(), 'total' => $b->count()];
    }

    /** One borrower: what we know, how they use the app, how they pay. */
    public function person(User $u): array
    {
        $r = $this->borrowers()->firstWhere('id', $u->id);
        $loans = DB::table('loans')->where('user_id', $u->id)->whereNull('deleted_at')->whereNotIn('status', ['pending', 'rejected'])->orderBy('borrow_date')
            ->get(['id', 'amount', 'status', 'borrow_date', 'due_date', 'days_overdue', 'agreement_signed_at']);
        $reps = DB::table('repayments as x')->join('loans as l', 'l.id', '=', 'x.loan_id')->where('l.user_id', $u->id)->whereNull('x.deleted_at')->where('x.kind', 'cash')
            ->orderBy('x.repayment_date')->get(['x.amount', 'x.repayment_date', 'x.created_at', 'x.loan_id']);
        $hours = $reps->groupBy(fn ($x) => ($h = (int) Carbon::parse($x->created_at)->format('G')) < 9 ? 'Before 9 am' : ($h < 17 ? '9 am to 5 pm' : 'After 5 pm'))->map->count();
        $byMonth = $reps->groupBy(fn ($x) => Carbon::parse($x->repayment_date)->format('Y-m'))->map(fn ($g) => round($g->sum('amount'), 2))->sortKeys()->take(-8);
        $borrowed = $loans->groupBy(fn ($l) => Carbon::parse($l->borrow_date)->format('Y-m'))->map(fn ($g) => round($g->sum('amount'), 2));

        return ['row' => $r, 'loans' => $loans, 'repayments' => $reps->count(), 'paid' => round($reps->sum('amount'), 2),
            'hours' => ['Before 9 am' => $hours['Before 9 am'] ?? 0, '9 am to 5 pm' => $hours['9 am to 5 pm'] ?? 0, 'After 5 pm' => $hours['After 5 pm'] ?? 0],
            'months' => $byMonth->keys()->merge($borrowed->keys())->unique()->sort()->take(-8)->values()->map(fn ($m) => ['m' => Carbon::createFromFormat('Y-m-d', $m . '-01')->format('M y'),
                'paid' => $byMonth[$m] ?? 0, 'borrowed' => $borrowed[$m] ?? 0])->all(),
            'signals' => [
                'Did their own KYC in the app' => ! empty($r?->kyc_submitted_at),
                'Signed a loan agreement online' => $loans->contains(fn ($l) => ! empty($l->agreement_signed_at)),
                'Sent a request themselves (top-up, extension, plan)' => DB::table('borrower_requests')->where('user_id', $u->id)->exists(),
                'Completed their own profile' => ! empty($r?->profile_completed_at),
            ]];
    }
}
