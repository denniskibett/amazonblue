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
            ['Borrowers', 'staff.borrowers', 'user', $u->can('view users'), ['staff.borrowers'], null],
            ['Sanctions & Compliance', 'aml.lists', 'shield', $u->can('view kyc documents'), ['aml.*'], null],
            ['Testimonials', 'staff.testimonials', 'star', $u->can('edit users'), ['staff.testimonials*'], $count(fn () => \Illuminate\Support\Facades\Schema::hasTable('testimonials') ? \App\Models\Testimonial::where('status', 'pending')->count() : 0)],
          ];
          // the owner's six groups (4 Nov 2026): related pages are one module, its pages are tabs on top (modules())
          $mods = self::modules($u, $count);
          $groups['Collections & Finance'] = [$mods['collections'], $mods['payments'], $mods['finops'], $mods['performance']];
          $groups['Partners'] = [
            ['Partner Management', 'partners.index', 'briefcase', $u->can('view partners'), ['partners.*'], $count(fn () => \Illuminate\Support\Facades\DB::table('partner_requests')->where('type', 'funding')->where('status', 'pending')->count())],
            ['Investment Portfolio', 'investments.index', 'layers', $u->can('view investments'), ['investments.*'], null],
            // balances are in the partners table; this shows (under Needs Attention) only while payouts wait, and opens them on Requests
            ['Partner Payouts', 'staff.requests', 'coins', $u->can('view partner statements') && $u->canAny(['rollover loans', 'edit partners']) && ($payoutsWaiting = $count(fn () => \Illuminate\Support\Facades\DB::table('partner_requests')->where('type', 'withdrawal')->whereIn('status', ['pending', 'prepared'])->count())) > 0, [], $payoutsWaiting ?? 0],
            ['Capital Raising', 'staff.capital', 'trending-up', $u->can('manage investor pipeline'), ['staff.capital*'], null],
          ];
          $groups['Management'] = [
            $mods['reports'],
            ['Audit Log', 'audit-log.index', 'list', $u->can('view audit log'), ['audit-log.*'], null],
            ['User Management', 'users.index', 'users', $u->can('view users'), ['users.index', 'users.show', 'users.edit', 'users.create'], null],
            ['Roles & Permissions', 'staff.roles', 'key', $u->canAny(['manage roles', 'view audit log']), ['staff.roles'], null],
            $mods['integrations'],
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

    /**
     * Modules (owner, 4 Nov 2026): one sidebar link for related pages, which show as tabs on top of each other
     * (partials.module-tabs). Each tab is [label, route, visible, active patterns, badge]; the module's sidebar
     * item is [label, first visible tab's route, icon, any tab visible, every tab's patterns, sum of badges].
     */
    public static function modules(User $u, ?callable $count = null): array
    {
        $count ??= fn (callable $fn) => 0;
        $defs = [
            'collections' => ['Collections & Recovery', 'alert', [
                ['Collections', 'staff.collections', $u->can('view recovery cases'), ['staff.collections'], null],
                ['Debt recovery', 'cases.index', $u->can('view recovery cases'), ['cases.*'], null],
                ['Collection agencies', 'staff.agencies', $u->can('manage collection agencies'), ['staff.agencies*'], null],
                ['Borrower messaging', 'staff.messages.index', $u->hasAnyRole(['super-admin', 'admin', 'manager']), ['staff.messages.*'], null],
            ]],
            'payments' => ['Payments', 'hand-coins', [
                ['Receive payment', 'staff.repayments.receive', $u->canAny(['create repayments', 'delete repayments']), ['staff.repayments.*'], null],
                ['M-Pesa payments', 'staff.payments', $u->can('create repayments'), ['staff.payments'], $count(fn () => \Illuminate\Support\Facades\Schema::hasTable('mpesa_transactions') ? \Illuminate\Support\Facades\DB::table('mpesa_transactions')->whereIn('status', ['unmatched', 'received'])->count() : 0)],
                ['M-Pesa payouts', 'mpesa.payouts', \App\Services\Mpesa\B2cPayouts::enabled() && $u->can('view disbursements'), ['mpesa.payouts'], null],
                // the second rail (9 Nov 2026), shown once Sharet Pay is switched on
                ['Sharet Pay', 'sharet.index', \App\Services\Sharet\SharetCollections::available() && $u->canAny(['create repayments', 'disburse loans', 'edit partners']), ['sharet.index'], null],
            ]],
            'finops' => ['Financial Operations', 'scale', [
                ['Wallets & reconciliation', 'staff.wallets', $u->canAny(['reconcile wallets', 'view house reconciliation']), ['staff.wallets'], null],
                ['End of day', 'staff.end-of-day', $u->canAny(['create repayments', 'create disbursements', 'view reports']), ['staff.end-of-day'], null],
                ['Operations review', 'staff.operations-review', $u->canAny(['view logs', 'view ledger']), ['staff.operations-review'], null],
            ]],
            'performance' => ['Financial Performance', 'trending-up', [
                ['P&L and unit economics', 'staff.pnl', $u->can('view reports'), ['staff.pnl'], null],
                ['Cash & growth', 'staff.cash-forecast', $u->can('view reports'), ['staff.cash-forecast'], null],
                ['Portfolio & income', 'staff.portfolio', $u->can('view reports'), ['staff.portfolio'], null],
            ]],
            'reports' => ['Reports & Analytics', 'chart', [
                ['Reports', 'reports.index', $u->can('view reports'), ['reports.*', 'disbursements.index'], null],
                ['People and places', 'staff.people', $u->can('view reports'), ['staff.people*'], null],
                ['Lending analytics', 'staff.analytics', $u->can('view reports'), ['staff.analytics'], null],
                ['Site analytics', 'staff.site-analytics', $u->can('view reports'), ['staff.site-analytics'], null],
            ]],
            'integrations' => ['Integrations & API Keys', 'layers', [
                ['Integrations', 'staff.integrations', $u->canAny(['manage integrations', 'access system settings']), ['staff.integrations*'], null],
                ['API keys', 'staff.api-keys', $u->can('manage API tokens'), ['staff.api-keys*'], null],
            ]],
        ];
        $out = [];
        foreach ($defs as $key => [$label, $icon, $tabs]) {
            $tabs = array_values(array_filter($tabs, fn ($t) => $t[2] && \Illuminate\Support\Facades\Route::has($t[1])));
            $out[$key] = [$label, $tabs[0][1] ?? 'dashboard', $icon, (bool) $tabs, array_merge(...array_map(fn ($t) => $t[3], $tabs ?: [[null, null, null, [], null]])),
                array_sum(array_map(fn ($t) => (int) $t[4], $tabs)) ?: null, 'tabs' => $tabs];
        }

        return $out;
    }

    /** The module the current page belongs to, with its tabs, when it has more than one (partials.module-tabs). */
    public static function tabsFor(User $u): ?array
    {
        foreach (self::modules($u) as $m) {
            if (count($m['tabs']) > 1 && collect($m['tabs'])->contains(fn ($t) => request()->routeIs(...$t[3]))) {
                return $m;
            }
        }

        return null;
    }
}
