{{-- The person side panel (owner, 22 Oct 2026): opened from any name in the staff pages. --}}
@php
  $p = \App\Support\UserProfile::of($user);
  $id = $p->identity();
  $kind = $p->kind();
  $kyc = \App\Support\UserProfile::KYC[$id['kyc']] ?? null;
  $label = 'text-theme-xs text-gray-500 dark:text-gray-400';
  $val = 'text-theme-sm font-medium text-gray-800 dark:text-white/90';
  $box = 'rounded-xl border border-gray-200 p-4 dark:border-gray-800';
@endphp
<div class="space-y-5">
  <div class="flex items-center gap-4">
    @if ($id['photo'])
      <img src="{{ $id['photo'] }}" alt="{{ $id['name'] }}" class="size-16 shrink-0 rounded-full object-cover">
    @else
      <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xl font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $id['initials'] }}</span>
    @endif
    <div class="min-w-0">
      <h3 class="truncate text-lg font-semibold text-gray-800 dark:text-white/90">{{ $id['name'] }}</h3>
      <div class="mt-1 flex flex-wrap gap-1.5">
        @foreach ($id['roles'] as $r)<span class="rounded-full bg-gray-100 px-2 py-0.5 text-theme-xs font-medium text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $r }}</span>@endforeach
        @if ($kyc)<span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $kyc[1] }}">{{ $kyc[0] }}</span>@endif
        @unless ($id['active'])<span class="rounded-full bg-error-50 px-2 py-0.5 text-theme-xs font-medium text-error-600">Inactive</span>@endunless
      </div>
    </div>
  </div>

  <div class="grid grid-cols-2 gap-3 {{ $box }}">
    <div><p class="{{ $label }}">Phone</p><p class="{{ $val }}">@if ($id['phone'])<a href="tel:{{ $id['phone'] }}" class="hover:text-brand-500">{{ $id['phone'] }}</a>@else — @endif</p></div>
    <div class="min-w-0"><p class="{{ $label }}">Email</p><p class="{{ $val }} truncate">{{ $id['email'] ?: '—' }}</p></div>
    <div><p class="{{ $label }}">ID</p><p class="{{ $val }}">{{ trim($id['id']) !== 'ID' ? $id['id'] : '—' }}</p></div>
    <div><p class="{{ $label }}">Joined</p><p class="{{ $val }}">{{ optional($id['joined'])->format('j M Y') }}</p></div>
  </div>

  @if ($kind === 'borrower')
    @php $open = $p->openLoans(); $cash = $p->cash(); $credit = $p->credit(); $counts = $p->loanCounts(); @endphp
    <div class="grid grid-cols-3 gap-3">
      <div class="{{ $box }}"><p class="{{ $label }}">Owes today</p><p class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"><span class="text-xs text-gray-400">KES</span> {{ number_format(collect($open)->sum('owed'), 2) }}</p></div>
      <div class="{{ $box }}"><p class="{{ $label }}">Loans</p><p class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90">{{ array_sum($counts) }}</p><p class="{{ $label }}">{{ count($open) }} open · {{ $counts['repaid'] ?? 0 }} repaid</p></div>
      <div class="{{ $box }}"><p class="{{ $label }}">4Cs</p><p class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90">{{ $credit['overall'] ?? '—' }}<span class="text-xs text-gray-400">/100</span></p><p class="{{ $label }}">{{ isset($credit['limit']['limit']) ? 'limit KES ' . number_format((float) $credit['limit']['limit']) : '' }}</p></div>
    </div>
    @if ($cash)
      <div class="{{ $box }}">
        <p class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Cash with us</p>
        <div class="grid grid-cols-3 gap-2 text-theme-sm">
          <div><p class="{{ $label }}">Sent to them</p><p class="{{ $val }}">{{ number_format($cash['received'], 2) }}</p></div>
          <div><p class="{{ $label }}">Repaid</p><p class="{{ $val }}">{{ number_format($cash['repaid'], 2) }}</p></div>
          <div><p class="{{ $label }}">Net</p><p class="font-medium {{ $cash['repaid'] - $cash['received'] >= 0 ? 'text-success-600' : 'text-error-600' }}">{{ number_format($cash['repaid'] - $cash['received'], 2) }}</p></div>
        </div>
      </div>
    @endif
    @php $bp = \App\Services\BorrowerAnalytics::profileFor($user->id); @endphp
    @if ($bp && $bp->loans)
      {{-- how they repay (borrower_profiles, owner 23 Oct 2026) --}}
      <div class="{{ $box }}">
        <div class="mb-2 flex items-center justify-between gap-2">
          <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">How they repay</p>
          @if ($bp->score !== null)<x-staff.pill :tone="$bp->score >= 80 ? 'green' : ($bp->score >= 60 ? 'amber' : 'red')">Score {{ $bp->score }}</x-staff.pill>@endif
        </div>
        <div class="grid grid-cols-3 gap-2 text-theme-sm">
          <div><p class="{{ $label }}">On time</p><p class="{{ $val }}">{{ $bp->on_time_rate === null ? '—' : rtrim(rtrim(number_format($bp->on_time_rate, 1), '0'), '.') . '%' }}</p></div>
          <div><p class="{{ $label }}">Comfortable up to</p><p class="{{ $val }}">{{ number_format($bp->comfort_amount, 2) }}</p></div>
          <div><p class="{{ $label }}">Suggested limit</p><p class="{{ $val }}">{{ number_format($bp->suggested_limit, 2) }}</p></div>
        </div>
        @if ($bp->flags)<div class="mt-2 flex flex-wrap gap-1">@foreach ($bp->flags as $f)<x-staff.pill :tone="$f['tone']">{{ $f['text'] }}</x-staff.pill>@endforeach</div>@endif
      </div>
    @endif
    @if ($open)
      <div>
        <p class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Open loans</p>
        <ul class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
          @foreach ($open as $o)
            <li><a href="{{ route('users.loans.show', [$o['loan']->user_id, $o['loan']->id]) }}" class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-white/[0.03]" title="View loan #{{ $o['loan']->id }}">
              <span><span class="text-theme-sm font-medium text-brand-500">#{{ $o['loan']->id }}</span> <span class="text-theme-xs text-gray-500">{{ $o['loan']->loanType->name ?? '' }} · {{ ucfirst($o['loan']->status) }} {{ $o['loan']->due_date ? '· due ' . \Illuminate\Support\Carbon::parse($o['loan']->due_date)->format('j M') : '' }}</span></span>
              <span class="flex items-center gap-3"><span class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ number_format($o['owed'], 2) }}</span><span class="whitespace-nowrap text-theme-xs font-medium text-brand-500">View →</span></span>
            </a></li>
          @endforeach
        </ul>
      </div>
    @endif
    @php $biz = $p->businesses(); @endphp
    @if ($biz)
      <div>
        <p class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Businesses</p>
        @foreach ($biz as $b)
          <a href="{{ \Illuminate\Support\Facades\Route::has('business-reviews.show') ? route('business-reviews.show', $b['business']->id) : '#' }}" class="mb-2 flex items-center gap-3 rounded-xl border border-gray-200 px-4 py-3 hover:border-brand-300 dark:border-gray-800">
            <span class="flex size-9 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-500/15"><x-ui.icon name="building" class="size-4" /></span>
            <span class="min-w-0 flex-1"><span class="block truncate text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $b['business']->trading_name ?: $b['business']->legal_name }}</span><span class="block text-theme-xs text-gray-500">{{ $b['role'] }}</span></span>
            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ \App\Support\UserProfile::BUSINESS[$b['business']->status] ?? \App\Support\UserProfile::BUSINESS['draft'] }}">{{ ucfirst($b['business']->status) }}</span>
          </a>
        @endforeach
      </div>
    @endif
  @elseif ($kind === 'partner')
    @php $wallets = $p->partnerWallets(); @endphp
    @if ($wallets)
      <div class="grid grid-cols-2 gap-3">
        @foreach ($wallets as $w)<div class="{{ $box }}"><p class="{{ $label }}">{{ $w['name'] }}</p><p class="mt-1 text-base font-semibold text-gray-800 dark:text-white/90"><span class="text-xs text-gray-400">KES</span> {{ number_format($w['balance'], 2) }}</p></div>@endforeach
      </div>
    @endif
    @if ($user->hasRole('broker'))<div class="{{ $box }}"><p class="{{ $label }}">Clients brought in</p><p class="{{ $val }}">{{ $p->brokerClients() }}</p></div>@endif
  @else
    <div>
      <p class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Recent work</p>
      <ul class="divide-y divide-gray-100 rounded-xl border border-gray-200 text-theme-sm dark:divide-gray-800 dark:border-gray-800">
        @forelse ($p->recentWork() as $w)
          <li class="flex justify-between gap-3 px-4 py-2.5"><span class="text-gray-700 dark:text-gray-300">{{ ucfirst($w->action) }} {{ str_replace('_', ' ', \Illuminate\Support\Str::singular($w->table_name)) }} #{{ $w->record_id }}</span><span class="text-theme-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($w->created_at)->diffForHumans(short: true) }}</span></li>
        @empty
          <li class="px-4 py-3 text-gray-500">Nothing recorded yet.</li>
        @endforelse
      </ul>
    </div>
  @endif
</div>
