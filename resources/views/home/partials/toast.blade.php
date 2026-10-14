{{-- Toast used by main.js for short confirmations, plus the site script. --}}
<div class="toast" role="status" aria-live="polite">
  <span class="chip sage"
    ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg></span
  ><span data-msg></span>
</div>
<script src="{{ asset('assets/js/main.js') }}" defer></script>
