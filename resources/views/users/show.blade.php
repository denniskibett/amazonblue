{{-- A person's profile (owner, 22 Oct 2026). One clean page shaped by who they are:
     borrower (money, loans, 4Cs, businesses, KYC), partner or broker (wallets, clients) or staff (roles, recent work).
     Businesses are shown as separate business accounts, not mixed into the personal profile. Data: App\Support\UserProfile. --}}
@extends('layouts.app')

@php
  $p = \App\Support\UserProfile::of($user);
  $id = $p->identity();
  $kind = $p->kind();
  $kyc = \App\Support\UserProfile::KYC[$id['kyc']] ?? null;
  $open = $kind === 'borrower' ? $p->openLoans() : [];
  $counts = $p->loanCounts();
  $biz = $p->businesses();
  $cases = $p->recoveryCases();
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $label = 'text-theme-xs text-gray-500 dark:text-gray-400';
  $val = 'text-theme-sm font-medium text-gray-800 dark:text-white/90';
  $h = 'text-base font-semibold text-gray-800 dark:text-white/90';
  $btn = 'inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]';
  $tabs = $kind === 'borrower'
    ? ['overview' => 'Overview', 'loans' => 'Loans (' . array_sum($counts) . ')', 'businesses' => 'Businesses (' . count($biz) . ')', 'kyc' => 'KYC & documents', 'recovery' => 'Recovery (' . $cases->count() . ')', 'activity' => 'Activity']
    : ($kind === 'partner'
      ? ['overview' => 'Overview', 'loans' => 'Loans (' . array_sum($counts) . ')', 'kyc' => 'Documents', 'activity' => 'Activity']
      : ['overview' => 'Overview', 'activity' => 'Activity']);
  $person = $user->contacts()->get();
  $guarantor = $person->firstWhere('relationship_specific', \App\Http\Controllers\HomeController::GUARANTOR);
  $kin = $person->first(fn ($c) => $c->relationship_specific !== \App\Http\Controllers\HomeController::GUARANTOR);
  $address = $user->addresses()->orderByDesc('is_primary')->first();
  $b = $user->borrower;
  $docs = ['id-front' => [$user->id_type === 'passport' ? 'Passport bio page' : 'ID: front', $user->id_front_path], 'id-back' => ['ID: back', $user->id_type === 'passport' ? null : $user->id_back_path],
           'photo' => ['Passport photo', $user->avatar], 'signature' => ['Signature', $user->signature], 'selfie-center' => ['Selfie: straight', $user->selfie_center_path],
           'selfie-left' => ['Selfie: left', $user->selfie_left_path], 'selfie-right' => ['Selfie: right', $user->selfie_right_path]];
@endphp

@section('content')
  {{-- header --}}
  <div class="mb-6 {{ $card }}">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex items-center gap-5">
        @if ($id['photo'])
          <img src="{{ $id['photo'] }}" alt="{{ $id['name'] }}" class="size-20 shrink-0 rounded-full border border-gray-200 object-cover dark:border-gray-800">
        @else
          <span class="flex size-20 shrink-0 items-center justify-center rounded-full bg-brand-50 text-2xl font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $id['initials'] }}</span>
        @endif
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $id['name'] }}</h1>
            @foreach ($id['roles'] as $r)<span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-theme-xs font-medium text-gray-700 dark:bg-white/5 dark:text-gray-300">{{ $r }}</span>@endforeach
            @if ($kyc)<span class="rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $kyc[1] }}">{{ $kyc[0] }}</span>@endif
            @unless ($id['active'])<span class="rounded-full bg-error-50 px-2.5 py-0.5 text-theme-xs font-medium text-error-600">Inactive</span>@endunless
          </div>
          <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-theme-sm text-gray-500 dark:text-gray-400">
            @if ($id['handle'])<span>{{ '@' . $id['handle'] }}</span>@endif
            @if ($id['phone'])<a href="tel:{{ $id['phone'] }}" class="inline-flex items-center gap-1.5 hover:text-brand-500"><x-ui.icon name="phone" class="size-4" />{{ $id['phone'] }}</a>@endif
            @if ($id['email'])<span class="inline-flex items-center gap-1.5"><x-ui.icon name="mail" class="size-4" />{{ $id['email'] }}</span>@endif
            @if ($user->id_number)<span class="inline-flex items-center gap-1.5"><x-ui.icon name="user-check" class="size-4" />{{ $id['id'] }}</span>@endif
            <span class="inline-flex items-center gap-1.5"><x-ui.icon name="calendar" class="size-4" />Joined {{ optional($id['joined'])->format('j M Y') }}</span>
          </div>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        @if ($kind === 'borrower' && auth()->user()->can('create loans') && \Illuminate\Support\Facades\Route::has('loans.create'))
          {{-- opens the loan modal (owner, 23 Oct 2026); the old page stays as the fallback --}}
          <a href="{{ route('loans.create', $user->id) }}" onclick="if (window.openLoansCreateModal) { event.preventDefault(); window.dispatchEvent(new CustomEvent('open-loan-create')); }" data-no-swap class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600"><x-ui.icon name="plus" class="size-4" />New loan</a>
        @endif
        @if ($open && auth()->user()->canAny(['create repayments']) && \Illuminate\Support\Facades\Route::has('staff.repayments.receive'))
          <a href="{{ route('staff.repayments.receive', ['loan' => $open[0]['loan']->id]) }}" class="{{ $btn }}"><x-ui.icon name="hand-coins" class="size-4" />Receive payment</a>
        @endif
        {{-- make this person a partner: the partner form opens filled in from their account and business (owner, 23 Oct 2026) --}}
        @if (auth()->user()->can('create partners') && ! \App\Models\Partner::where('user_id', $user->id)->exists() && ! $user->isStaff())
          <a href="{{ route('partners.index', ['from_user' => $user->id]) }}" class="{{ $btn }}" data-no-swap><x-ui.icon name="users" class="size-4" />Make partner</a>
        @endif
        @if ($user->kyc_status === 'submitted' && auth()->user()->can('view users') && \Illuminate\Support\Facades\Route::has('kyc-reviews.show'))
          <a href="{{ route('kyc-reviews.show', $user) }}" class="{{ $btn }}"><x-ui.icon name="user-check" class="size-4" />Review KYC</a>
        @endif
        @can('edit users')
          <button type="button" onclick="document.getElementById('editProfileModal').classList.remove('hidden')" class="{{ $btn }}"><x-ui.icon name="edit" class="size-4" />Edit profile</button>
        @endcan
      </div>
    </div>
  </div>

  {{-- headline figures --}}
  @if ($kind === 'borrower')
    @php $cash = $p->cash(); $credit = $p->credit(); @endphp
    <x-ui.metrics :items="array_values(array_filter([
      ['label' => 'Owes today', 'value' => 'KES ' . number_format(collect($open)->sum('owed'), 2), 'icon' => 'wallet', 'hint' => count($open) . ' open loan' . (count($open) === 1 ? '' : 's')],
      ['label' => 'Loans', 'value' => number_format(array_sum($counts)), 'icon' => 'briefcase', 'hint' => ($counts['repaid'] ?? 0) . ' repaid in full'],
      $cash ? ['label' => 'Cash sent to them', 'value' => 'KES ' . number_format($cash['received'], 2), 'icon' => 'send', 'hint' => 'repaid KES ' . number_format($cash['repaid'], 2)] : null,
      $credit ? ['label' => '4Cs score', 'value' => ($credit['overall'] ?? '—') . '/100', 'icon' => 'star', 'hint' => ($credit['category']['name'] ?? '') . (isset($credit['limit']['limit']) ? ' · limit KES ' . number_format((float) $credit['limit']['limit']) : '')] : null,
    ]))" />
  @elseif ($kind === 'partner')
    <x-ui.metrics :items="collect($p->partnerWallets())->map(fn ($w) => ['label' => $w['name'], 'value' => 'KES ' . number_format($w['balance'], 2), 'icon' => 'wallet'])->push($user->hasRole('broker') ? ['label' => 'Clients brought in', 'value' => number_format($p->brokerClients()), 'icon' => 'users'] : null)->filter()->values()->all()" />
  @endif

  {{-- tabs --}}
  <div x-data="{ tab: (location.hash || '').slice(1) in @js($tabs) ? location.hash.slice(1) : 'overview' }" x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))" @open-loan-create.window="if (@js(isset($tabs['loans']))) tab = 'loans'">
    <div class="mb-6 flex gap-1 overflow-x-auto rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-white/[0.03]">
      @foreach ($tabs as $k => $t)
        <button type="button" @click="tab = @js($k)" :class="tab === @js($k) ? 'bg-brand-500 text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5'"
                class="whitespace-nowrap rounded-lg px-4 py-2 text-theme-sm font-medium">{{ $t }}</button>
      @endforeach
    </div>

    {{-- OVERVIEW --}}
    <div x-show="tab === 'overview'" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      @if ($kind === 'borrower')
        <div class="space-y-6 xl:col-span-2">
          <div class="{{ $card }}">
            <h3 class="mb-4 {{ $h }}">Open loans</h3>
            @forelse ($open as $o)
              @php $l = $o['loan']; @endphp
              <a href="{{ route('loans.show', $l->id) }}" class="mb-3 flex items-center gap-4 rounded-xl border border-gray-200 p-4 last:mb-0 hover:border-brand-300 dark:border-gray-800">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/15"><x-ui.icon name="briefcase" class="size-5" /></span>
                <span class="min-w-0 flex-1">
                  <span class="block text-theme-sm font-medium text-gray-800 dark:text-white/90">Loan #{{ $l->id }} · {{ $l->loanType->name ?? 'Loan' }}</span>
                  <span class="block text-theme-xs text-gray-500">Borrowed {{ optional($l->borrow_date)->format('j M Y') }}{{ $l->due_date ? ' · due ' . \Illuminate\Support\Carbon::parse($l->due_date)->format('j M Y') : '' }} · {{ ucfirst($l->status) }}</span>
                </span>
                <span class="text-right"><span class="block {{ $label }}">Owes today</span><span class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">KES {{ number_format($o['owed'], 2) }}</span></span>
              </a>
            @empty
              <p class="text-theme-sm text-gray-500">No open loans.</p>
            @endforelse
          </div>
          <div class="{{ $card }}">
            <h3 class="mb-4 {{ $h }}">Recent payments</h3>
            <ul class="divide-y divide-gray-100 dark:divide-gray-800">
              @forelse ($p->payments(8) as $pay)
                <li class="flex items-center justify-between gap-3 py-2.5 text-theme-sm">
                  <span><span class="font-medium text-gray-800 dark:text-white/90">{{ \Illuminate\Support\Carbon::parse($pay->repayment_date)->format('j M Y') }}</span> <span class="text-gray-500">· loan #{{ $pay->loan_id }} · {{ ucfirst($pay->mode ?? '') }} {{ $pay->kind && $pay->kind !== 'cash' ? '· ' . str_replace('_', ' ', $pay->kind) : '' }}</span></span>
                  <span class="font-medium text-gray-800 dark:text-white/90">KES {{ number_format((float) $pay->amount, 2) }}</span>
                </li>
              @empty
                <li class="py-3 text-theme-sm text-gray-500">No payments yet.</li>
              @endforelse
            </ul>
          </div>
        </div>
        <div class="space-y-6">
          @if (! empty($credit['scores']))
            <div class="{{ $card }}">
              <h3 class="mb-4 {{ $h }}">4Cs today</h3>
              @foreach ($credit['scores'] as $name => $score)
                <div class="mb-3">
                  <div class="mb-1 flex justify-between text-theme-sm"><span class="text-gray-600 dark:text-gray-400">{{ ucfirst($name) }}</span><span class="font-medium text-gray-800 dark:text-white/90">{{ is_numeric($score) ? round($score) : $score }}</span></div>
                  <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800"><div class="h-2 rounded-full {{ (float) $score >= 70 ? 'bg-success-500' : ((float) $score >= 40 ? 'bg-amber-400' : 'bg-error-500') }}" style="width: {{ min(100, max(0, (float) $score)) }}%"></div></div>
                </div>
              @endforeach
              @if (isset($credit['limit']['limit']))<p class="mt-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $credit['limit']['label'] ?? 'Limit' }}: <span class="font-medium text-gray-800 dark:text-white/90">KES {{ number_format((float) $credit['limit']['limit'], 2) }}</span></p>@endif
            </div>
          @endif
          <div class="{{ $card }}">
            <h3 class="mb-4 {{ $h }}">Personal details</h3>
            <dl class="grid grid-cols-2 gap-4">
              <div><dt class="{{ $label }}">Date of birth</dt><dd class="{{ $val }}">{{ $user->dob ? \Illuminate\Support\Carbon::parse($user->dob)->format('j M Y') . ' (' . \Illuminate\Support\Carbon::parse($user->dob)->age . ')' : '—' }}</dd></div>
              <div><dt class="{{ $label }}">Gender</dt><dd class="{{ $val }}">{{ $user->gender ? ucfirst($user->gender) : '—' }}</dd></div>
              <div><dt class="{{ $label }}">Marital status</dt><dd class="{{ $val }}">{{ $user->marital_status ? ucfirst($user->marital_status) : '—' }}</dd></div>
              <div><dt class="{{ $label }}">KRA PIN</dt><dd class="{{ $val }}">{{ $user->kra_pin ?: '—' }}</dd></div>
              <div class="col-span-2"><dt class="{{ $label }}">Address</dt><dd class="{{ $val }}">{{ $address ? trim($address->address_line_1 . ', ' . $address->city, ', ') : '—' }}</dd></div>
              <div class="col-span-2"><dt class="{{ $label }}">Work</dt><dd class="{{ $val }}">{{ $b ? trim(($b->job_title ? $b->job_title . ' at ' : '') . ($b->employer_name ?: $b->workplace)) ?: '—' : '—' }}{{ $b && $b->net_salary ? ' · net KES ' . number_format((float) $b->net_salary) : '' }}</dd></div>
            </dl>
          </div>
          <div class="{{ $card }}">
            <h3 class="mb-4 {{ $h }}">People to call</h3>
            @foreach (['Next of kin' => $kin, 'Guarantor' => $guarantor] as $t => $c)
              <div class="mb-3 rounded-xl bg-gray-50 p-3 last:mb-0 dark:bg-white/[0.03]">
                <p class="{{ $label }}">{{ $t }}{{ $c && $t === 'Next of kin' && $c->relationship_specific ? ' · ' . $c->relationship_specific : '' }}</p>
                @if ($c)<p class="{{ $val }}">{{ $c->name }}</p><a href="tel:{{ $c->phone }}" class="text-theme-sm text-brand-500">{{ $c->phone }}</a>
                @elseif ($t === 'Next of kin' && $user->kin_name)<p class="{{ $val }}">{{ $user->kin_name }}</p><a href="tel:{{ $user->kin_phone }}" class="text-theme-sm text-brand-500">{{ $user->kin_phone }}</a>
                @else<p class="text-theme-sm text-gray-500">Not provided</p>@endif
              </div>
            @endforeach
          </div>
        </div>
      @elseif ($kind === 'partner')
        <div class="{{ $card }} xl:col-span-3">
          @php $partner = $p->partner(); @endphp
          <h3 class="mb-4 {{ $h }}">Partner account</h3>
          @if ($partner)
            <dl class="grid grid-cols-2 gap-4 md:grid-cols-4">
              <div><dt class="{{ $label }}">Name</dt><dd class="{{ $val }}">{{ $partner->name }}</dd></div>
              <div><dt class="{{ $label }}">Type</dt><dd class="{{ $val }}">{{ ucfirst($partner->type ?? '—') }}</dd></div>
              <div><dt class="{{ $label }}">Broker rate</dt><dd class="{{ $val }}">{{ $partner->broker_rate !== null ? $partner->broker_rate . '%' : '—' }}</dd></div>
              <div><dt class="{{ $label }}">Profit share</dt><dd class="{{ $val }}">{{ $partner->profit_share_rate !== null ? $partner->profit_share_rate . '%' : '—' }}</dd></div>
              <div><dt class="{{ $label }}">Bank</dt><dd class="{{ $val }}">{{ trim(($partner->bank_name ?? '') . ' ' . ($partner->bank_account_number ?? '')) ?: '—' }}</dd></div>
              <div><dt class="{{ $label }}">Status</dt><dd class="{{ $val }}">{{ ucfirst($partner->status ?? '—') }}</dd></div>
            </dl>
            @if (\Illuminate\Support\Facades\Route::has('partners.show'))<a href="{{ route('partners.show', $partner->id) }}" class="mt-5 inline-flex {{ $btn }}">Open partner page</a>@endif
          @else
            <p class="text-theme-sm text-gray-500">No partner account linked.</p>
          @endif
        </div>
      @else
        <div class="{{ $card }}">
          <h3 class="mb-4 {{ $h }}">Access</h3>
          <div class="flex flex-wrap gap-1.5">@foreach ($id['roles'] as $r)<span class="rounded-full bg-brand-50 px-2.5 py-1 text-theme-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $r }}</span>@endforeach</div>
          <p class="mt-4 text-theme-sm text-gray-600 dark:text-gray-400">{{ $user->getAllPermissions()->count() }} permissions through these roles.</p>
          @if (\Illuminate\Support\Facades\Route::has('staff.roles'))<a href="{{ route('staff.roles') }}" class="mt-4 inline-flex {{ $btn }}">Roles & permissions</a>@endif
        </div>
        <div class="{{ $card }} xl:col-span-2">
          <h3 class="mb-4 {{ $h }}">Recent work</h3>
          <ul class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($p->recentWork(12) as $w)
              <li class="flex justify-between gap-3 py-2.5 text-theme-sm"><span class="text-gray-700 dark:text-gray-300">{{ ucfirst($w->action) }} {{ str_replace('_', ' ', \Illuminate\Support\Str::singular($w->table_name)) }} #{{ $w->record_id }}</span><span class="text-theme-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($w->created_at)->format('j M Y H:i') }}</span></li>
            @empty
              <li class="py-3 text-theme-sm text-gray-500">Nothing recorded yet.</li>
            @endforelse
          </ul>
        </div>
      @endif
    </div>

    {{-- LOANS --}}
    @if (isset($tabs['loans']))
      <div x-show="tab === 'loans'" x-cloak>
        @include('partials.table.table-loans', ['loans' => $user->loans()->with(['loanType', 'repayments', 'user'])->orderByDesc('borrow_date')->get(), 'userView' => true, 'showUserColumn' => false, 'context' => 'user-' . $user->id])
      </div>
    @endif

    {{-- BUSINESSES: each business is its own account, shown apart from the personal profile --}}
    @if (isset($tabs['businesses']))
      <div x-show="tab === 'businesses'" x-cloak class="space-y-6">
        @forelse ($biz as $row)
          @php $bz = $row['business']; $members = $bz->members()->get(); @endphp
          <div class="overflow-hidden rounded-2xl border border-success-500/30 bg-white dark:bg-white/[0.03]">
            <div class="flex flex-col gap-4 border-b border-gray-200 bg-success-50/60 px-6 py-5 dark:border-gray-800 dark:bg-success-500/[0.06] sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-4">
                <span class="flex size-12 items-center justify-center rounded-xl bg-success-500 text-white"><x-ui.icon name="building" class="size-6" /></span>
                <div>
                  <p class="text-theme-xs font-medium uppercase tracking-wide text-success-700 dark:text-success-500">Business account · {{ $row['role'] }}</p>
                  <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $bz->trading_name ?: $bz->legal_name }}</h3>
                  @if ($bz->trading_name && $bz->legal_name !== $bz->trading_name)<p class="text-theme-sm text-gray-500">{{ $bz->legal_name }}</p>@endif
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ \App\Support\UserProfile::BUSINESS[$bz->status] ?? \App\Support\UserProfile::BUSINESS['draft'] }}">{{ ucfirst($bz->status) }}</span>
                @if (\Illuminate\Support\Facades\Route::has('business-reviews.show'))<a href="{{ route('business-reviews.show', $bz->id) }}" class="{{ $btn }}">Open business</a>@endif
              </div>
            </div>
            <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">
              <dl class="grid grid-cols-2 gap-4 lg:col-span-2">
                <div><dt class="{{ $label }}">Registration</dt><dd class="{{ $val }}">{{ $bz->registration_number ?: '—' }} {{ $bz->registration_type ? '· ' . str_replace('_', ' ', $bz->registration_type) : '' }}</dd></div>
                <div><dt class="{{ $label }}">KRA PIN</dt><dd class="{{ $val }}">{{ $bz->kra_pin ?: '—' }}</dd></div>
                <div><dt class="{{ $label }}">Industry</dt><dd class="{{ $val }}">{{ $bz->industry ?: '—' }}</dd></div>
                <div><dt class="{{ $label }}">Location</dt><dd class="{{ $val }}">{{ trim(($bz->city ?? '') . ', ' . ($bz->county ?? ''), ', ') ?: '—' }}</dd></div>
                <div><dt class="{{ $label }}">Monthly revenue</dt><dd class="{{ $val }}">{{ $bz->monthly_revenue ? 'KES ' . number_format((float) $bz->monthly_revenue, 2) : '—' }}</dd></div>
                <div><dt class="{{ $label }}">Employees</dt><dd class="{{ $val }}">{{ $bz->employees ?: '—' }}</dd></div>
                <div><dt class="{{ $label }}">Contact</dt><dd class="{{ $val }}">{{ $bz->phone ?: '—' }} {{ $bz->email ? '· ' . $bz->email : '' }}</dd></div>
                <div><dt class="{{ $label }}">Bank</dt><dd class="{{ $val }}">{{ trim(($bz->bank_name ?? '') . ' ' . ($bz->bank_account ?? '')) ?: '—' }}</dd></div>
              </dl>
              <div>
                <p class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Directors and shareholders</p>
                <ul class="space-y-2">
                  @forelse ($members as $m)
                    <li class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-theme-sm dark:bg-white/[0.03]">
                      <span class="{{ $m->user_id ? 'cursor-pointer hover:text-brand-500' : '' }} text-gray-800 dark:text-white/90" @if ($m->user_id) data-user-peek="{{ $m->user_id }}" @endif>{{ $m->full_name }}</span>
                      <span class="text-theme-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $m->role)) }}{{ $m->ownership_pct ? ' · ' . rtrim(rtrim(number_format((float) $m->ownership_pct, 2), '0'), '.') . '%' : '' }}</span>
                    </li>
                  @empty
                    <li class="text-theme-sm text-gray-500">None added yet.</li>
                  @endforelse
                </ul>
              </div>
            </div>
          </div>
        @empty
          <div class="{{ $card }}"><x-staff.empty title="No business accounts" message="This person has not added a business, and is not a director or shareholder in one." /></div>
        @endforelse
      </div>
    @endif

    {{-- KYC & DOCUMENTS --}}
    @if (isset($tabs['kyc']))
      <div x-show="tab === 'kyc'" x-cloak class="space-y-6">
        <div class="{{ $card }}">
          <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="{{ $h }}">Identity documents</h3>
            <span class="text-theme-sm text-gray-500">{{ $user->kyc_submitted_at ? 'Submitted ' . $user->kyc_submitted_at->format('j M Y') : 'Not submitted' }}{{ $user->kyc_reviewed_at ? ' · reviewed ' . $user->kyc_reviewed_at->format('j M Y') : '' }}</span>
          </div>
          @if ($user->kyc_review_notes)<p class="mb-4 rounded-lg bg-gray-50 p-3 text-theme-sm text-gray-700 dark:bg-white/[0.03] dark:text-gray-300">{{ $user->kyc_review_notes }}</p>@endif
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 xl:grid-cols-7">
            @foreach ($docs as $key => [$name, $path])
              @continue($key === 'id-back' && $user->id_type === 'passport')
              <div>
                <p class="mb-1 {{ $label }}">{{ $name }}</p>
                @if ($path && ($url = $user->documentUrl($key)))
                  <a href="{{ $url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                    @if (str_ends_with(strtolower($path), '.pdf'))<span class="flex h-28 items-center justify-center text-sm font-medium text-brand-500">Open PDF</span>
                    @else<img src="{{ $url }}" alt="{{ $name }}" class="h-28 w-full object-contain">@endif
                  </a>
                @else
                  {{-- staff can paste, drop or choose the missing picture right here --}}
                  @php
                    $missing = ['id-front' => ['ID front missing', 'Front of the national ID (photo side), or the passport bio page.'],
                                'id-back' => ['ID back missing', 'Back of the national ID.'],
                                'photo' => ['Passport photo missing', 'A clear, recent head-and-shoulders photo of the customer, face visible.'],
                                'signature' => ['Signature missing', 'The customer\'s signature on plain paper.'],
                                'selfie-center' => ['Selfie: straight missing', 'Customer looking straight at the camera, face fully visible.'],
                                'selfie-left' => ['Selfie: turned left missing', 'Customer with the head turned to their left (side of the face).'],
                                'selfie-right' => ['Selfie: turned right missing', 'Customer with the head turned to their right (other side).']][$key];
                  @endphp
                  <x-staff.doc-drop :user="$user" :document="$key" :title="$missing[0]" :hint="$missing[1]" :initials="$key === 'photo' ? $user->getInitials() : null" class="!h-28 !p-2 [&_p]:text-[11px]" />
                @endif
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    {{-- RECOVERY --}}
    @if (isset($tabs['recovery']))
      <div x-show="tab === 'recovery'" x-cloak class="{{ $card }}">
        <h3 class="mb-4 {{ $h }}">Recovery cases</h3>
        <ul class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($cases as $c)
            <li><a href="{{ route('cases.show', $c->id) }}" class="flex items-center justify-between gap-3 py-3 hover:text-brand-500">
              <span class="text-theme-sm"><span class="font-medium text-gray-800 dark:text-white/90">{{ $c->case_number ?? 'Case #' . $c->id }}</span> <span class="text-gray-500">· loan #{{ $c->loan_id }} · opened {{ optional($c->created_at)->format('j M Y') }}</span></span>
              <span class="text-theme-sm"><span class="mr-3 text-gray-500">{{ $c->status->name ?? '' }}</span><span class="font-medium text-gray-800 dark:text-white/90">KES {{ number_format((float) $c->total_debt_amount, 2) }}</span></span>
            </a></li>
          @empty
            <li class="py-3 text-theme-sm text-gray-500">No recovery cases.</li>
          @endforelse
        </ul>
      </div>
    @endif

    {{-- ACTIVITY: changes made to this person's records --}}
    <div x-show="tab === 'activity'" x-cloak class="{{ $card }}">
      <h3 class="mb-4 {{ $h }}">Activity</h3>
      @php
        $log = \Illuminate\Support\Facades\DB::table('audit_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
          ->where(fn ($q) => $q->where(fn ($q) => $q->where('a.table_name', 'users')->where('a.record_id', $user->id))->orWhere('a.user_id', $user->id))
          ->latest('a.created_at')->limit(40)->get(['a.table_name', 'a.record_id', 'a.action', 'a.created_at', 'u.name as by']);
      @endphp
      <ul class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($log as $w)
          <li class="flex justify-between gap-3 py-2.5 text-theme-sm"><span class="text-gray-700 dark:text-gray-300">{{ ucfirst($w->action) }} {{ str_replace('_', ' ', \Illuminate\Support\Str::singular($w->table_name)) }} #{{ $w->record_id }} <span class="text-gray-500">by {{ $w->by ?? 'System' }}</span></span><span class="text-theme-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($w->created_at)->format('j M Y H:i') }}</span></li>
        @empty
          <li class="py-3 text-theme-sm text-gray-500">Nothing recorded yet.</li>
        @endforelse
      </ul>
    </div>
  </div>

  @can('edit users')
    @include('users._edit-modal')
  @endcan
@endsection
