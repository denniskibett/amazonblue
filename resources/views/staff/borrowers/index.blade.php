{{-- Borrowers (owner, 23 Oct 2026): what we know about each borrower, what is missing, and how they repay. --}}
@extends('layouts.app')

@php
  $types = \App\Http\Controllers\HomeController::INCOME_TYPES;
  $freq = \App\Models\Borrower::PAY_FREQUENCIES;
  $kyc = ['approved' => ['Verified', 'green'], 'submitted' => ['Checking', 'blue'], 'rejected' => ['Needs fixing', 'red']];
  $pct = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Borrowers', 'sub' => 'Everyone who borrows: verification, income, employer, pay schedule, what is missing, and how they repay.', 'metrics' => false,
    'actions' => auth()->user()->hasAnyRole(['super-admin', 'admin', 'manager']) ? '<a href="' . route('staff.messages.create', ['kind' => 'complete_profile']) . '" class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Ask them to complete their profile</a>' : null])

  <x-ui.metrics :items="[
    ['label' => 'Borrowers', 'value' => (string) $counts['all'], 'icon' => 'users', 'hint' => $counts['complete'] . ' with a complete profile'],
    ['label' => 'No income on file', 'value' => (string) $counts['no_income'], 'icon' => 'wallet', 'hint' => 'their limit has no income cap'],
    ['label' => 'No pay schedule', 'value' => (string) $counts['no_pay'], 'icon' => 'calendar', 'hint' => 'reminders can\'t follow payday'],
    ['label' => 'No borrowers record', 'value' => (string) $counts['no_row'], 'icon' => 'alert', 'hint' => 'borrowers:fill-gaps --apply'],
    ['label' => 'Avoid weekend lending', 'value' => (string) $counts['weekend'], 'icon' => 'clock', 'hint' => 'pay late on Fri–Sun loans'],
  ]" />

  <x-staff.table title="All borrowers" caption="Borrowers" data-table-page="25"
    :headers="['Borrower', 'Income · employer', 'Paid', 'Missing', ['Score', 'num', 'mobile'], ['On time', 'num'], ['Comfortable up to', 'num'], ['Suggested limit', 'num'], 'What to know']">
    @foreach ($rows as $r)
      @php [$kycText, $kycTone] = $kyc[$r->user->kyc_status] ?? ['Not verified', 'gray']; @endphp
      <tr>
        <td data-sort-value="{{ $r->user->name }}" data-filter-value="{{ $kycText }}">
          <x-staff.person :user="$r->user" :sub="$r->user->phone" />
          <span class="mt-1 inline-block"><x-staff.pill :tone="$kycTone" dot>{{ $kycText }}</x-staff.pill>@if ($r->open)<x-staff.pill tone="blue" class="ml-1">{{ $r->open }} open</x-staff.pill>@endif</span>
        </td>
        <td data-filter-value="{{ $r->b?->income_type ? ($types[$r->b->income_type] ?? $r->b->income_type) : 'Unknown' }}">
          <span class="text-gray-800 dark:text-white/90">{{ $r->b && (float) $r->b->net_salary > 0 ? 'KES ' . number_format((float) $r->b->net_salary, 2) : '—' }}</span>
          <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r->b?->income_type ? ($types[$r->b->income_type] ?? $r->b->income_type) : 'income type unknown' }}{{ $r->b && ($r->b->employer_name || $r->b->workplace) ? ' · ' . ($r->b->employer_name ?: $r->b->workplace) : '' }}</span>
        </td>
        <td data-filter-value="{{ $r->b?->pay_frequency ? ($freq[$r->b->pay_frequency] ?? $r->b->pay_frequency) : 'Unknown' }}">
          @if ($r->b?->pay_frequency){{ $freq[$r->b->pay_frequency] ?? $r->b->pay_frequency }}@if ($r->b->pay_day)<span class="block text-theme-xs text-gray-500 dark:text-gray-400">on the {{ \Illuminate\Support\Number::ordinal($r->b->pay_day) }}</span>@endif
          @else<span class="text-gray-400">—</span>@endif
        </td>
        <td class="!whitespace-normal" data-filter-value="{{ $r->missing ? 'Missing details' : 'Complete' }}">
          @if ($r->missing)<div class="flex max-w-[220px] flex-wrap gap-1">@foreach ($r->missing as $m)<x-staff.pill tone="amber">{{ ucfirst($m) }}</x-staff.pill>@endforeach</div>
          @else<x-staff.pill tone="green" dot>Complete</x-staff.pill>@endif
        </td>
        <td class="num" data-sort-value="{{ $r->score ?? -1 }}">@if ($r->score === null)<span class="text-gray-400">—</span>@else<x-staff.pill :tone="$r->score >= 80 ? 'green' : ($r->score >= 60 ? 'amber' : 'red')" class="font-outfit">{{ $r->score }}</x-staff.pill>@endif</td>
        <td class="num" data-sort-value="{{ $r->on_time ?? -1 }}">{{ $pct($r->on_time) }}</td>
        <td class="num" data-sort-value="{{ $r->comfort }}">{{ $r->comfort > 0 ? number_format($r->comfort, 2) : '—' }}</td>
        <td class="num" data-sort-value="{{ $r->suggested }}">{{ $r->suggested ? number_format($r->suggested, 2) : '—' }}</td>
        <td class="!whitespace-normal" data-filter-value="{{ $r->flags ? implode(', ', $r->flags) : 'Nothing' }}">
          <div class="flex max-w-xs flex-wrap gap-1">@forelse ($r->flags as $f)<x-staff.pill :tone="str_contains($f, 'Never') ? 'green' : (str_contains($f, 'bad') || str_contains($f, 'Overdue') ? 'red' : 'amber')">{{ $f }}</x-staff.pill>@empty<span class="text-theme-xs text-gray-400">—</span>@endforelse</div>
        </td>
      </tr>
    @endforeach
  </x-staff.table>
  <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400">Behaviour is worked out every night at 02:15 (analytics:profiles) and copied onto each borrower's record. Income, employer and pay schedule come from verification or the borrower's "Complete your profile" page.</p>
@endsection
