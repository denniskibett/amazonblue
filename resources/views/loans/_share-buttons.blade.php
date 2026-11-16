{{-- Share the borrower's statement link (owner, 16 Nov 2026): Copy link, Send on WhatsApp, Open.
     $share from LoanStatementController::share(); $size: 'sm' (loan page header) or default. --}}
@php
  $btn = 'inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium shadow-theme-xs';
  $ghost = $btn . ' border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
@endphp
<span class="contents" x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js($share['url'])).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
  <button type="button" @click="copy()" class="{{ $ghost }}" title="Anyone with the link can see the statement, print it or save it as a PDF, without signing in. It works until {{ $share['until']->format('j M Y') }}.">
    <span x-text="copied ? 'Copied' : 'Copy link'">Copy link</span>
  </button>
  @if ($share['wa'])
    <a href="{{ $share['wa'] }}" target="_blank" rel="noopener" class="{{ $btn }} bg-emerald-600 text-white hover:bg-emerald-700">Send on WhatsApp</a>
  @endif
  <a href="{{ $share['url'] }}" target="_blank" rel="noopener" class="{{ $ghost }}">Open</a>
</span>
