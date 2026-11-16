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
    <a href="{{ $share['wa'] }}" target="_blank" rel="noopener" class="{{ $btn }} bg-[#25D366] text-white hover:bg-[#1ebe5a]" title="Send on WhatsApp">
      <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.43 9.43 0 0 1-4.8-1.32l-.35-.2-3.57.94.95-3.48-.22-.36a9.4 9.4 0 0 1-1.45-5.02c0-5.2 4.24-9.44 9.46-9.44a9.4 9.4 0 0 1 6.68 2.77 9.39 9.39 0 0 1 2.76 6.68c0 5.21-4.24 9.44-9.45 9.44m8.04-17.48A11.3 11.3 0 0 0 12.05.7C5.79.7.69 5.79.69 12.05c0 2 .52 3.95 1.52 5.67L.6 23.3l5.73-1.5a11.3 11.3 0 0 0 5.42 1.38h.01c6.26 0 11.35-5.1 11.35-11.35 0-3.03-1.18-5.88-3.32-8.03"/></svg>
      WhatsApp</a>
  @endif
  <a href="{{ $share['url'] }}" target="_blank" rel="noopener" class="{{ $ghost }}">Open</a>
</span>
