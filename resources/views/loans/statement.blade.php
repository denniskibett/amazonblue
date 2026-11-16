{{-- The full loan statement (owner, 16 Nov 2026): every cycle with its own disbursements and repayments.
     Same look as the one-cycle statement on the loan page; prints portrait A4 through loans._print-css. --}}
@extends('layouts.app')

@php
  $kes = fn ($n) => number_format((float) $n, 2);
  $day = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('j M Y') : '—';
@endphp

@section('content')
<div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
  <a href="{{ route('users.loans.show', [$loan->user_id, $loan->id]) }}" class="text-sm font-medium text-brand-500 hover:underline">← Back to loan #{{ $loan->id }}</a>
  <button type="button" onclick="window.print()" class="rounded-lg bg-brand-500 px-3 py-2 text-sm font-medium text-white hover:bg-brand-600">Print full statement</button>
</div>

{{-- a link the borrower can open without signing in, to view, print or save the statement (owner, 16 Nov 2026) --}}
<div class="no-print mb-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]"
     x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js($shareUrl)).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
  <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Share this statement</p>
  <p class="mb-2 text-xs text-gray-500">Anyone with this link can see the statement, print it or save it as a PDF, without signing in. It works until {{ $shareUntil->format('j M Y') }}.</p>
  <div class="flex flex-wrap gap-2">
    <input readonly value="{{ $shareUrl }}" onclick="this.select()" class="h-10 min-w-0 flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" aria-label="Statement link">
    <button type="button" @click="copy()" class="h-10 rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300" x-text="copied ? 'Copied' : 'Copy link'"></button>
    @if ($waUrl)
      <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center rounded-lg bg-emerald-600 px-3 text-sm font-medium text-white hover:bg-emerald-700">Send on WhatsApp</a>
    @endif
    <a href="{{ $shareUrl }}" target="_blank" rel="noopener" class="inline-flex h-10 items-center rounded-lg border border-gray-300 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Open</a>
  </div>
</div>

@include('loans._statement-card')
@endsection

@push('scripts')
  @include('loans._statement-print')
@endpush
