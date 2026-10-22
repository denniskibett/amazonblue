<?php

namespace App\Support;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\User;
use App\Services\CustomerWallets;
use App\Services\Ledger;
use App\Services\LedgerReports;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The cards at the top of each staff page (owner, 22 Oct 2026): up to six TailAdmin metric cards per page, worked
 * out for the signed-in user. Each card is ['label', 'value' (HTML), 'icon', 'hint', 'delta', 'tone', 'href'].
 * A card that fails to compute is left out; a page never breaks because of its cards.
 */
class PageMetrics
{
    public const OPEN = [Loan::STATUS_DISBURSED, Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_FORBEARANCE, Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY];

    public static function for(string $page, ?User $user): array
    {
        if (! $user) {
            return [];
        }
        $m = new self($user);
        $method = 'page' . str_replace(' ', '', ucwords(str_replace(['-', '.', '_'], ' ', $page)));
        if (! method_exists($m, $method)) {
            return [];
        }
        try {
            return array_values(array_filter($m->{$method}()));
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public function __construct(private User $user) {}

    // ------------------------------------------------------------------ pages

    public function pageDashboard(): array
    {
        $u = $this->user;
        if ($u->hasRole('broker')) {
            $ids = $this->brokerBorrowers();
            $open = Loan::whereIn('user_id', $ids)->whereIn('status', self::OPEN);

            return [
                $this->card('Your clients', self::n(count($ids)), 'users', 'borrowers linked to you'),
                $this->card('Open loans', self::n((clone $open)->count()), 'briefcase', 'disbursed and not repaid'),
                $this->card('Owed by your clients', self::kes($this->owedFor((clone $open)->pluck('id')->all())), 'wallet', 'principal + interest + penalty today'),
                $this->card('Overdue', self::n((clone $open)->whereIn('status', [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED])->count()), 'alert', 'call them before it grows', null, 'warn'),
                $this->card('Fees earned', self::kes($this->brokerEarned()), 'coins', 'your share of interest and penalty'),
                $this->card('Repaid loans', self::n(Loan::whereIn('user_id', $ids)->where('status', Loan::STATUS_REPAID)->count()), 'check-circle', 'closed in full'),
            ];
        }
        if ($u->hasRole('teller') && ! $u->hasAnyRole(User::BACK_OFFICE_ROLES)) {
            return $this->pageReceivePayment() + [];
        }
        if ($u->hasRole('recovery-officer') && ! $u->hasAnyRole(['super-admin', 'admin', 'manager'])) {
            return $this->pageCollections();
        }
        if ($u->hasRole('loan-officer') && ! $u->hasAnyRole(['super-admin', 'admin', 'manager'])) {
            return $this->pageInbox();
        }
        $p = $this->portfolio();
        [$month, $last] = [$this->cashOut(now()->startOfMonth(), now()), $this->cashOut(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow())];
        [$in, $inLast] = [$this->cashIn(now()->startOfMonth(), now()), $this->cashIn(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow())];

        return [
            $this->card('Owed to us today', self::kes($p['owed']), 'wallet', $p['open_loans'] . ' open loans', null, 'neutral', $this->route('staff.portfolio', 'view reports')),
            $this->card('Disbursed this month', self::kes($month), 'send', 'cash sent', self::change($month, $last), $month >= $last ? 'up' : 'down'),
            $this->card('Collected this month', self::kes($in), 'hand-coins', 'cash received', self::change($in, $inLast), $in >= $inLast ? 'up' : 'down'),
            $this->card('Portfolio at risk', $p['par30'] . '%', 'trending-down', 'owed 30+ days late · PAR90 ' . $p['par90'] . '%', null, 'neutral', $this->route('staff.collections', 'view recovery cases')),
            $this->card('Non-performing', self::kes($p['npl_owed']), 'alert', $p['npl_loans'] . ' loans past their default day', null, 'neutral', $this->route('staff.collections', 'view recovery cases')),
            $this->card('Active borrowers', self::n(Loan::whereIn('status', self::OPEN)->distinct('user_id')->count('user_id')), 'users', User::role('borrower')->where('created_at', '>=', now()->startOfMonth())->count() . ' new this month'),
        ];
    }

    public function pagePortfolio(): array
    {
        $p = $this->portfolio();

        return [
            $this->card('Owed to us today', self::kes($p['owed']), 'wallet', $p['open_loans'] . ' open loans · principal ' . number_format($p['principal'])),
            $this->card('Portfolio at risk', $p['par30'] . '%', 'trending-down', 'PAR1 ' . $p['par1'] . '% · PAR90 ' . $p['par90'] . '%'),
            $this->card('Non-performing', self::kes($p['npl_owed']), 'alert', $p['npl_loans'] . ' loans past their default day'),
            $this->card('Cash lent', self::kes($p['cash_lent']), 'send', 'all money sent to borrowers'),
            $this->card('Cash repaid', self::kes($p['cash_repaid']), 'hand-coins', 'all money received'),
            $this->card('Interest and penalty booked', self::kes($p['interest_income'] + $p['penalty_income']), 'coins', 'charged to loans (ledger)'),
        ];
    }

    public function pageLoans(): array
    {
        $q = $this->scopedLoans();
        $open = (clone $q)->whereIn('status', self::OPEN);

        return [
            $this->card('Loans', self::n((clone $q)->count()), 'briefcase', (clone $q)->where('created_at', '>=', now()->startOfMonth())->count() . ' this month'),
            $this->card('Open', self::n((clone $open)->count()), 'clock', 'disbursed and not repaid'),
            $this->card('Owed today', self::kes($this->owedFor((clone $open)->pluck('id')->all())), 'wallet', 'principal + interest + penalty'),
            $this->card('Overdue or defaulted', self::n((clone $q)->whereIn('status', [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY])->count()), 'alert', 'past their due date', null, 'warn'),
            $this->card('Waiting', self::n((clone $q)->whereIn('status', [Loan::STATUS_PENDING, Loan::STATUS_APPROVED])->count()), 'inbox', 'pending approval or disbursement'),
            $this->card('Repaid', self::n((clone $q)->where('status', Loan::STATUS_REPAID)->count()), 'check-circle', 'closed in full'),
        ];
    }

    public function pageUsers(): array
    {
        $staff = User::role(User::STAFF_ROLES)->count();

        return [
            $this->card('All users', self::n(User::count()), 'users', User::where('created_at', '>=', now()->startOfMonth())->count() . ' joined this month'),
            $this->card('Borrowers', self::n(User::role('borrower')->count()), 'user', User::role('borrower')->whereHas('loans', fn ($q) => $q->whereIn('status', self::OPEN))->count() . ' with an open loan'),
            $this->card('Staff', self::n($staff), 'shield', 'every staff role'),
            $this->card('Partners', self::n(User::role('partner')->count()), 'briefcase', 'investor accounts'),
        ];
    }

    public function pageKycReviews(): array
    {
        $by = fn ($s) => User::where('kyc_status', $s)->count();

        return [
            $this->card('Waiting for review', self::n($by('submitted')), 'inbox', 'oldest first', null, $by('submitted') ? 'warn' : 'neutral'),
            $this->card('Approved', self::n($by('approved')), 'user-check', User::where('kyc_status', 'approved')->where('kyc_reviewed_at', '>=', now()->startOfMonth())->count() . ' this month'),
            $this->card('Rejected', self::n($by('rejected')), 'x-circle', 'asked to fix and resubmit'),
            $this->card('Not submitted', self::n(User::role('borrower')->whereNull('kyc_status')->count()), 'clock', 'borrowers yet to verify'),
        ];
    }

    public function pageBusinessReviews(): array
    {
        if (! Schema::hasTable('businesses')) {
            return [];
        }
        $by = fn ($s) => DB::table('businesses')->whereNull('deleted_at')->where('status', $s)->count();

        return [
            $this->card('Waiting for review', self::n($by('submitted')), 'inbox', 'submitted by owners', null, $by('submitted') ? 'warn' : 'neutral'),
            $this->card('Approved', self::n($by('approved')), 'building', 'can borrow'),
            $this->card('Rejected', self::n($by('rejected')), 'x-circle', 'asked to fix'),
            $this->card('Drafts', self::n($by('draft')), 'file', 'started, not submitted'),
        ];
    }

    public function pageCases(): array
    {
        $open = \App\Models\DebtRecoveryCase::open();
        $closed = fn ($slug) => \App\Models\DebtRecoveryCase::whereHas('status', fn ($q) => $q->where('slug', $slug))->count();

        return [
            $this->card('Open cases', self::n((clone $open)->count()), 'briefcase', (clone $open)->whereNull('assigned_to')->count() . ' without an officer', null, 'neutral'),
            $this->card('Debt in recovery', self::kes((float) (clone $open)->sum('total_debt_amount')), 'wallet', 'recorded when the case opened'),
            $this->card('Recovered', self::n($closed('recovered')), 'check-circle', 'cases closed as paid'),
            $this->card('Written off', self::n($closed('written_off')), 'x-circle', 'cases closed as lost'),
            $this->card('Promised this week', self::n(DB::table('recovery_actions')->whereNull('deleted_at')->whereBetween('promised_date', [now()->startOfWeek(), now()->endOfWeek()])->count()), 'calendar', 'payments borrowers promised'),
            $this->card('NPL loans', self::n(Loan::where('is_non_performing', true)->count()), 'alert', 'past their default day'),
        ];
    }

    public function pageInbox(): array
    {
        $u = $this->user;

        return [
            $u->can('view loans') ? $this->card('Loans to approve', self::n(Loan::where('status', Loan::STATUS_PENDING)->count()), 'inbox', self::kes0((float) Loan::where('status', Loan::STATUS_PENDING)->sum('amount')) . ' asked', null, 'neutral', $this->route('staff.approvals')) : null,
            $u->can('view disbursements') ? $this->card('Waiting to be sent', self::n(Loan::where('status', Loan::STATUS_APPROVED)->count()), 'send', 'approved, not disbursed', null, 'neutral', $this->route('staff.disbursements.queue')) : null,
            $u->can('view users') ? $this->card('KYC to review', self::n(User::where('kyc_status', 'submitted')->count()), 'user-check', 'borrower verifications', null, 'neutral', $this->route('kyc-reviews.index')) : null,
            $u->canAny(['rollover loans', 'edit partners']) && Schema::hasTable('borrower_requests') ? $this->card('Requests', self::n(DB::table('borrower_requests')->where('status', 'open')->count() + DB::table('partner_requests')->whereIn('status', ['pending', 'prepared'])->count()), 'mail', 'borrowers and partners', null, 'neutral', $this->route('staff.requests')) : null,
            Schema::hasTable('approvals') ? $this->card('Second sign-offs', self::n(DB::table('approvals')->where('status', 'pending')->where('requested_by', '!=', $u->id)->count()), 'shield', 'waiting on you', null, 'neutral', $this->route('staff.sign-offs')) : null,
            $u->can('view loans') ? $this->card('Due this week', self::n(Loan::whereIn('status', self::OPEN)->whereBetween('due_date', [now()->startOfDay(), now()->addDays(7)->endOfDay()])->count()), 'calendar', 'call before the due date') : null,
        ];
    }

    public function pageApprovals(): array
    {
        $pending = Loan::where('status', Loan::STATUS_PENDING);
        $decided = fn ($s) => DB::table('audit_logs')->where('table_name', 'loans')->where('new_values', 'like', '%"status":"' . $s . '"%')->where('created_at', '>=', now()->startOfMonth())->count();

        return [
            $this->card('Waiting for a decision', self::n((clone $pending)->count()), 'inbox', 'oldest first', null, (clone $pending)->count() ? 'warn' : 'neutral'),
            $this->card('Amount asked', self::kes((float) (clone $pending)->sum('amount')), 'wallet', 'all pending loans'),
            $this->card('Approved this month', self::n($decided('approved')), 'check-circle', 'then sent to the queue'),
            $this->card('Rejected this month', self::n($decided('rejected')), 'x-circle', 'with the reason saved'),
        ];
    }

    public function pageDisbursementsQueue(): array
    {
        $approved = Loan::where('status', Loan::STATUS_APPROVED);

        return [
            $this->card('Waiting to be sent', self::n((clone $approved)->count()), 'inbox', 'approved loans'),
            $this->card('Amount waiting', self::kes((float) (clone $approved)->sum('amount')), 'wallet', 'to send'),
            $this->card('Sent today', self::kes($this->cashOut(now()->startOfDay(), now())), 'send', $this->cashOutCount(now()->startOfDay(), now()) . ' disbursements'),
            $this->card('Sent this month', self::kes($this->cashOut(now()->startOfMonth(), now())), 'calendar', $this->cashOutCount(now()->startOfMonth(), now()) . ' disbursements'),
        ];
    }

    public function pageReceivePayment(): array
    {
        return [
            $this->card('Collected today', self::kes($this->cashIn(now()->startOfDay(), now())), 'hand-coins', $this->cashInCount(now()->startOfDay(), now()) . ' payments'),
            $this->card('Collected this month', self::kes($this->cashIn(now()->startOfMonth(), now())), 'calendar', $this->cashInCount(now()->startOfMonth(), now()) . ' payments'),
            $this->card('Due today', self::n(Loan::whereIn('status', self::OPEN)->whereDate('due_date', now()->toDateString())->count()), 'clock', 'loans whose cycle ends today'),
            $this->card('Sent today', self::kes($this->cashOut(now()->startOfDay(), now())), 'send', 'disbursed by the desk'),
        ];
    }

    public function pageCollections(): array
    {
        $late = Loan::whereIn('status', [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED, Loan::STATUS_RECOVERY]);
        $days = fn ($from, $to) => (clone $late)->whereNotNull('due_date')->whereBetween('due_date', [now()->subDays($to)->startOfDay(), now()->subDays($from)->endOfDay()])->count();
        $mine = \App\Models\DebtRecoveryCase::open()->where('assigned_to', $this->user->id)->count();

        return [
            $this->card('Late loans', self::n((clone $late)->count()), 'alert', 'overdue, defaulted or in recovery', null, 'warn'),
            $this->card('1 to 7 days late', self::n($days(1, 7)), 'clock', 'call today'),
            $this->card('8 to 30 days', self::n($days(8, 30)), 'clock', 'visit or plan'),
            $this->card('Over 30 days', self::n((clone $late)->where('due_date', '<', now()->subDays(30))->count()), 'trending-down', 'recovery cases'),
            $this->card('Owed on late loans', self::kes($this->owedFor((clone $late)->pluck('id')->all())), 'wallet', 'today, no penalty days'),
            $this->card('Cases on you', self::n($mine), 'user', 'assigned to you'),
        ];
    }

    public function pageRequests(): array
    {
        if (! Schema::hasTable('borrower_requests')) {
            return [];
        }
        $month = now()->startOfMonth();

        return [
            $this->card('Borrowers waiting', self::n(DB::table('borrower_requests')->where('status', 'open')->count()), 'mail', 'extensions and plans'),
            $this->card('Partners waiting', self::n(DB::table('partner_requests')->whereIn('status', ['pending', 'prepared'])->count()), 'briefcase', 'funds sent and withdrawals'),
            $this->card('Agreed this month', self::n(DB::table('borrower_requests')->where('status', 'approved')->where('decided_at', '>=', $month)->count()), 'check-circle', 'borrower requests'),
            $this->card('Declined this month', self::n(DB::table('borrower_requests')->where('status', 'declined')->where('decided_at', '>=', $month)->count()), 'x-circle', 'with a note to the borrower'),
        ];
    }

    public function pageSignOffs(): array
    {
        if (! Schema::hasTable('approvals')) {
            return [];
        }
        $q = DB::table('approvals');

        return [
            $this->card('Waiting', self::n((clone $q)->where('status', 'pending')->count()), 'shield', 'second sign-offs'),
            $this->card('Waiting on you', self::n((clone $q)->where('status', 'pending')->where('requested_by', '!=', $this->user->id)->count()), 'user-check', 'someone else asked'),
            $this->card('Approved this month', self::n((clone $q)->where('status', 'approved')->where('decided_at', '>=', now()->startOfMonth())->count()), 'check-circle', 'then carried out'),
            $this->card('Rejected this month', self::n((clone $q)->where('status', 'rejected')->where('decided_at', '>=', now()->startOfMonth())->count()), 'x-circle', 'nothing changed'),
        ];
    }

    public function pagePayments(): array
    {
        if (! Schema::hasTable('mpesa_transactions')) {
            return [];
        }
        $t = DB::table('mpesa_transactions');

        return [
            $this->card('Received today', self::kes((float) (clone $t)->whereIn('status', ['matched', 'received'])->where('created_at', '>=', now()->startOfDay())->sum('amount')), 'phone', 'M-Pesa'),
            $this->card('To assign', self::n((clone $t)->whereIn('status', ['unmatched', 'received'])->whereNull('repayment_id')->count()), 'inbox', 'not matched to a loan', null, 'warn'),
            $this->card('Codes to check', self::n(Schema::hasTable('payment_claims') ? DB::table('payment_claims')->where('status', 'checking')->count() : 0), 'search', 'entered by borrowers'),
            $this->card('Received this month', self::kes((float) (clone $t)->whereIn('status', ['matched', 'received'])->where('created_at', '>=', now()->startOfMonth())->sum('amount')), 'calendar', config('mpesa.enabled') ? 'paybill on' : 'M-Pesa is off'),
        ];
    }

    public function pageEndOfDay(): array
    {
        if (! Schema::hasTable('teller_day_closes')) {
            return [];
        }
        $t = DB::table('teller_day_closes');

        return [
            $this->card('Cash in today', self::kes($this->cashIn(now()->startOfDay(), now())), 'hand-coins', 'repayments recorded'),
            $this->card('Cash out today', self::kes($this->cashOut(now()->startOfDay(), now())), 'send', 'disbursements recorded'),
            $this->card('Closes to approve', self::n((clone $t)->where('status', 'closed')->count()), 'inbox', 'tellers waiting'),
            $this->card('Variance this month', self::kes((float) (clone $t)->where('day', '>=', now()->startOfMonth()->toDateString())->sum('variance')), 'scale', 'counted minus expected'),
        ];
    }

    public function pageRoles(): array
    {
        return [
            $this->card('Roles', self::n(DB::table('roles')->count()), 'shield', 'staff, borrower and partner'),
            $this->card('Permissions', self::n(DB::table('permissions')->count()), 'key', 'each guards an action'),
            $this->card('Staff', self::n(User::role(User::STAFF_ROLES)->count()), 'users', 'people holding a staff role'),
            $this->card('Without a role', self::n(User::doesntHave('roles')->count()), 'alert', 'cannot sign in to anything', null, User::doesntHave('roles')->count() ? 'warn' : 'neutral'),
        ];
    }

    public function pageAuditLog(): array
    {
        $a = DB::table('audit_logs');
        $money = ['repayments', 'disbursements', 'loans', 'loan_cycles', 'partner_transactions'];

        return [
            $this->card('Changes today', self::n((clone $a)->where('created_at', '>=', now()->startOfDay())->count()), 'list', 'every table'),
            $this->card('Money changes today', self::n((clone $a)->whereIn('table_name', $money)->where('created_at', '>=', now()->startOfDay())->count()), 'coins', 'loans, cycles, payments'),
            $this->card('This week', self::n((clone $a)->where('created_at', '>=', now()->startOfWeek())->count()), 'calendar', 'since Monday'),
            $this->card('People active today', self::n((clone $a)->where('created_at', '>=', now()->startOfDay())->distinct('user_id')->count('user_id')), 'users', 'made a change'),
        ];
    }

    public function pagePartners(): array
    {
        $earned = (float) DB::table('partner_transactions')->whereIn('type', ['broker_fee_earned', 'penalty_share', 'profit_distribution', 'interest'])->sum('amount');

        return [
            $this->card('Partners', self::n(DB::table('partners')->where('is_house_account', 0)->count()), 'briefcase', 'investors and brokers'),
            $this->card('Brokers', self::n(DB::table('partners')->where('broker_rate', '>', 0)->count()), 'users', 'earn a share of loans'),
            $this->card('Earned by partners', self::kes($earned), 'coins', 'interest and penalty shares'),
            $this->card('Withdrawals waiting', self::n(Schema::hasTable('partner_requests') ? DB::table('partner_requests')->where('type', 'withdrawal')->whereIn('status', ['pending', 'prepared'])->count() : 0), 'send', 'asked by partners'),
        ];
    }

    public function pageInvestments(): array
    {
        $i = DB::table('investments');

        return [
            $this->card('Investments', self::n((clone $i)->count()), 'layers', (clone $i)->where('status', 'active')->count() . ' active'),
            $this->card('Invested', self::kes((float) (clone $i)->sum('initial_amount')), 'wallet', 'initial amounts'),
            $this->card('Current value', self::kes((float) (clone $i)->sum('current_value')), 'trending-up', 'as last valued'),
            $this->card('Expected return', self::kes((float) (clone $i)->sum('expected_return')), 'percent', 'over the term'),
        ];
    }

    public function pageProducts(): array
    {
        $top = Loan::whereIn('status', self::OPEN)->select('loan_type_id', DB::raw('count(*) as n'))->groupBy('loan_type_id')->orderByDesc('n')->first();

        return [
            $this->card('Products', self::n(DB::table('loan_types')->count()), 'layers', 'loan types on offer'),
            $this->card('Average rate', round((float) DB::table('loan_types')->avg('interest_rate'), 1) . '%', 'percent', 'interest per period'),
            $this->card('Open loans', self::n(Loan::whereIn('status', self::OPEN)->count()), 'briefcase', 'across all products'),
            $this->card('Most used', e($top ? (string) DB::table('loan_types')->where('id', $top->loan_type_id)->value('name') : '-'), 'star', $top ? $top->n . ' open loans' : ''),
        ];
    }

    public function pageWallets(): array
    {
        $c = Cache::remember('page-metrics:wallets', 600, function () {
            $l = app(Ledger::class);

            return ['tb' => $l->trialBalance()['total'], 'differ' => count($l->checkReceivables()['issues']), 'accounts' => DB::table('wallets')->count()];
        });

        return [
            $this->card('Accounts', self::n($c['accounts']), 'wallet', 'ledger and customer wallets'),
            $this->card('Trial balance', number_format((float) $c['tb'], 4), 'scale', 'must be 0', null, abs((float) $c['tb']) < 0.0001 ? 'up' : 'down'),
            $this->card('Loans that differ', self::n($c['differ']), 'alert', 'ledger vs cycles', null, $c['differ'] ? 'warn' : 'neutral', $this->route('staff.operations-review', 'view ledger')),
            $this->card('Checked', now()->format('H:i'), 'refresh', 'refreshed every 10 minutes'),
        ];
    }

    public function pagePartnerPayouts(): array
    {
        $pt = DB::table('partner_transactions');

        return [
            $this->card('Earned', self::kes((float) (clone $pt)->whereIn('type', ['broker_fee_earned', 'penalty_share', 'profit_distribution', 'interest'])->sum('amount')), 'coins', 'all partners, all time'),
            $this->card('Paid out', self::kes(abs((float) (clone $pt)->whereIn('type', ['withdrawal', 'payout', 'broker_fee_paid'])->sum('amount'))), 'send', 'withdrawals and fee payouts'),
            $this->card('This month', self::kes((float) (clone $pt)->whereIn('type', ['broker_fee_earned', 'penalty_share', 'profit_distribution', 'interest'])->where('transaction_date', '>=', now()->startOfMonth())->sum('amount')), 'calendar', 'earned'),
            $this->card('Withdrawals waiting', self::n(Schema::hasTable('partner_requests') ? DB::table('partner_requests')->where('type', 'withdrawal')->whereIn('status', ['pending', 'prepared'])->count() : 0), 'inbox', 'to prepare or sign off'),
        ];
    }

    public function pageReports(): array
    {
        $p = $this->portfolio();

        return [
            $this->card('Loans this month', self::n(Loan::where('created_at', '>=', now()->startOfMonth())->count()), 'briefcase', 'applications'),
            $this->card('Disbursed this month', self::kes($this->cashOut(now()->startOfMonth(), now())), 'send', 'cash sent'),
            $this->card('Collected this month', self::kes($this->cashIn(now()->startOfMonth(), now())), 'hand-coins', 'cash received'),
            $this->card('Owed today', self::kes($p['owed']), 'wallet', 'PAR30 ' . $p['par30'] . '%'),
        ];
    }

    // ------------------------------------------------------------------ helpers

    private function card(string $label, string $value, string $icon, ?string $hint = null, ?string $delta = null, string $tone = 'neutral', ?string $href = null): array
    {
        return compact('label', 'value', 'icon', 'hint', 'delta', 'tone', 'href');
    }

    private function route(string $name, ?string $permission = null): ?string
    {
        return (! $permission || $this->user->can($permission)) && \Illuminate\Support\Facades\Route::has($name) ? route($name) : null;
    }

    public static function kes(float $v): string
    {
        return 'KES ' . number_format($v, 2);
    }

    public static function kes0(float $v): string
    {
        return 'KES ' . number_format($v, 2);
    }

    public static function n($v): string
    {
        return number_format((float) $v);
    }

    public static function change(float $now, float $before): ?string
    {
        return $before > 0 ? round(abs($now - $before) / $before * 100, 1) . '%' : null;
    }

    private function portfolio(): array
    {
        return Cache::remember('page-metrics:portfolio', 300, fn () => collect(app(LedgerReports::class)->portfolio())->except('rows')->all());
    }

    private function cashOut(Carbon $from, Carbon $to): float
    {
        return round((float) CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at')->whereBetween('disburse_date', [$from->toDateString(), $to->toDateString()]))->sum('amount'), 2);
    }

    private function cashOutCount(Carbon $from, Carbon $to): int
    {
        return CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at')->whereBetween('disburse_date', [$from->toDateString(), $to->toDateString()]))->count();
    }

    private function cashIn(Carbon $from, Carbon $to): float
    {
        return round((float) CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at')->whereBetween('repayment_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))->sum('amount'), 2);
    }

    private function cashInCount(Carbon $from, Carbon $to): int
    {
        return CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at')->whereBetween('repayment_date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))->count();
    }

    /** What the listed loans owe today (from their latest cycles). */
    private function owedFor(array $ids): float
    {
        $r = app(LedgerReports::class);

        return round(collect($ids)->sum(fn ($id) => (float) ($r->balance((int) $id)['final_outstanding'] ?? 0)), 2);
    }

    /** Loans this user may see: a broker only their borrowers', a borrower only their own. */
    private function scopedLoans()
    {
        $q = Loan::query();
        if ($this->user->hasRole('broker') && ! $this->user->hasAnyRole(User::BACK_OFFICE_ROLES)) {
            $q->whereIn('user_id', $this->brokerBorrowers());
        } elseif (! $this->user->isStaff()) {
            $q->where('user_id', $this->user->id);
        }

        return $q;
    }

    private function brokerBorrowers(): array
    {
        $broker = $this->user->broker()->first();

        return $broker ? Borrower::where('broker_id', $broker->id)->pluck('user_id')->all() : [];
    }

    private function brokerEarned(): float
    {
        $partnerId = DB::table('partners')->where('user_id', $this->user->id)->value('id');

        return $partnerId ? round((float) DB::table('partner_transactions')->where('partner_id', $partnerId)->whereIn('type', ['broker_fee_earned', 'penalty_share'])->sum('amount'), 2) : 0.0;
    }
}
