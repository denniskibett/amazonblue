{{-- Capital raising (owner, 4 Nov 2026): an investment manager's quarter target, pipeline from first call to money in,
     investors and what they hold; admins and the owner also see every manager side by side and capital in against
     money lent. Staff\CapitalController. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $th = 'px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400';
  $td = 'whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300';
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 0);
  $short = fn ($v) => abs($v) >= 1e6 ? 'KES ' . number_format($v / 1e6, 1) . 'M' : $kes($v);
  $S = \App\Http\Controllers\Staff\CapitalController::STAGES;
  $pct = $target > 0 ? min(100, round($raisedQ / $target * 100)) : null;
  $C = 2 * M_PI * 58;
  $fmax = max(1, $funnel->max('amount'));
  $mmax = max(1, $months->max(fn ($m) => max($m['in'], $m['lent'])));
  $stageTone = ['first_call' => 'gray', 'meeting' => 'blue', 'due_diligence' => 'amber', 'term_sheet' => 'purple', 'won' => 'green', 'lost' => 'red'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Capital raising', 'sub' => $all ? 'Every investment manager, their pipeline and their investors. They report to the owner.' : 'Your pipeline from first call to money in, and the investors you look after.', 'metrics' => false,
    'actions' => ($canAll ? '<a href="' . route('staff.capital', $all ? ['mine' => 1] : []) . '" class="inline-flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700">' . ($all ? 'Only mine' : 'All managers') . '</a>' : '')
      . '<button type="button" onclick="document.getElementById(\'add-prospect\').showModal()" class="inline-flex h-11 items-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white">+ Prospect</button>'])

  <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ([['Raised this quarter', $short($raisedQ), $target > 0 ? 'target ' . $short($target) . ' · ' . $pct . '%' : 'set CAPITAL_TARGET_QUARTER'], ['Pipeline (weighted)', $short($weighted), $short($pipelineFull) . ' if all close'],
      ['Investors', $partners->count(), $partners->whereIn('type', ['corporate', 'company', 'business', 'sacco'])->count() . ' institutions'], ['Capital in, all time', $short($raisedAll), 'from these investors'],
      ['Paid back to them', $short($paidOut), 'principal and returns']] as [$l, $v, $s])
      <div class="{{ $card }}"><p class="text-theme-sm text-gray-500">{{ $l }}</p><p class="mt-2 whitespace-nowrap font-outfit text-2xl font-bold text-gray-800 dark:text-white/90">{{ $v }}</p><p class="mt-1 text-theme-xs text-gray-500">{{ $s }}</p></div>
    @endforeach
  </div>

  <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
    <div class="{{ $card }} xl:col-span-2">
      <h3 class="font-semibold text-gray-800 dark:text-white/90">Quarter target</h3>
      <div class="mt-4 flex items-center gap-6">
        <svg width="150" height="150" viewBox="0 0 150 150" aria-hidden="true"><circle cx="75" cy="75" r="58" fill="none" stroke="#F2F4F7" stroke-width="16" />
          <circle cx="75" cy="75" r="58" fill="none" stroke="#465FFF" stroke-width="16" stroke-linecap="round" stroke-dasharray="{{ round($C * ($pct ?? 0) / 100, 1) }} {{ round($C, 1) }}" transform="rotate(-90 75 75)" />
          <text x="75" y="83" text-anchor="middle" font-size="26" font-weight="700" fill="#1D2939">{{ $pct !== null ? $pct . '%' : '—' }}</text></svg>
        <div class="text-theme-sm text-gray-600 dark:text-gray-400">
          <p class="font-semibold text-gray-800 dark:text-white/90">{{ $short($raisedQ) }}{{ $target > 0 ? ' of ' . $short($target) : '' }}</p>
          <p class="mt-1">{{ (int) now()->diffInDays(now()->endOfQuarter()) }} days left in the quarter.</p>
          @if ($target > 0)<p class="mt-1">Needs {{ $short(max(0, $target - $raisedQ)) }} more; the weighted pipeline holds {{ $short($weighted) }}.</p>@endif
        </div>
      </div>
      <h3 class="mt-6 font-semibold text-gray-800 dark:text-white/90">Pipeline</h3>
      @foreach ($funnel as $k => $f)
        <div class="mt-2.5 flex items-center gap-3 text-theme-sm"><span class="w-28 shrink-0 text-gray-600 dark:text-gray-400">{{ $f['label'] }}</span>
          <div class="h-7 rounded-md {{ $k === 'won' ? 'bg-success-500' : 'bg-brand-500' }}" style="width: {{ max(2, $f['amount'] / $fmax * 60) }}%; opacity: {{ 1 - $loop->index * 0.12 }}"></div>
          <span class="whitespace-nowrap font-outfit font-medium text-gray-800 dark:text-white/90">{{ $short($f['amount']) }} · {{ $f['n'] }}</span></div>
      @endforeach
    </div>

    <div class="{{ $card }} xl:col-span-3">
      <h3 class="font-semibold text-gray-800 dark:text-white/90">Capital in vs money lent</h3>
      <p class="mb-4 text-theme-sm text-gray-500">Are we raising faster than we can lend? By month, all investors.</p>
      <div class="flex h-56 items-end gap-4 border-b border-gray-200 dark:border-gray-800">
        @foreach ($months as $m)
          <div class="flex flex-1 flex-col items-center gap-1">
            <div class="flex h-48 w-full items-end justify-center gap-1.5">
              <div class="w-2/5 rounded-t bg-brand-500" style="height: {{ $m['in'] / $mmax * 100 }}%" title="Capital in {{ $kes($m['in']) }}"></div>
              <div class="w-2/5 rounded-t bg-success-500" style="height: {{ $m['lent'] / $mmax * 100 }}%" title="Lent {{ $kes($m['lent']) }}"></div>
            </div>
            <span class="text-theme-xs text-gray-500">{{ $m['m'] }}</span>
          </div>
        @endforeach
      </div>
      <div class="mt-2 flex gap-4 text-theme-xs text-gray-600"><span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded bg-brand-500"></span>Capital in</span><span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded bg-success-500"></span>Lent to borrowers</span></div>
    </div>
  </div>

  @if ($all && $byManager->isNotEmpty())
    <div class="mb-6 {{ $card }}">
      <h3 class="font-semibold text-gray-800 dark:text-white/90">Investment managers · reporting to the owner</h3>
      <div class="mt-3 overflow-x-auto"><table class="w-full min-w-[760px]">
        <thead><tr class="border-b border-gray-100 dark:border-gray-800">@foreach (['Manager', 'Raised this quarter', 'Raised, all time', 'Investors', 'Still invested', 'Their investors earned', 'Pipeline (weighted)'] as $h)<th class="{{ $th }}">{{ $h }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($byManager as $m)
            <tr><td class="{{ $td }} font-medium">{{ $m['name'] }}</td><td class="{{ $td }} font-outfit">{{ $kes($m['raised_q']) }}</td><td class="{{ $td }} font-outfit">{{ $kes($m['raised']) }}</td><td class="{{ $td }}">{{ $m['investors'] }}</td>
              <td class="{{ $td }}">{{ $m['kept'] !== null ? $m['kept'] . '%' : '—' }}</td><td class="{{ $td }}">{{ $m['investor_return'] !== null ? $m['investor_return'] . '% of capital' : '—' }}</td><td class="{{ $td }} font-outfit">{{ $kes($m['pipeline']) }}</td></tr>
          @endforeach
        </tbody>
      </table></div>
    </div>
  @endif

  <x-staff.tabs :tabs="['pipeline' => ['Pipeline', $open->count()], 'won' => ['Money in', $prospects->where('stage', 'won')->count()], 'investors' => ['Investors', $partners->count()], 'lost' => ['Lost', $prospects->where('stage', 'lost')->count()]]">
    @foreach (['pipeline' => $open, 'won' => $prospects->where('stage', 'won'), 'lost' => $prospects->where('stage', 'lost')] as $tab => $rows)
      <x-staff.tab :name="$tab">
        <div class="overflow-x-auto"><table class="w-full min-w-[1000px]">
          <thead><tr class="border-b border-gray-100 dark:border-gray-800">@foreach (['Investor', 'Wants', 'Ticket', 'Chance', 'Next step', $all ? 'Manager' : null, 'Stage'] as $h)@if ($h !== null)<th class="{{ $th }} {{ $loop->first ? 'pl-6' : '' }}">{{ $h }}</th>@endif @endforeach</tr></thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($rows as $p)
              <tr>
                <td class="{{ $td }} pl-6"><span class="font-medium text-gray-800 dark:text-white/90">{{ $p->name }}</span><span class="block text-theme-xs text-gray-500">{{ \App\Http\Controllers\Staff\CapitalController::KINDS[$p->kind] ?? $p->kind }}{{ $p->contact ? ' · ' . $p->contact : '' }}</span></td>
                <td class="{{ $td }}">{{ $p->wants ?: '—' }}</td><td class="{{ $td }} font-outfit">{{ $p->ticket ? $kes($p->ticket) : '—' }}</td><td class="{{ $td }}">{{ $p->chance !== null ? $p->chance . '%' : '—' }}</td>
                <td class="{{ $td }}">{{ $p->next_step ?: '—' }}{{ $p->next_on ? ' · ' . \Illuminate\Support\Carbon::parse($p->next_on)->format('j M') : '' }}</td>
                @if ($all)<td class="{{ $td }}">{{ $p->manager ?? '—' }}</td>@endif
                <td class="{{ $td }}">
                  <form method="POST" action="{{ route('staff.capital.update', $p->id) }}" class="flex items-center gap-2" x-data="{ s: @js($p->stage) }">@csrf
                    <select name="stage" x-model="s" class="h-9 rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900">@foreach ($S as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                    <input type="number" name="chance" min="0" max="100" value="{{ $p->chance }}" placeholder="%" class="h-9 w-16 rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Chance">
                    <select name="partner_id" x-show="s === 'won'" class="h-9 rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Became partner"><option value="">Which partner?</option>@foreach (\App\Models\Partner::where('is_house_account', false)->orderBy('name')->get(['id', 'name']) as $pp)<option value="{{ $pp->id }}" @selected($p->partner_id == $pp->id)>{{ $pp->name }}</option>@endforeach</select>
                    <button class="h-9 rounded-lg bg-brand-500 px-3 text-sm text-white">Save</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Nothing here yet. Add a prospect when someone shows interest.</td></tr>
            @endforelse
          </tbody>
        </table></div>
      </x-staff.tab>
    @endforeach
    <x-staff.tab name="investors">
      <div class="overflow-x-auto"><table class="w-full min-w-[700px]">
        <thead><tr class="border-b border-gray-100 dark:border-gray-800"><th class="{{ $th }} pl-6">Investor</th><th class="{{ $th }}">Type</th><th class="{{ $th }}">Looked after by</th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @foreach ($partners as $pt)
            <tr><td class="{{ $td }} pl-6"><a class="font-medium text-brand-500 hover:underline" href="{{ route('partners.show', $pt->id) }}">{{ $pt->name }}</a></td><td class="{{ $td }}">{{ ucfirst($pt->type) }}</td>
              <td class="{{ $td }}">
                @if ($canAll)
                  <form method="POST" action="{{ route('staff.capital.assign') }}" class="flex gap-2">@csrf<input type="hidden" name="partner_id" value="{{ $pt->id }}">
                    <select name="manager_id" class="h-9 rounded-lg border border-gray-300 px-2 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="">Nobody</option>@foreach ($managers as $m)<option value="{{ $m->id }}" @selected($pt->investment_manager_id == $m->id)>{{ $m->name }}</option>@endforeach</select>
                    <button class="h-9 rounded-lg border border-gray-300 px-3 text-sm dark:border-gray-700">Save</button></form>
                @else
                  You
                @endif
              </td></tr>
          @endforeach
        </tbody>
      </table></div>
    </x-staff.tab>
  </x-staff.tabs>

  <dialog id="add-prospect" class="w-full max-w-lg rounded-2xl p-0 backdrop:bg-gray-900/50">
    <form method="POST" action="{{ route('staff.capital.store') }}" class="space-y-4 p-6">@csrf
      <h3 class="text-lg font-semibold text-gray-800">New prospect</h3>
      <div class="grid grid-cols-2 gap-3">
        <label class="col-span-2 text-sm text-gray-700">Name<input name="name" required class="{{ $input }} mt-1"></label>
        <label class="text-sm text-gray-700">Type<select name="kind" class="{{ $input }} mt-1">@foreach (\App\Http\Controllers\Staff\CapitalController::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
        <label class="text-sm text-gray-700">Contact<input name="contact" placeholder="phone or email" class="{{ $input }} mt-1"></label>
        <label class="text-sm text-gray-700">Wants<input name="wants" placeholder="short-term book, asset pool…" class="{{ $input }} mt-1"></label>
        <label class="text-sm text-gray-700">Ticket (KES)<input name="ticket" type="number" min="0" class="{{ $input }} mt-1"></label>
        <label class="text-sm text-gray-700">Stage<select name="stage" class="{{ $input }} mt-1">@foreach ($S as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
        <label class="text-sm text-gray-700">Chance (%)<input name="chance" type="number" min="0" max="100" class="{{ $input }} mt-1"></label>
        <label class="text-sm text-gray-700">Next step<input name="next_step" class="{{ $input }} mt-1"></label>
        <label class="text-sm text-gray-700">On<input name="next_on" type="date" class="{{ $input }} mt-1"></label>
        @if ($canAll)<label class="col-span-2 text-sm text-gray-700">Manager<select name="manager_id" class="{{ $input }} mt-1"><option value="">Me</option>@foreach ($managers as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></label>@endif
      </div>
      <div class="flex justify-end gap-2"><button type="button" onclick="this.closest('dialog').close()" class="h-11 rounded-lg border border-gray-300 px-4 text-sm">Cancel</button><button class="h-11 rounded-lg bg-brand-500 px-4 text-sm font-medium text-white">Add</button></div>
    </form>
  </dialog>
@endsection
