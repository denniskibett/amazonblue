<?php

namespace App\Support;

use App\Models\Business;
use App\Models\DebtRecoveryCase;
use App\Models\Loan;
use App\Models\User;
use App\Services\CreditAssessment;
use App\Services\CustomerWallets;
use App\Services\LedgerReports;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Everything the staff pages show about one person (owner, 22 Oct 2026), shaped by what the person is:
 * a borrower (loans, money, 4Cs, businesses), a partner or broker (wallets, clients) or staff (roles, recent work).
 * Used by the side panel (users/_peek) and the profile page (users/show), so both always agree.
 */
class UserProfile
{
    public function __construct(public User $user) {}

    public static function of(User $user): self
    {
        return new self($user);
    }

    public function kind(): string
    {
        $u = $this->user;
        if ($u->hasAnyRole([...User::BACK_OFFICE_ROLES, 'teller']) || $u->isStaff()) {
            return 'staff';
        }
        if ($u->hasRole('broker') || $u->hasRole('partner') || $this->partner()) {
            return $u->hasRole('borrower') && $u->loans()->exists() ? 'borrower' : 'partner';
        }

        return 'borrower';
    }

    public function identity(): array
    {
        $u = $this->user;

        return [
            'name' => $u->name,
            'initials' => $u->getInitials(),
            'photo' => $u->avatar ? $u->documentUrl('photo') : null,
            'roles' => $u->getRoleNames()->map(fn ($r) => ucwords(str_replace('-', ' ', $r)))->all(),
            'active' => (int) $u->status === 0,
            'phone' => $u->phone,
            'email' => $u->email,
            'id' => trim(($u->id_type === 'passport' ? 'Passport ' : 'ID ') . ($u->id_number ?? '')),
            'kra' => $u->kra_pin,
            'joined' => $u->created_at,
            'kyc' => $u->kyc_status,
            'handle' => $u->handle,
        ];
    }

    /** Open loans with what each owes today, newest first. */
    public function openLoans(): array
    {
        $r = app(LedgerReports::class);

        return Loan::with('loanType:id,name')->where('user_id', $this->user->id)->whereIn('status', PageMetrics::OPEN)->latest('borrow_date')->get()
            ->map(fn ($l) => ['loan' => $l, 'owed' => (float) ($r->balance($l->id)['final_outstanding'] ?? 0)])->all();
    }

    public function loanCounts(): array
    {
        return Loan::where('user_id', $this->user->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n)->all();
    }

    public function cash(): ?array
    {
        try {
            return $this->user->loans()->exists() ? app(CustomerWallets::class)->borrowerCash($this->user) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function credit(): ?array
    {
        try {
            return $this->user->loans()->exists() ? app(CreditAssessment::class)->current($this->user) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function payments(int $n = 6)
    {
        return DB::table('repayments as r')->join('loans as l', 'l.id', '=', 'r.loan_id')->where('l.user_id', $this->user->id)->whereNull('r.deleted_at')
            ->orderByDesc('r.repayment_date')->limit($n)->get(['r.id', 'r.loan_id', 'r.amount', 'r.repayment_date', 'r.mode', 'r.kind', 'r.transaction']);
    }

    /** Businesses this person owns, and those they belong to as a director, shareholder or signatory. */
    public function businesses(): array
    {
        if (! Schema::hasTable('businesses')) {
            return [];
        }
        $owned = Business::where('owner_user_id', $this->user->id)->get()->map(fn ($b) => ['business' => $b, 'role' => 'Owner']);
        $member = Schema::hasTable('business_members')
            ? DB::table('business_members')->where('user_id', $this->user->id)->get()->map(function ($m) {
                $b = Business::find($m->business_id);

                return $b ? ['business' => $b, 'role' => ucfirst(str_replace('_', ' ', $m->role ?: 'member')) . ($m->ownership_pct ? ' · ' . rtrim(rtrim(number_format((float) $m->ownership_pct, 2), '0'), '.') . '%' : '')] : null;
            })->filter()
            : collect();

        return $owned->concat($member)->unique(fn ($x) => $x['business']->id)->values()->all();
    }

    public function recoveryCases()
    {
        return DebtRecoveryCase::with('status')->where('user_id', $this->user->id)->latest()->limit(5)->get();
    }

    public function partner()
    {
        return Schema::hasTable('partners') ? DB::table('partners')->where('user_id', $this->user->id)->first() : null;
    }

    /** A partner's wallet balances by name. */
    public function partnerWallets(): array
    {
        $p = $this->partner();
        if (! $p) {
            return [];
        }

        return DB::table('wallets')->where('holder_type', (new \App\Models\Partner)->getMorphClass())->where('holder_id', $p->id)->where('slug', 'not like', 'gl-%')
            ->get(['name', 'slug', 'balance', 'decimal_places'])
            ->map(fn ($w) => ['name' => $w->name ?: ucfirst($w->slug), 'balance' => (float) $w->balance / (10 ** (int) ($w->decimal_places ?? 2))])->all();
    }

    public function brokerClients(): int
    {
        $broker = $this->user->broker()->first();

        return $broker ? \App\Models\Borrower::where('broker_id', $broker->id)->count() : 0;
    }

    /** What a staff member did lately (audit trail). */
    public function recentWork(int $n = 8)
    {
        return DB::table('audit_logs')->where('user_id', $this->user->id)->latest('created_at')->limit($n)->get(['table_name', 'record_id', 'action', 'created_at']);
    }

    public const KYC = [
        'approved' => ['Verified', 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500'],
        'submitted' => ['KYC waiting for review', 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400'],
        'rejected' => ['KYC rejected', 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500'],
    ];

    public const BUSINESS = [
        'approved' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500',
        'submitted' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
        'rejected' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
        'draft' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-300',
    ];
}
