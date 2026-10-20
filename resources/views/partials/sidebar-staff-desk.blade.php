{{-- Stage 2 staff desk links (docs/stage2/walkthroughs/staff.md). Each link is shown only with the permission its route needs. --}}
@php
  $u = Auth::user();
  $deskGroups = [
    'Desk' => [
      ['staff.inbox', 'My inbox', $u->isStaff(), 'M4 13h4l2 3h4l2-3h4M4 13l2-8h12l2 8v6H4z'],
      ['staff.approvals', 'Loan approvals', $u->can('view loans'), 'M9 12l2 2 4-4M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z'],
      ['staff.disbursements.queue', 'Disbursement queue', $u->can('view disbursements'), 'M3 7h18v10H3zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
      ['staff.repayments.receive', 'Receive payment', $u->canAny(['create repayments', 'delete repayments']), 'M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6M5 11.5v6C5 19 8 20 12 20s7-1 7-2.5v-6'],
      ['staff.collections', 'Collections', $u->can('view recovery cases'), 'M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01'],
      ['staff.payments', 'M-Pesa payments', $u->can('create repayments'), 'M7 3h10v18H7zM11 18h2'],
      ['staff.end-of-day', 'End of day', $u->canAny(['create repayments', 'create disbursements', 'view reports']), 'M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2zM8 7h8'],
    ],
    'Set-up & finance' => [
      ['staff.roles', 'Roles & permissions', $u->canAny(['manage roles', 'view audit log']), 'M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z'],
      ['staff.products', 'Loan products', $u->canAny(['manage loan types', 'view reports']), 'M7 3h7l5 5v13H7zM14 3v5h5'],
      ['staff.wallets', 'Wallets & reconciliation', $u->canAny(['reconcile wallets', 'view house reconciliation']), 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
      ['staff.partner-payouts', 'Partner payouts', $u->can('view partner statements'), 'M17 20h5v-2a4 4 0 0 0-5-3.9M9 20H2v-2a4 4 0 0 1 7-2.6M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z'],
    ],
  ];
@endphp
@foreach ($deskGroups as $groupTitle => $items)
  @php $items = array_filter($items, fn ($i) => $i[2]); @endphp
  @if ($items)
    <div>
      <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
        <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $groupTitle }}</span>
      </h3>
      <ul class="mb-6 flex flex-col gap-4">
        @foreach ($items as [$name, $label, $allowed, $icon])
          @php $active = request()->routeIs($name) || ($name === 'staff.disbursements.queue' && request()->routeIs('staff.disbursements.*')); @endphp
          <li>
            <a href="{{ route($name) }}" class="menu-item group {{ $active ? 'menu-item-active' : 'menu-item-inactive' }}">
              <svg class="{{ $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="{{ $icon }}" style="fill:none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">{{ $label }}</span>
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  @endif
@endforeach
