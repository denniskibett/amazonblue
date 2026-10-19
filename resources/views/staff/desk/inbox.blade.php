{{-- Approvals inbox: everything waiting for the signed-in user, by permission. --}}
@extends('layouts.app')

@php $card = 'flex flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6'; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'My inbox', 'sub' => 'Everything waiting for you, ' . auth()->user()->name . '.'])

  @if (empty($cards) && $businesses === null)
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <x-staff.empty title="Nothing for your role" message="Your role has no approval or work queues. Use the menu to look things up." />
    </div>
  @else
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
      @foreach ($cards as $c)
        <section class="{{ $card }}">
          <div class="flex items-start justify-between gap-3">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $c['title'] }}</h2>
            <span @class(['rounded-full px-2.5 py-0.5 font-outfit text-theme-sm font-semibold', 'bg-az-ink-blue text-white' => $c['count'] > 0, 'bg-gray-100 text-gray-500 dark:bg-gray-800' => $c['count'] === 0])>{{ $c['count'] }}</span>
          </div>
          @if (! empty($c['sub']))<p class="mt-1 text-theme-sm text-gray-500">{{ $c['sub'] }}</p>@endif
          @if (! empty($c['rows']) && $c['rows']->isNotEmpty())
            <ul class="mt-3 divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
              @foreach ($c['rows'] as $loan)
                <li class="flex items-center justify-between gap-3 py-2">
                  <span class="min-w-0 truncate"><span class="text-gray-500">#{{ $loan->id }}</span> {{ $loan->user->name ?? '—' }}</span>
                  <x-staff.money :amount="$loan->amount" />
                </li>
              @endforeach
            </ul>
          @elseif (! empty($c['people']) && $c['people']->isNotEmpty())
            <ul class="mt-3 divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
              @foreach ($c['people'] as $p)
                <li class="flex items-center justify-between gap-3 py-2"><span class="min-w-0 truncate">{{ $p->name }}</span><span class="text-theme-xs text-gray-500">{{ $p->kyc_submitted_at ? \Illuminate\Support\Carbon::parse($p->kyc_submitted_at)->diffForHumans(short: true) : '' }}</span></li>
              @endforeach
            </ul>
          @elseif ($c['count'] === 0)
            <p class="mt-3 text-theme-sm text-gray-500">All clear.</p>
          @endif
          <a href="{{ $c['link'] }}" class="mt-auto pt-4 text-theme-sm font-medium text-brand-500 hover:underline">{{ $c['action'] }} →</a>
        </section>
      @endforeach

      @if ($businesses !== null)
        <section class="{{ $card }}">
          <div class="flex items-start justify-between gap-3">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Business submissions</h2>
            <span @class(['rounded-full px-2.5 py-0.5 font-outfit text-theme-sm font-semibold', 'bg-az-ink-blue text-white' => $businesses > 0, 'bg-gray-100 text-gray-500 dark:bg-gray-800' => $businesses === 0])>{{ $businesses }}</span>
          </div>
          <p class="mt-1 text-theme-sm text-gray-500">Businesses waiting for review.</p>
          @if (\Illuminate\Support\Facades\Route::has('business-reviews.index'))
            <a href="{{ route('business-reviews.index') }}" class="mt-auto pt-4 text-theme-sm font-medium text-brand-500 hover:underline">Open business reviews →</a>
          @endif
        </section>
      @endif
    </div>
  @endif
@endsection
