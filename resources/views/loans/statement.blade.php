{{-- The full loan statement (owner, 16 Nov 2026): every cycle with its own disbursements and repayments.
     Same look as the one-cycle statement on the loan page; prints portrait A4 through loans._print-css. --}}
@extends('layouts.app')

@php
  $kes = fn ($n) => number_format((float) $n, 2);
  $day = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
@endphp

@section('content')
{{-- one toolbar above the statement (owner, 16 Nov 2026): back on the left, share and print together on the right --}}
<div class="no-print mb-4 flex flex-wrap items-center justify-between gap-2">
  <a href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}"
     class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">← Back to loan #{{ $loan->id }}</a>
  <div class="flex flex-wrap items-center gap-2">
    @include('loans._share-buttons', ['share' => $share])
    <button type="button" onclick="window.print()" class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Print full statement</button>
  </div>
</div>

@include('loans._statement-card')
@endsection

@push('scripts')
  @include('loans._statement-print')
@endpush
