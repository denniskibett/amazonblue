{{-- Suggest (owner, 17 Nov 2026): on every signed-in page, for staff, borrowers and partners alike. An idea or a problem
     goes to the sysadmin's Features and suggestions page with the page it was sent from. --}}
@auth
<div x-data="{ open: false, kind: 'idea', body: '', sending: false, err: '',
      async send() {
        if (this.body.trim().length < 5) { this.err = 'Write a few words about it.'; return; }
        this.sending = true; this.err = '';
        try {
          const r = await fetch(@js(route('suggestions.store')), { method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ kind: this.kind, body: this.body, page: location.pathname }) });
          if (!r.ok) { const j = await r.json().catch(() => ({})); this.err = j.message || 'It did not send. Try again in a minute.'; return; }
          const what = this.kind === 'problem' ? 'report' : 'suggestion';
          this.open = false; this.body = ''; this.kind = 'idea';
          window.toast && window.toast('Thanks, we got your ' + what + '. The team reviews every one.');
        } catch (e) { this.err = 'It did not send. Check your connection.'; } finally { this.sending = false; }
      } }"
  @keydown.escape.window="open = false" class="fixed bottom-5 right-5 z-[60] print:hidden" data-suggest>
  <div x-show="open" x-cloak x-transition.origin.bottom.right @click.outside="open = false"
    class="mb-3 w-[min(22rem,calc(100vw-2.5rem))] rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900" role="dialog" aria-label="Send a suggestion">
    <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Tell us what would help</p>
    <p class="mb-3 text-theme-xs text-gray-500 dark:text-gray-400">An idea for this page, or something that isn't working.</p>
    <div class="mb-3 flex gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800" role="radiogroup" aria-label="Kind">
      <button type="button" @click="kind = 'idea'" :aria-pressed="kind === 'idea'" :class="kind === 'idea' ? 'bg-white text-gray-800 shadow-theme-xs dark:bg-gray-900 dark:text-white' : 'text-gray-500'" class="flex-1 rounded-md px-3 py-1.5 text-theme-sm font-medium">An idea</button>
      <button type="button" @click="kind = 'problem'" :aria-pressed="kind === 'problem'" :class="kind === 'problem' ? 'bg-white text-gray-800 shadow-theme-xs dark:bg-gray-900 dark:text-white' : 'text-gray-500'" class="flex-1 rounded-md px-3 py-1.5 text-theme-sm font-medium">A problem</button>
    </div>
    <label for="suggest-body" class="sr-only">Your suggestion</label>
    <textarea id="suggest-body" x-model="body" rows="4" maxlength="2000" :placeholder="kind === 'problem' ? 'What went wrong, and what did you expect?' : 'What would make this easier?'"
      class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-theme-sm text-gray-800 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90"></textarea>
    <p x-show="err" x-text="err" class="mt-1 text-theme-xs text-error-600"></p>
    <div class="mt-3 flex justify-end gap-2">
      <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Cancel</button>
      <button type="button" @click="send()" :disabled="sending" class="rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60" x-text="sending ? 'Sending…' : 'Send'">Send</button>
    </div>
  </div>
  <button type="button" @click="open = !open" :aria-expanded="open" class="ml-auto flex items-center gap-2 rounded-full bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-lg hover:bg-brand-600">
    <x-ui.icon name="send" class="size-4" />Suggest
  </button>
</div>
@endauth
