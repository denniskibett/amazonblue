<?php

namespace App\Support;

use App\Models\User;

/**
 * The staff menu (owner, 22 Oct 2026), one definition for the sidebar and the header search (3 Nov 2026):
 * grouped by the job staff do, every link only with the permission its page needs. Each item is
 * [label, route, icon, visible, active patterns, badge]; hidden items and missing routes are dropped.
 */
class NavMenu
{
    public static function groups(User $u, bool $counts = true): array
    {
        $staff = $u->hasAnyRole([...\App\Models\User::BACK_OFFICE_ROLES, 'teller']);
        $count = function (callable $fn) use ($counts) { if (! $counts) { return 0; } try { return (int) $fn(); } catch (\Throwable $e) { return 0; } };

        // [label, route, icon, visible, active patterns, badge]
        $groups = [];
        // the owner's layout (3 Nov 2026): Needs Attention is fixed (approvals, recovery, reviews, sign-offs) and anything
        // else with a count moves up into it; then lending, administration, partners, collections and payments,
        // finance and reconciliation, analytics and reports, settings
        $groups['Overview'] = [
          ['Dashboard', 'dashboard', 'grid', true, ['dashboard'], null],
          ['My Inbox', 'staff.inbox', 'inbox', $u->isStaff(), ['staff.inbox'], null],
        ];
        if ($staff) {
          $groups['Needs Attention'] = [
            ['Loan Approvals', 'staff.approvals', 'check-circle', $u->can('view loans'), ['staff.approvals'], $count(fn () => \App\Models\Loan::where('status', 'pending')->count())],
            ['Debt Recovery', 'cases.index', 'shield', $u->can('view recovery cases'), ['cases.*'], $count(fn () => \App\Models\DebtRecoveryCase::open()->count())],
            ['KYC Reviews', 'kyc-reviews.index', 'user-check', $u->can('edit users'), ['kyc-reviews.*'], $count(fn () => \App\Models\User::where('kyc_status', 'submitted')->count())],
            ['Business Reviews', 'business-reviews.index', 'building', $u->can('view users'), ['business-reviews.*'], $count(fn () => \App\Models\Business::where('status', 'submitted')->count())],
            ['Second Sign-offs', 'staff.sign-offs', 'shield', $u->canAny(['approve loans', 'delete repayments', 'write off loan', 'edit partners', 'edit users', 'disburse loans']), ['staff.sign-offs'], $count(fn () => \Illuminate\Support\Facades\DB::table('approvals')->where('status', 'pending')->count())],
          ];
          $groups['Lending'] = [
            ['Loan Portfolio', 'loans.index', 'briefcase', $u->can('view loans'), ['loans.*', 'users.loans.*'], null],
            ['Loan Requests', 'staff.requests', 'mail', $u->canAny(['rollover loans', 'edit partners']), ['staff.requests'], $count(fn () => \Illuminate\Support\Facades\DB::table('borrower_requests')->where('status', 'open')->count())],
            ['Loan Products', 'staff.products', 'file', $u->canAny(['manage loan types', 'view reports']), ['staff.products'], null],
            ['Disbursements', 'staff.disbursements.queue', 'send', $u->can('view disbursements'), ['staff.disbursements.*', 'disbursements.create', 'disbursements.show', 'disbursements.edit'], $count(fn () => \App\Models\Loan::where('status', 'approved')->count())],
          ];
          $groups['Administration'] = [
            ['Borrowers', 'staff.borrowers', 'user', $u->can('view users'), ['staff.borrowers'], null],
            ['Sanctions Lists', 'aml.lists', 'shield', $u->can('view kyc documents'), ['aml.*'], null],
            ['Testimonials', 'staff.testimonials', 'star', $u->can('edit users'), ['staff.testimonials*'], $count(fn () => \Illuminate\Support\Facades\Schema::hasTable('testimonials') ? \App\Models\Testimonial::where('status', 'pending')->count() : 0)],
            ['User Management', 'users.index', 'users', $u->can('view users'), ['users.index', 'users.show', 'users.edit', 'users.create'], null],
            ['Roles & Permissions', 'staff.roles', 'key', $u->canAny(['manage roles', 'view audit log']), ['staff.roles'], null],
          ];
          $groups['Partners'] = [
            ['Partners', 'partners.index', 'briefcase', $u->can('view partners'), ['partners.*'], $count(fn () => \Illuminate\Support\Facades\DB::table('partner_requests')->where('type', 'funding')->where('status', 'pending')->count())],
            ['Investment Portfolio', 'investments.index', 'layers', $u->can('view investments'), ['investments.*'], null],
            ['Partner Payouts', 'staff.partner-payouts', 'coins', $u->can('view partner statements'), ['staff.partner-payouts'], $count(fn () => \Illuminate\Support\Facades\DB::table('partner_requests')->where('type', 'withdrawal')->whereIn('status', ['pending', 'prepared'])->count())],
          ];
          $groups['Collections & Payments'] = [
            ['Collections', 'staff.collections', 'alert', $u->can('view recovery cases'), ['staff.collections'], null],
            ['Receive Payment', 'staff.repayments.receive', 'hand-coins', $u->canAny(['create repayments', 'delete repayments']), ['staff.repayments.*'], null],
            ['M-Pesa Payments', 'staff.payments', 'phone', $u->can('create repayments'), ['staff.payments'], $count(fn () => \Illuminate\Support\Facades\Schema::hasTable('mpesa_transactions') ? \Illuminate\Support\Facades\DB::table('mpesa_transactions')->whereIn('status', ['unmatched', 'received'])->count() : 0)],
            ['M-Pesa Payouts', 'mpesa.payouts', 'send', \App\Services\Mpesa\B2cPayouts::enabled() && $u->can('view disbursements'), ['mpesa.payouts'], null],
            ['Collection Agencies', 'staff.agencies', 'users', $u->can('manage collection agencies'), ['staff.agencies*'], null],
            ['Messages to Borrowers', 'staff.messages.index', 'mail', $u->hasAnyRole(['super-admin', 'admin', 'manager']), ['staff.messages.*'], null],
            ['End of Day', 'staff.end-of-day', 'clock', $u->canAny(['create repayments', 'create disbursements', 'view reports']), ['staff.end-of-day'], null],
          ];
          $groups['Finance & Reconciliation'] = [
            ['Cash & Growth', 'staff.cash-forecast', 'chart', $u->can('view reports'), ['staff.cash-forecast'], null],
            ['Portfolio & Income', 'staff.portfolio', 'pie', $u->can('view reports'), ['staff.portfolio'], null],
            ['Wallets & Reconciliation', 'staff.wallets', 'scale', $u->canAny(['reconcile wallets', 'view house reconciliation']), ['staff.wallets'], null],
            ['Operations Review', 'staff.operations-review', 'refresh', $u->canAny(['view logs', 'view ledger']), ['staff.operations-review'], null],
          ];
          $groups['Analytics & Reports'] = [
            ['Reports', 'reports.index', 'chart', $u->can('view reports'), ['reports.*', 'disbursements.index'], null],
            ['Lending Analytics', 'staff.analytics', 'pie', $u->can('view reports'), ['staff.analytics'], null],
            ['Site Analytics', 'staff.site-analytics', 'trending-up', $u->can('view reports'), ['staff.site-analytics'], null],
            ['Audit Log', 'audit-log.index', 'list', $u->can('view audit log'), ['audit-log.*'], null],
          ];
          $groups['Settings'] = [
            ['Integrations', 'staff.integrations', 'layers', $u->canAny(['manage integrations', 'access system settings']), ['staff.integrations*'], null],
            ['API Keys', 'staff.api-keys', 'key', $u->can('manage API tokens'), ['staff.api-keys*'], null],
          ];
        }
        if ($u->hasRole('borrower') && ! $u->isStaff()) {
          $groups['My account'] = [
            ['My loans', 'loans.index', 'briefcase', true, ['loans.*'], null],
            ['Repayments', 'repayments.index', 'hand-coins', true, ['repayments.*'], null],
            ['My recovery status', 'cases.index', 'shield', true, ['cases.*'], $count(fn () => $u->debtRecoveryCases()->open()->count())],
          ];
        }
        $groups = array_filter(array_map(fn ($items) => array_values(array_filter($items, fn ($i) => $i[3] && \Illuminate\Support\Facades\Route::has($i[1]))), $groups));
        // Work waiting (owner, 22 Oct 2026): anything with a count moves up under Overview so staff see it first
        if ($staff) {
          $waiting = $groups['Needs Attention'] ?? [];
          foreach ($groups as $title => $items) {
            if (in_array($title, ['Overview', 'Needs Attention'], true)) continue;
            foreach ($items as $k => $i) {
              if ($i[5] > 0) { $waiting[] = $i; unset($groups[$title][$k]); }
            }
            $groups[$title] = array_values($groups[$title]);
          }
          $groups = array_filter($groups);
          unset($groups['Needs Attention']);
          if ($waiting) {
            $groups = array_merge(array_intersect_key($groups, ['Overview' => 1]), ['Needs Attention' => $waiting], array_diff_key($groups, ['Overview' => 1]));
          }
        }

        return $groups;
    }
}
