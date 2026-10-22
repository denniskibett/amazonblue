<?php

namespace App\Support;

use App\Models\DebtRecoveryCase;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications for staff (owner, 22 Oct 2026). One feed per person, built from what their role works on:
 * new applications, loans to send, KYC and business reviews, requests, second sign-offs, recovery cases on them,
 * loans falling due or going overdue, M-Pesa payments without a loan, "I've paid" claims, partner withdrawals,
 * plus anything sent to them personally (database notifications).
 *
 * Read state is one "seen up to" time per person: everything newer is unread. Nothing is copied into a table,
 * so the feed is always what is true now (a loan already approved drops out by itself).
 */
class StaffNotifications
{
    public const DAYS = 14;

    public function __construct(private User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    /** @return array<int, array{key:string,type:string,icon:string,tone:string,title:string,line:string,at:Carbon,href:?string,unread:bool}> */
    public function items(int $limit = 200): array
    {
        $u = $this->user;
        $since = now()->subDays(self::DAYS);
        $out = [];
        $add = function (string $type, string $key, string $icon, string $tone, string $title, string $line, $at, ?string $href) use (&$out) {
            $out[] = compact('type', 'key', 'icon', 'tone', 'title', 'line', 'href') + ['at' => Carbon::parse($at ?? now())];
        };
        $safe = function (callable $fn) { try { $fn(); } catch (\Throwable $e) { report($e); } };
        $route = fn (string $name, $params = []) => Route::has($name) ? route($name, $params) : null;

        if ($u->can('view loans')) {
            $safe(fn () => Loan::with('user:id,name')->where('status', Loan::STATUS_PENDING)->latest()->limit(50)->get()
                ->each(fn ($l) => $add('Applications', 'loan-pending-' . $l->id, 'inbox', 'brand', 'New loan application',
                    ($l->user->name ?? 'A borrower') . ' asked for KES ' . number_format((float) $l->amount, 2) . ' (loan #' . $l->id . ')', $l->created_at, $route('staff.approvals'))));
            // due today or tomorrow, and loans that went overdue in the last 3 days
            $safe(fn () => Loan::with('user:id,name')->whereIn('status', PageMetrics::OPEN)
                ->whereBetween('due_date', [now()->startOfDay(), now()->addDay()->endOfDay()])->limit(50)->get()
                ->each(fn ($l) => $add('Due', 'loan-due-' . $l->id . '-' . Carbon::parse($l->due_date)->format('Ymd'), 'calendar', 'amber',
                    'Loan #' . $l->id . ' due ' . (Carbon::parse($l->due_date)->isToday() ? 'today' : 'tomorrow'),
                    ($l->user->name ?? 'Borrower') . ': call before the due date', now()->startOfDay(), $route('loans.show', $l->id))));
            $safe(fn () => Loan::with('user:id,name')->where('status', Loan::STATUS_OVERDUE)
                ->whereBetween('due_date', [now()->subDays(3)->startOfDay(), now()->subDay()->endOfDay()])->limit(50)->get()
                ->each(fn ($l) => $add('Overdue', 'loan-overdue-' . $l->id, 'alert', 'red', 'Loan #' . $l->id . ' is overdue',
                    ($l->user->name ?? 'Borrower') . ' passed the due date ' . Carbon::parse($l->due_date)->diffForHumans(), Carbon::parse($l->due_date)->addDay(), $route('loans.show', $l->id))));
        }
        if ($u->can('view disbursements')) {
            $safe(fn () => Loan::with('user:id,name')->where('status', Loan::STATUS_APPROVED)->latest('updated_at')->limit(50)->get()
                ->each(fn ($l) => $add('To send', 'loan-approved-' . $l->id, 'send', 'blue', 'Approved: ready to send',
                    'Loan #' . $l->id . ' for ' . ($l->user->name ?? 'a borrower') . ', KES ' . number_format((float) $l->amount, 2), $l->updated_at, $route('staff.disbursements.queue'))));
        }
        if ($u->can('view users')) {
            $safe(fn () => User::where('kyc_status', 'submitted')->latest('kyc_submitted_at')->limit(50)->get(['id', 'name', 'kyc_submitted_at'])
                ->each(fn ($p) => $add('KYC', 'kyc-' . $p->id . '-' . optional($p->kyc_submitted_at)->timestamp, 'user-check', 'green', 'KYC submitted',
                    $p->name . ' sent their details for verification', $p->kyc_submitted_at, $route('kyc-reviews.show', $p->id))));
            if (Schema::hasTable('businesses')) {
                $safe(fn () => DB::table('businesses')->where('status', 'submitted')->whereNull('deleted_at')->latest('submitted_at')->limit(50)->get()
                    ->each(fn ($b) => $add('KYC', 'business-' . $b->id . '-' . strtotime((string) $b->submitted_at), 'building', 'green', 'Business submitted',
                        ($b->trading_name ?: $b->legal_name) . ' is waiting for review', $b->submitted_at, $route('business-reviews.show', $b->id))));
            }
        }
        if ($u->canAny(['rollover loans', 'edit partners']) && Schema::hasTable('borrower_requests')) {
            $safe(fn () => DB::table('borrower_requests as r')->leftJoin('users as u', 'u.id', '=', 'r.user_id')->where('r.status', 'open')->latest('r.created_at')->limit(50)->get(['r.*', 'u.name'])
                ->each(fn ($r) => $add('Requests', 'request-' . $r->id, 'mail', 'orange', ucfirst(str_replace('_', ' ', $r->type)) . ' request',
                    ($r->name ?? 'A borrower') . ($r->loan_id ? ' on loan #' . $r->loan_id : ''), $r->created_at, $route('staff.requests'))));
        }
        if ($u->can('edit partners') && Schema::hasTable('partner_requests')) {
            $safe(fn () => DB::table('partner_requests as r')->leftJoin('partners as p', 'p.id', '=', 'r.partner_id')->whereIn('r.status', ['pending', 'prepared'])->latest('r.created_at')->limit(50)->get(['r.*', 'p.name'])
                ->each(fn ($r) => $add('Requests', 'partner-request-' . $r->id . '-' . $r->status, 'coins', 'orange', 'Partner ' . str_replace('_', ' ', $r->type),
                    ($r->name ?? 'A partner') . ' · KES ' . number_format((float) $r->amount, 2) . ' · ' . $r->status, $r->updated_at ?? $r->created_at, $route('staff.requests'))));
        }
        if (Schema::hasTable('approvals') && $u->canAny(['approve loans', 'delete repayments', 'write off loan', 'edit partners'])) {
            $safe(fn () => DB::table('approvals as a')->leftJoin('users as u', 'u.id', '=', 'a.requested_by')->where('a.status', 'pending')->where('a.requested_by', '!=', $u->id)->latest('a.created_at')->limit(50)->get(['a.*', 'u.name'])
                ->each(fn ($a) => $add('Sign-offs', 'approval-' . $a->id, 'shield', 'purple', 'Second signature needed',
                    ucfirst(str_replace('_', ' ', $a->action)) . ' asked by ' . ($a->name ?? 'a colleague'), $a->created_at, $route('staff.sign-offs'))));
            // my own requests that were decided
            $safe(fn () => DB::table('approvals')->where('requested_by', $u->id)->whereIn('status', ['approved', 'rejected'])->where('decided_at', '>=', $since)->latest('decided_at')->limit(30)->get()
                ->each(fn ($a) => $add('Sign-offs', 'approval-decided-' . $a->id, $a->status === 'approved' ? 'check-circle' : 'x-circle', $a->status === 'approved' ? 'green' : 'red',
                    'Your ' . str_replace('_', ' ', $a->action) . ' was ' . $a->status, $a->reason ?: 'Second sign-off decided', $a->decided_at, $route('staff.sign-offs'))));
        }
        if ($u->can('view recovery cases')) {
            $safe(fn () => DebtRecoveryCase::with('loan.user:id,name')->where('assigned_to', $u->id)->where('updated_at', '>=', $since)->latest('updated_at')->limit(30)->get()
                ->each(fn ($c) => $add('Recovery', 'case-' . $c->id . '-' . optional($c->updated_at)->timestamp, 'shield', 'red', 'Recovery case on you',
                    ($c->case_number ?? ('Case #' . $c->id)) . ' · ' . ($c->loan->user->name ?? 'borrower'), $c->updated_at, $route('cases.show', $c->id))));
        }
        if ($u->can('create repayments')) {
            if (Schema::hasTable('mpesa_transactions')) {
                $safe(fn () => DB::table('mpesa_transactions')->whereIn('status', ['unmatched', 'received'])->latest('id')->limit(30)->get()
                    ->each(fn ($t) => $add('Payments', 'mpesa-' . $t->id, 'phone', 'green', 'M-Pesa payment without a loan',
                        'KES ' . number_format((float) $t->amount, 2) . ' · ' . $t->trans_id . ' · ref ' . ($t->bill_ref ?: 'none'), $t->trans_time ?? $t->created_at, $route('staff.payments'))));
            }
            if (Schema::hasTable('payment_claims')) {
                $safe(fn () => DB::table('payment_claims as c')->leftJoin('users as u', 'u.id', '=', 'c.user_id')->where('c.status', 'checking')->latest('c.created_at')->limit(30)->get(['c.*', 'u.name'])
                    ->each(fn ($c) => $add('Payments', 'claim-' . $c->id, 'hand-coins', 'green', '"I\'ve paid" claim to check',
                        ($c->name ?? 'A borrower') . ' says they paid KES ' . number_format((float) $c->amount, 2) . ' (' . $c->code . ')', $c->created_at, $route('staff.payments'))));
            }
        }
        // finance: yesterday's cash, once a day (accountants, managers, anyone who reads the reports)
        if ($u->can('view reports')) {
            $safe(function () use ($add, $route) {
                $day = now()->subDay();
                $out = \App\Services\CustomerWallets::whereCash(DB::table('disbursements')->whereNull('deleted_at')->whereDate('disburse_date', $day->toDateString()))->sum(DB::raw(\App\Services\CustomerWallets::DISBURSED_CASH_SQL));
                $in = \App\Services\CustomerWallets::whereCash(DB::table('repayments')->whereNull('deleted_at')->whereDate('repayment_date', $day->toDateString()))->sum('amount');
                $add('Finance', 'cash-' . $day->format('Ymd'), 'banknote', 'blue', 'Cash for ' . $day->format('D j M'),
                    'Sent KES ' . number_format((float) $out, 2) . ' · received KES ' . number_format((float) $in, 2), now()->startOfDay()->addHours(7), $route('staff.end-of-day', ['date' => $day->toDateString()]));
            });
        }
        if ($u->canAny(['view partner statements', 'edit partners']) && Schema::hasTable('partner_requests') && ! $u->can('edit partners')) {
            $safe(fn () => DB::table('partner_requests as r')->leftJoin('partners as p', 'p.id', '=', 'r.partner_id')->whereIn('r.status', ['pending', 'prepared'])->latest('r.created_at')->limit(30)->get(['r.*', 'p.name'])
                ->each(fn ($r) => $add('Finance', 'partner-request-' . $r->id . '-' . $r->status, 'coins', 'orange', 'Partner ' . str_replace('_', ' ', $r->type),
                    ($r->name ?? 'A partner') . ' · KES ' . number_format((float) $r->amount, 2) . ' · ' . $r->status, $r->updated_at ?? $r->created_at, $route('staff.partner-payouts'))));
        }
        // anything sent to this person directly
        if (Schema::hasTable('notifications')) {
            $safe(fn () => $u->notifications()->where('created_at', '>=', $since)->latest()->limit(30)->get()
                ->each(fn ($n) => $add('Messages', 'db-' . $n->id, 'mail', 'brand', $n->data['title'] ?? 'Message', $n->data['line'] ?? '', $n->created_at, $n->data['url'] ?? null)));
        }

        $seen = $this->seenAt();
        usort($out, fn ($a, $b) => $b['at'] <=> $a['at']);

        return array_map(fn ($i) => $i + ['unread' => $i['at']->gt($seen)], array_slice($out, 0, $limit));
    }

    public function unreadCount(): int
    {
        return count(array_filter($this->items(), fn ($i) => $i['unread']));
    }

    public function seenAt(): Carbon
    {
        return Carbon::parse(Cache::get('notifications-seen:' . $this->user->id, now()->subDays(self::DAYS)));
    }

    public function markAllRead(): void
    {
        Cache::forever('notifications-seen:' . $this->user->id, now()->toIso8601String());
    }

    public const TONES = [
        'brand' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
        'blue' => 'bg-blue-light-50 text-blue-light-500 dark:bg-blue-light-500/15 dark:text-blue-light-400',
        'green' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400',
        'red' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
        'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400',
    ];
}
