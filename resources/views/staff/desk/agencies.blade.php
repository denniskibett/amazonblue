{{-- Outside debt collection agencies (owner, 3 Nov 2026): add an agency and its collectors, place late loans with it
     (forbearance included), mark when the borrower was told, take loans back. Collectors work them on /collect. --}}
@extends('layouts.app')

@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
  $btn = 'inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5';
  $primary = 'inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600';
  $card = 'overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
  $th = 'px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400';
  $td = 'px-4 py-3 text-sm text-gray-700 dark:text-gray-300';
  $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
  $kes = fn ($v) => number_format((float) $v, 2);
  $tone = fn ($s) => match ($s) { 'forbearance' => 'bg-warning-50 text-warning-600', 'overdue' => 'bg-orange-50 text-orange-600', default => 'bg-error-50 text-error-600' };
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Collection agencies', 'sub' => 'Outside agencies work the late loans you place with them, forbearance included. They see only those loans, until the end date, and may contact a borrower ' . $noticeDays . ' days after the borrower was told which agency will call.', 'metrics' => false])

  <div x-data="{ adding: false, collector: false }" class="space-y-6">
    {{-- agencies --}}
    <div class="{{ $card }}">
      <div class="flex flex-col justify-between gap-4 border-b border-gray-100 p-5 dark:border-gray-800 sm:flex-row sm:items-center sm:px-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Agencies</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Only agencies with a signed data sharing agreement. Every change is in the audit log.</p>
        </div>
        <button type="button" @click="adding = true" class="{{ $primary }}">+ Add agency</button>
      </div>
      <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[760px]">
          <thead class="border-b border-gray-100 dark:border-gray-800"><tr>
            <th class="{{ $th }} pl-6">Agency</th><th class="{{ $th }}">Agreement</th><th class="{{ $th }}">Commission</th><th class="{{ $th }}">Covers</th><th class="{{ $th }}">Collectors</th><th class="{{ $th }}">Open loans</th><th class="{{ $th }}">Status</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($agencies as $a)
              <tr class="{{ $agency && $agency->id === $a->id ? 'bg-brand-50/60 dark:bg-brand-500/10' : '' }}">
                <td class="{{ $td }} pl-6"><a href="{{ route('staff.agencies', ['agency' => $a->id]) }}" class="font-medium text-brand-500 hover:underline">{{ $a->name }}</a>
                  <span class="block text-xs text-gray-500">{{ $a->contact_name }} {{ $a->phone }}</span></td>
                <td class="{{ $td }}">{{ $a->agreement_ref }}</td>
                <td class="{{ $td }}">{{ rtrim(rtrim((string) $a->commission_rate, '0'), '.') }}%</td>
                <td class="{{ $td }}">{{ $a->coverage ?: '—' }}@if ($a->cross_border) <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs dark:bg-white/5">outside Kenya</span>@endif</td>
                <td class="{{ $td }}">{{ $a->collectors_count }}</td>
                <td class="{{ $td }}">{{ $a->open_count }}</td>
                <td class="{{ $td }}">
                  <form method="POST" action="{{ route('staff.agencies.toggle', $a) }}">@csrf
                    <button class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $a->status === 'active' ? 'bg-success-50 text-success-600' : 'bg-gray-100 text-gray-700' }}" data-confirm="{{ $a->status === 'active' ? 'Pause ' . $a->name . '? Its collectors see nothing until you switch it back on.' : 'Switch ' . $a->name . ' back on?' }}">{{ ucfirst($a->status) }}</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No agencies yet. Add one to start placing loans.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($agency)
      {{-- the selected agency: collectors and what it holds --}}
      <div class="{{ $card }}">
        <div class="flex flex-col justify-between gap-4 border-b border-gray-100 p-5 dark:border-gray-800 sm:flex-row sm:items-center sm:px-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $agency->name }} · collectors</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">They sign in at {{ route('login') }} and land on their own portal, not the staff app.</p>
          </div>
          <button type="button" @click="collector = true" class="{{ $btn }}">+ Add collector</button>
        </div>
        <table class="w-full">
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($collectors as $c)
              <tr><td class="{{ $td }} pl-6 font-medium">{{ $c->name }}</td><td class="{{ $td }}">{{ $c->email }}</td><td class="{{ $td }}">{{ $c->phone }}</td>
                <td class="{{ $td }} pr-6 text-right"><form method="POST" action="{{ route('staff.agencies.collectors.destroy', [$agency, $c->id]) }}">@csrf @method('DELETE')
                  <button class="text-sm text-error-500 hover:underline" data-confirm="Remove {{ $c->name }}'s access?" data-confirm-danger>Remove access</button></form></td></tr>
            @empty
              <tr><td class="px-6 py-6 text-sm text-gray-500">No collectors yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="{{ $card }}">
        <div class="border-b border-gray-100 p-5 dark:border-gray-800 sm:px-6">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Placed with {{ $agency->name }} · {{ $placed->count() }}</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Set the day the borrower was told; the agency may contact them {{ $noticeDays }} days later.</p>
        </div>
        <div class="w-full overflow-x-auto">
          <table class="w-full min-w-[820px]">
            <thead class="border-b border-gray-100 dark:border-gray-800"><tr>
              <th class="{{ $th }} pl-6">Loan</th><th class="{{ $th }}">Borrower</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Placed</th><th class="{{ $th }}">Ends</th><th class="{{ $th }}">Borrower told</th><th class="{{ $th }}">Contacts</th><th class="{{ $th }}"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @forelse ($placed as $p)
                <tr>
                  <td class="{{ $td }} pl-6"><a class="text-brand-500 hover:underline" href="{{ route('loans.show', $p->loan_id) }}">#{{ $p->loan_id }}</a></td>
                  <td class="{{ $td }}">{{ $p->loan?->user?->name }}</td>
                  <td class="{{ $td }}"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $tone($p->loan?->status) }}">{{ ucfirst((string) $p->loan?->status) }}</span></td>
                  <td class="{{ $td }}">{{ $p->placed_at->format('j M Y') }}</td>
                  <td class="{{ $td }}">{{ $p->ends_on->format('j M Y') }}</td>
                  <td class="{{ $td }}">
                    <form method="POST" action="{{ route('staff.placements.notice', $p) }}" class="flex items-center gap-2">@csrf
                      <input type="date" name="notice_sent_on" value="{{ $p->notice_sent_on?->toDateString() }}" max="{{ now()->toDateString() }}" class="{{ $input }} !h-9 !w-40 !px-2" aria-label="Day the borrower was told">
                      <button class="{{ $btn }} !h-9 !px-3 text-xs">Save</button>
                    </form>
                    <span class="mt-1 block text-xs {{ $p->contactAllowed() ? 'text-success-600' : 'text-gray-500' }}">{{ $p->contactAllowed() ? 'Agency may contact' : ($p->contactFrom() ? 'Contact from ' . $p->contactFrom()->format('j M') : 'Not told yet: no contact') }}</span>
                  </td>
                  <td class="{{ $td }}">{{ $p->contacts_count }}</td>
                  <td class="{{ $td }} pr-6 text-right"><form method="POST" action="{{ route('staff.placements.take-back', $p) }}">@csrf
                    <button class="{{ $btn }} !h-9 !px-3 text-xs" data-confirm="Take loan #{{ $p->loan_id }} back from {{ $agency->name }}? They lose access at once.">Take back</button></form></td>
                </tr>
              @empty
                <tr><td colspan="8" class="px-6 py-6 text-sm text-gray-500">Nothing placed yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- place loans --}}
      <form method="POST" action="{{ route('staff.agencies.place', $agency) }}" class="{{ $card }}">@csrf
        <div class="flex flex-col gap-4 border-b border-gray-100 p-5 dark:border-gray-800 sm:px-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Place loans with {{ $agency->name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Overdue, defaulted, recovery and forbearance loans not already with an agency · {{ $placeable->count() }}</p>
          </div>
          <div class="flex flex-wrap items-end gap-3">
            <label class="text-sm"><span class="{{ $label }}">Placement ends</span><input type="date" name="ends_on" required value="{{ now()->addDays(90)->toDateString() }}" min="{{ now()->addDay()->toDateString() }}" class="{{ $input }} !w-44"></label>
            <label class="text-sm"><span class="{{ $label }}">Borrower already told on</span><input type="date" name="notice_sent_on" max="{{ now()->toDateString() }}" class="{{ $input }} !w-44"></label>
            <button class="{{ $primary }}" @disabled($agency->status !== 'active')>Place selected</button>
          </div>
        </div>
        <div class="w-full overflow-x-auto" x-data>
          <table class="w-full min-w-[760px]">
            <thead class="border-b border-gray-100 dark:border-gray-800"><tr>
              <th class="{{ $th }} pl-6"><input type="checkbox" aria-label="Select all" @change="$root.querySelectorAll('input[name=\'loan_ids[]\']').forEach(c => c.checked = $event.target.checked)"></th>
              <th class="{{ $th }}">Loan</th><th class="{{ $th }}">Borrower</th><th class="{{ $th }}">Product</th><th class="{{ $th }}">Status</th><th class="{{ $th }} text-right">Days late</th><th class="{{ $th }} pr-6 text-right">Owed today</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @forelse ($placeable as $r)
                <tr>
                  <td class="{{ $td }} pl-6"><input type="checkbox" name="loan_ids[]" value="{{ $r->loan->id }}" aria-label="Place loan {{ $r->loan->id }}"></td>
                  <td class="{{ $td }}"><a class="text-brand-500 hover:underline" href="{{ route('loans.show', $r->loan->id) }}">#{{ $r->loan->id }}</a></td>
                  <td class="{{ $td }}">{{ $r->loan->user?->name }}</td>
                  <td class="{{ $td }}">{{ $r->loan->loanType?->name }}</td>
                  <td class="{{ $td }}"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $tone($r->loan->status) }}">{{ ucfirst($r->loan->status) }}</span>
                    @if ($r->loan->status === 'forbearance' && $r->loan->forbearance_until)<span class="block text-xs text-gray-500">until {{ $r->loan->forbearance_until->format('j M') }}</span>@endif</td>
                  <td class="{{ $td }} text-right">{{ $r->days }}</td>
                  <td class="{{ $td }} pr-6 text-right font-medium">{{ $kes($r->owed) }}</td>
                </tr>
              @empty
                <tr><td colspan="7" class="px-6 py-6 text-sm text-gray-500">No late or forbearance loans waiting.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </form>
    @endif

    {{-- add agency --}}
    <div x-cloak x-show="adding" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="adding = false">
      <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="adding = false"></div>
      <div class="relative w-full max-w-[640px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
        <h4 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">Add a collection agency</h4>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Add it only once the data sharing agreement is signed. An agency outside Kenya also needs the cross-border clause (Data Protection Act, section 48).</p>
        <form method="POST" action="{{ route('staff.agencies.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">@csrf
          <label class="sm:col-span-2"><span class="{{ $label }}">Agency name</span><input name="name" required maxlength="120" class="{{ $input }}"></label>
          <label><span class="{{ $label }}">Contact person</span><input name="contact_name" maxlength="120" class="{{ $input }}"></label>
          <label><span class="{{ $label }}">Phone</span><input name="phone" maxlength="30" class="{{ $input }}"></label>
          <label><span class="{{ $label }}">Email</span><input type="email" name="email" maxlength="160" class="{{ $input }}"></label>
          <label><span class="{{ $label }}">Agreement reference</span><input name="agreement_ref" required maxlength="60" placeholder="e.g. DSA-2026-04" class="{{ $input }}"></label>
          <label><span class="{{ $label }}">Commission (% of what they collect)</span><input type="number" step="0.01" min="0" max="100" name="commission_rate" required value="15" class="{{ $input }}"></label>
          <label><span class="{{ $label }}">Covers</span><input name="coverage" maxlength="160" placeholder="e.g. Coast and Uganda" class="{{ $input }}"></label>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 sm:col-span-2"><input type="checkbox" name="cross_border" value="1"> Works outside Kenya</label>
          <div class="mt-4 flex flex-col gap-3 sm:col-span-2 sm:flex-row sm:justify-between">
            <button type="button" @click="adding = false" class="{{ $btn }}">Close</button>
            <button class="{{ $primary }}">Add agency</button>
          </div>
        </form>
      </div>
    </div>

    @if ($agency)
      {{-- add collector --}}
      <div x-cloak x-show="collector" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="collector = false">
        <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="collector = false"></div>
        <div class="relative w-full max-w-[560px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
          <h4 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">Add a collector to {{ $agency->name }}</h4>
          <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">They see only this agency's open placements. Give them the password yourself; they can change it with Forgot password.</p>
          <form method="POST" action="{{ route('staff.agencies.collectors.store', $agency) }}" class="space-y-4">@csrf
            <label class="block"><span class="{{ $label }}">Full name</span><input name="name" required maxlength="120" class="{{ $input }}"></label>
            <label class="block"><span class="{{ $label }}">Email (their sign-in)</span><input type="email" name="email" required maxlength="160" class="{{ $input }}"></label>
            <label class="block"><span class="{{ $label }}">Phone</span><input name="phone" required maxlength="30" class="{{ $input }}"></label>
            <label class="block"><span class="{{ $label }}">Password (10 characters or more)</span><input type="password" name="password" required minlength="10" autocomplete="new-password" class="{{ $input }}"></label>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
              <button type="button" @click="collector = false" class="{{ $btn }}">Close</button>
              <button class="{{ $primary }}">Add collector</button>
            </div>
          </form>
        </div>
      </div>
    @endif
  </div>
@endsection
