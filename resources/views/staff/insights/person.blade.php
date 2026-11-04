{{-- One borrower, studied (owner, 4 Nov 2026): trust score, how they earn, literacy from how they use the app, income
     they told us against what they borrow and repay, when in the day they pay, and every loan's outcome. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 0);
  $max = max(1, collect($months)->max(fn ($m) => max($m['paid'], $m['borrowed'])), (float) ($row->income ?? 0));
  $hourTotal = max(1, array_sum($hours));
@endphp

@section('content')
  @include('staff.desk._header', ['title' => $user->name, 'sub' => ($row->work ?? 'Borrower') . ($row?->job_title ? ' · ' . $row->job_title : '') . ($row?->place ? ' · ' . $row->place : '') . ' · with us since ' . $user->created_at->format('M Y'), 'metrics' => false,
    'actions' => '<a href="' . route('users.show', $user) . '" class="inline-flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700">Open the profile</a>'])

  <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ([['Trust score', $row?->score !== null ? $row->score . ' / 100' : '—', 'behaviour profile'], ['On time', $row?->on_time_rate !== null ? round($row->on_time_rate) . '%' : '—', (int) ($row->loans ?? 0) . ' loans'],
      ['Digital literacy', $row->literacy ?? '—', ($row->signals ?? 0) . ' of 4 done themselves'], ['Education', $row->education_band ?? '—', 'they told us'],
      ['Loan ÷ income', $row?->loan_to_income !== null ? $row->loan_to_income . '×' : '—', $row && $row->income > 0 ? 'income ' . $kes($row->income) . ' a month' : 'no income on file']] as [$l, $v, $s])
      <div class="{{ $card }}"><p class="text-theme-sm text-gray-500">{{ $l }}</p><p class="mt-2 font-outfit text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $v }}</p><p class="mt-1 text-theme-xs text-gray-500">{{ $s }}</p></div>
    @endforeach
  </div>

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="{{ $card }} xl:col-span-2">
      <h3 class="font-semibold text-gray-800 dark:text-white/90">Borrowed and repaid by month</h3>
      <p class="mb-4 text-theme-sm text-gray-500">Against the monthly income they told us (dashed line).</p>
      <div class="relative flex h-56 items-end gap-3 border-b border-gray-200 dark:border-gray-800">
        @if ($row && $row->income > 0)<div class="absolute inset-x-0 border-t-2 border-dashed border-gray-400" style="bottom: {{ $row->income / $max * 100 }}%"><span class="absolute -top-5 right-0 text-theme-xs text-gray-500">income {{ $kes($row->income) }}</span></div>@endif
        @foreach ($months as $m)
          <div class="flex flex-1 flex-col items-center gap-1">
            <div class="flex h-48 w-full items-end justify-center gap-1">
              <div class="w-1/3 rounded-t bg-brand-500" style="height: {{ $m['borrowed'] / $max * 100 }}%" title="Borrowed {{ $kes($m['borrowed']) }}"></div>
              <div class="w-1/3 rounded-t bg-success-500" style="height: {{ $m['paid'] / $max * 100 }}%" title="Repaid {{ $kes($m['paid']) }}"></div>
            </div>
            <span class="text-theme-xs text-gray-500">{{ $m['m'] }}</span>
          </div>
        @endforeach
      </div>
      <div class="mt-2 flex gap-4 text-theme-xs text-gray-600"><span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded bg-brand-500"></span>Borrowed</span><span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded bg-success-500"></span>Repaid</span></div>

      <h3 class="mt-6 font-semibold text-gray-800 dark:text-white/90">Every loan</h3>
      <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach ($loans as $l)
          @php $tone = in_array($l->status, ['repaid', 'completed', 'closed'], true) && (int) $l->days_overdue === 0 ? 'bg-success-500' : (in_array($l->status, ['written_off', 'defaulted', 'recovery'], true) ? 'bg-error-500' : ((int) $l->days_overdue > 0 || $l->status === 'overdue' ? 'bg-orange-500' : 'bg-brand-500')); @endphp
          <a href="{{ route('loans.show', $l->id) }}" title="#{{ $l->id }} · {{ $kes($l->amount) }} · {{ $l->status }}" class="size-5 rounded {{ $tone }}"></a>
        @endforeach
      </div>
      <p class="mt-2 text-theme-xs text-gray-500">Green paid · blue open · amber late · red defaulted or written off. {{ $repayments }} repayments, {{ $kes($paid) }} in all.</p>
    </div>

    <div class="space-y-6">
      <div class="{{ $card }}">
        <h3 class="font-semibold text-gray-800 dark:text-white/90">How they use the app</h3>
        <p class="mb-2 text-theme-sm text-gray-500">Signs of digital literacy, used to explain things better, never to refuse.</p>
        @foreach ($signals as $s => $yes)
          <div class="flex items-center justify-between border-t border-gray-100 py-2 text-theme-sm dark:border-gray-800"><span>{{ $s }}</span><x-staff.pill :tone="$yes ? 'green' : 'gray'">{{ $yes ? 'Yes' : 'No' }}</x-staff.pill></div>
        @endforeach
      </div>
      <div class="{{ $card }}">
        <h3 class="font-semibold text-gray-800 dark:text-white/90">When they pay</h3>
        @foreach ($hours as $h => $n)
          <div class="mt-3"><div class="flex justify-between text-theme-sm"><span>{{ $h }}</span><b class="font-outfit">{{ round($n / $hourTotal * 100) }}%</b></div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><div class="h-2 rounded-full bg-brand-500" style="width: {{ $n / $hourTotal * 100 }}%"></div></div></div>
        @endforeach
        <p class="mt-3 text-theme-xs text-gray-500">From when each payment reached us. Send reminders just before their usual time.</p>
      </div>
      <p class="rounded-lg bg-blue-light-50 p-3 text-theme-xs text-blue-light-700 dark:bg-blue-light-500/10 dark:text-blue-light-400">Religion, tribe, disability and health are never used to score anyone (Data Protection Act 2019).</p>
    </div>
  </div>
@endsection
