{{-- resources/views/dashboard.blade.php: one dashboard for every staff role (owner, 22 Oct 2026).
     The cards come from App\Support\PageMetrics::pageDashboard(), which picks the right six for the role. --}}
@extends('layouts.app')

@section('content')
@php
  $u = auth()->user();
  $hour = (int) now()->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $greeting }}, {{ \Illuminate\Support\Str::of($u->name)->before(' ') }}</h2>
    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">The loan book as of {{ now()->format('l, j F Y') }}.</p>
  </div>
  @can('view reports')
    <a href="{{ route('staff.portfolio') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
      <x-ui.icon name="pie" class="size-5" /> Portfolio
    </a>
  @endcan
</div>

<x-ui.page-metrics page="dashboard" />

<div class="grid grid-cols-12 gap-4 md:gap-6">
  @if ($u->can('view reports') || $u->hasRole('broker'))
    <div class="col-span-12">
      @include('partials.chart.monthly-loans', ['monthlyData' => $monthlyData ?? []])
    </div>
  @endif

  @if ($u->can('view loans'))
    <div class="col-span-12">
      @include('partials.table.table-due-loans', ['dueLoans' => $dueLoans ?? collect()])
    </div>
  @endif

  @if ($u->hasRole('broker') && ! empty($broker))
    <div class="col-span-12">
      @include('partials.media-card', ['broker' => $broker ?? null, 'clients' => $clients ?? 0, 'activeLoans' => $activeLoans ?? 0])
    </div>
  @endif
</div>

@include('partials.modal.flash-alert')
@endsection
