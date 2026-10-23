{{-- Sidebar (owner, 22 Oct 2026): grouped by the job staff do, TailAdmin style, every link shown only with the
     permission its page needs. Groups fold away and remember it per browser. --}}
@php
  $u = Auth::user();
  $staff = $u->hasAnyRole([...\App\Models\User::BACK_OFFICE_ROLES, 'teller']);
  $count = function (callable $fn) { try { return (int) $fn(); } catch (\Throwable $e) { return 0; } };
  $is = fn (...$patterns) => request()->routeIs(...$patterns);

  // [label, route, icon, visible, active patterns, badge]
  $groups = [];
  $groups['Overview'] = [
    ['Dashboard', 'dashboard', 'grid', true, ['dashboard'], null],
    ['My inbox', 'staff.inbox', 'inbox', $u->isStaff(), ['staff.inbox'], null],
    ['Reports & Analytics', 'reports.index', 'chart', $u->can('view reports'), ['reports.*', 'disbursements.index'], null],
    ['Lending analytics', 'staff.analytics', 'pie', $u->can('view reports'), ['staff.analytics'], null],
  ];
  if ($staff) {
    $groups['Lending'] = [
      ['Loan Portfolio', 'loans.index', 'briefcase', $u->can('view loans'), ['loans.*', 'users.loans.*'], null],
      ['Loan approvals', 'staff.approvals', 'check-circle', $u->can('view loans'), ['staff.approvals'], $count(fn () => \App\Models\Loan::where('status', 'pending')->count())],
      ['Disbursement queue', 'staff.disbursements.queue', 'send', $u->can('view disbursements'), ['staff.disbursements.*', 'disbursements.create', 'disbursements.show', 'disbursements.edit'], $count(fn () => \App\Models\Loan::where('status', 'approved')->count())],
      ['Requests', 'staff.requests', 'mail', $u->canAny(['rollover loans', 'edit partners']), ['staff.requests'], $count(fn () => \Illuminate\Support\Facades\DB::table('borrower_requests')->where('status', 'open')->count())],
      ['Collections', 'staff.collections', 'alert', $u->can('view recovery cases'), ['staff.collections'], null],
      ['Debt Recovery', 'cases.index', 'shield', $u->can('view recovery cases'), ['cases.*'], $count(fn () => \App\Models\DebtRecoveryCase::open()->count())],

      ['Messages', 'staff.messages.index', 'mail', $u->hasAnyRole(['super-admin', 'admin', 'manager']), ['staff.messages.*'], null],
    ];
    $groups['Cash desk'] = [
      ['Receive payment', 'staff.repayments.receive', 'hand-coins', $u->canAny(['create repayments', 'delete repayments']), ['staff.repayments.*'], null],
      ['M-Pesa payments', 'staff.payments', 'phone', $u->can('create repayments'), ['staff.payments'], $count(fn () => \Illuminate\Support\Facades\Schema::hasTable('mpesa_transactions') ? \Illuminate\Support\Facades\DB::table('mpesa_transactions')->whereIn('status', ['unmatched', 'received'])->count() : 0)],
      ['End of day', 'staff.end-of-day', 'clock', $u->canAny(['create repayments', 'create disbursements', 'view reports']), ['staff.end-of-day'], null],
    ];
    $groups['Customers'] = [
      ['User Management', 'users.index', 'users', $u->can('view users'), ['users.index', 'users.show', 'users.edit', 'users.create'], null],
      ['Borrowers', 'staff.borrowers', 'user', $u->can('view users'), ['staff.borrowers'], null],
      ['KYC Reviews', 'kyc-reviews.index', 'user-check', $u->can('edit users'), ['kyc-reviews.*'], $count(fn () => \App\Models\User::where('kyc_status', 'submitted')->count())],
      ['Business Reviews', 'business-reviews.index', 'building', $u->can('view users'), ['business-reviews.*'], $count(fn () => \App\Models\Business::where('status', 'submitted')->count())],
    ];
    $groups['Finance'] = [
      ['Portfolio', 'staff.portfolio', 'pie', $u->can('view reports'), ['staff.portfolio'], null],
      ['Wallets & reconciliation', 'staff.wallets', 'scale', $u->canAny(['reconcile wallets', 'view house reconciliation']), ['staff.wallets'], null],
      ['Partner payouts', 'staff.partner-payouts', 'coins', $u->can('view partner statements'), ['staff.partner-payouts'], null],
      ['Partners', 'partners.index', 'briefcase', $u->can('view partners'), ['partners.*'], null],
      ['Investment Portfolio', 'investments.index', 'layers', $u->can('view investments'), ['investments.*'], null],
    ];
    $groups['Control'] = [
      ['Second sign-offs', 'staff.sign-offs', 'shield', $u->canAny(['approve loans', 'delete repayments', 'write off loan', 'edit partners']), ['staff.sign-offs'], $count(fn () => \Illuminate\Support\Facades\DB::table('approvals')->where('status', 'pending')->count())],
      ['Operations review', 'staff.operations-review', 'refresh', $u->canAny(['view logs', 'view ledger']), ['staff.operations-review'], null],
      ['Audit Log', 'audit-log.index', 'list', $u->can('view audit log'), ['audit-log.*'], null],
      ['Roles & permissions', 'staff.roles', 'key', $u->canAny(['manage roles', 'view audit log']), ['staff.roles'], null],
      ['Loan products', 'staff.products', 'file', $u->canAny(['manage loan types', 'view reports']), ['staff.products'], null],
    ];
  }
  if ($u->hasRole('broker') && ! $staff) {
    $groups['Your book'] = [
      ['Your loans', 'loans.index', 'briefcase', true, ['loans.*'], null],
      ['Repayments', 'repayments.index', 'hand-coins', true, ['repayments.*'], null],
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
    $waiting = [];
    foreach ($groups as $title => $items) {
      if ($title === 'Overview') continue;
      foreach ($items as $k => $i) {
        if ($i[5] > 0) { $waiting[] = $i; unset($groups[$title][$k]); }
      }
      $groups[$title] = array_values($groups[$title]);
    }
    $groups = array_filter($groups);
    if ($waiting) {
      $groups = array_merge(array_intersect_key($groups, ['Overview' => 1]), ['Needs attention' => $waiting], array_diff_key($groups, ['Overview' => 1]));
    }
  }
@endphp
<aside
  :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
  class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-gray-900 lg:static lg:translate-x-0"
>
  <div :class="sidebarToggle ? 'justify-center' : 'justify-between'" class="sidebar-header flex items-center gap-2 pb-7 pt-8">
    <a href="{{ route('dashboard') }}" class="flex items-center">
      <span class="logo" :class="sidebarToggle ? 'hidden' : 'flex items-center'">
        <img class="h-7 w-auto dark:hidden" src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures">
        <img class="hidden h-7 w-auto dark:block" src="{{ asset('assets/images/logo/aimzone-horizontal-white.svg') }}" alt="Aimzone Ventures">
      </span>
      <span class="logo-icon" :class="sidebarToggle ? 'lg:block' : 'hidden'">
        <img class="h-9 w-9 dark:hidden" src="{{ asset('assets/images/logo/aimzone-icon-blue.svg') }}" alt="Aimzone Ventures">
        <img class="hidden h-9 w-9 dark:block" src="{{ asset('assets/images/logo/aimzone-icon-white.svg') }}" alt="Aimzone Ventures">
      </span>
    </a>
  </div>

  <div class="no-scrollbar flex flex-col overflow-y-auto pb-10 duration-300 ease-linear">
    <nav x-data="{ closed: $persist([]).as('sidebar-closed') }">
      @foreach ($groups as $title => $items)
        @php $hasActive = collect($items)->contains(fn ($i) => $is(...$i[4])); @endphp
        <div class="mb-5">
          <button type="button" class="mb-3 flex w-full items-center justify-between text-xs uppercase leading-[20px] tracking-wide text-gray-400"
                  @click="closed.includes(@js($title)) ? closed = closed.filter(t => t !== @js($title)) : closed.push(@js($title))"
                  :aria-expanded="! closed.includes(@js($title))">
            <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $title }}</span>
            <span :class="sidebarToggle ? 'lg:hidden' : ''"><x-ui.icon name="chevron-down" class="size-4 transition-transform" ::class="closed.includes({{ json_encode($title) }}) ? '-rotate-90' : ''" /></span>
            <span :class="sidebarToggle ? 'mx-auto lg:block' : 'hidden'" class="hidden h-px w-6 bg-gray-200 dark:bg-gray-800"></span>
          </button>
          <ul class="flex flex-col gap-1" x-show="{{ $hasActive ? 'true' : 'false' }} || ! closed.includes(@js($title)) || sidebarToggle">
            @foreach ($items as [$label, $route, $icon, $visible, $patterns, $badge])
              @php $active = $is(...$patterns); @endphp
              <li>
                <a href="{{ route($route) }}" class="menu-item group {{ $active ? 'menu-item-active' : 'menu-item-inactive' }}" title="{{ $label }}">
                  <x-ui.icon :name="$icon" class="size-6 shrink-0 {{ $active ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300' }}" />
                  <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $label }}</span>
                  @if ($badge)
                    <span class="ml-auto rounded-full bg-orange-50 px-2 py-0.5 text-theme-xs font-medium text-orange-600 dark:bg-orange-500/15 dark:text-orange-400" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $badge }}</span>
                  @endif
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </nav>
  </div>
</aside>
