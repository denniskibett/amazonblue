{{-- Approve an extension request (Stage 4 Phase 5): a slide-over like partials/modal/loan-rollover-modal.
<style>[x-cloak]{display:none!important}</style>
     The new cycle starts from the last due date by default; staff may pick another day (older loans, a late loan).
     Open with: $dispatch('extend-request', {id: <borrower_requests.id>}) --}}
<div x-data="extendModal(@js(route('staff.requests.preview', ['id' => '__ID__'])), @js(route('staff.requests.borrower', ['id' => '__ID__'])), @js(csrf_token()))"
     @extend-request.window="load($event.detail.id)" @keydown.escape.window="close()" x-cloak>
  <div x-show="open" x-transition.opacity class="fixed inset-0 z-[99999] bg-black/50" @click="close()"></div>
  <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
       class="fixed right-0 top-0 z-[99999] h-full w-full max-w-2xl overflow-y-auto bg-white shadow-2xl dark:bg-gray-900" role="dialog" aria-modal="true" aria-label="Approve extension">
    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
      <div>
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Approve extension</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="d ? d.borrower + ' · loan #' + d.loan + (d.product ? ' · ' + d.product : '') : 'Loading…'"></p>
      </div>
      <button type="button" @click="close()" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
    </div>

    <form method="POST" :action="submitUrl" class="space-y-5 p-6 text-sm" @submit="if (! (d && d.ok)) { $event.preventDefault(); }">
      <input type="hidden" name="_token" :value="token">
      <input type="hidden" name="decision" value="approve">

      <template x-if="d && d.cycle">
        <div class="grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
          <div><span class="text-gray-500">Current cycle</span><p class="font-semibold text-gray-900 dark:text-white" x-text="'Cycle ' + d.cycle.n + ' · ' + d.cycle.rate + '%'"></p></div>
          <div><span class="text-gray-500">Runs</span><p class="font-semibold text-gray-900 dark:text-white" x-text="fmt(d.cycle.start) + ' → ' + fmt(d.cycle.due)"></p></div>
          <div><span class="text-gray-500">Owed on the due date</span><p class="font-semibold tabular-nums text-gray-900 dark:text-white" x-text="'KES ' + money(d.cycle.owed_on_due)"></p></div>
          <div><span class="text-gray-500">Owed today</span><p class="font-semibold tabular-nums text-gray-900 dark:text-white" x-text="'KES ' + money(d.cycle.owed_today)"></p></div>
        </div>
      </template>

      <fieldset class="space-y-2">
        <legend class="mb-1 font-medium text-gray-800 dark:text-white/90">The new cycle starts</legend>
        <label class="flex items-start gap-3 rounded-lg border p-3" :class="mode === 'due' ? 'border-brand-500 bg-brand-50/50 dark:bg-brand-500/10' : 'border-gray-200 dark:border-gray-700'">
          <input type="radio" value="due" x-model="mode" @change="preview()" class="mt-1">
          <span><span class="font-medium text-gray-800 dark:text-white/90" x-text="'From the last due date' + (d && d.cycle ? ' (' + fmt(d.cycle.due) + ')' : '')"></span>
            <span class="block text-theme-xs text-gray-500">Recommended: the new cycle picks up where the old one ended, with no penalty days in between.</span></span>
        </label>
        <label class="flex items-start gap-3 rounded-lg border p-3" :class="mode === 'pick' ? 'border-brand-500 bg-brand-50/50 dark:bg-brand-500/10' : 'border-gray-200 dark:border-gray-700'">
          <input type="radio" value="pick" x-model="mode" @change="preview()" class="mt-1">
          <span class="flex-1"><span class="font-medium text-gray-800 dark:text-white/90">Pick a date</span>
            <span class="block text-theme-xs text-gray-500">For older loans, or a late loan extended from a later day (the penalty up to that day is carried).</span>
            <input type="date" x-show="mode === 'pick'" x-model="date" @change="preview()" :min="d && d.cycle ? d.cycle.start : ''" :max="d ? d.today : ''"
                   class="mt-2 h-10 rounded-lg border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900" aria-label="Start date"></span>
        </label>
        <input type="hidden" name="date" :value="mode === 'pick' ? date : (d && d.cycle ? (d.cycle.due <= d.today ? d.cycle.due : d.today) : '')">
      </fieldset>

      <div x-show="d && d.needs_rate">
        <label class="font-medium text-gray-800 dark:text-white/90">Rate for the new cycle (%)
          <input type="number" name="interest_rate" step="0.01" min="0" max="100" x-model="rate" @change="preview()"
                 class="mt-1 block h-10 w-32 rounded-lg border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900"></label>
        <p class="mt-1 text-theme-xs text-gray-500" x-text="d ? 'From cycle ' + d.rate_from_cycle + ' a rate must be set (or use a payment plan).' : ''"></p>
      </div>

      <div x-show="loading" class="text-gray-500">Working out the new cycle…</div>
      <div x-show="d && ! d.ok && ! loading" class="rounded-lg bg-amber-50 px-3 py-2 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200" x-text="d && d.message"></div>

      <template x-if="d && d.ok && ! loading">
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/30 dark:bg-emerald-500/10">
          <p class="mb-2 font-semibold text-emerald-900 dark:text-emerald-100" x-text="'Cycle ' + d.plan.new_cycle + ' will be booked'"></p>
          <dl class="grid grid-cols-2 gap-x-4 gap-y-1 tabular-nums">
            <dt class="text-gray-600 dark:text-gray-300">Starts</dt><dd x-text="fmt(d.plan.start_date)"></dd>
            <dt class="text-gray-600 dark:text-gray-300">Due</dt><dd x-text="fmt(d.plan.due_date)"></dd>
            <dt class="text-gray-600 dark:text-gray-300">Carried (owed that day)</dt><dd x-text="'KES ' + money(d.plan.principal)"></dd>
            <dt class="text-gray-600 dark:text-gray-300">of which penalty</dt><dd x-text="'KES ' + money(d.plan.penalty)"></dd>
            <dt class="text-gray-600 dark:text-gray-300" x-text="'Interest (' + d.plan.interest_rate + '%)'"></dt><dd x-text="'KES ' + money(d.plan.interest)"></dd>
            <dt class="font-semibold text-gray-800 dark:text-white">To repay by the due date</dt><dd class="font-semibold" x-text="'KES ' + money(d.plan.new_balance)"></dd>
          </dl>
        </div>
      </template>

      <label class="block text-gray-700 dark:text-gray-300">Note for the borrower (optional)
        <input type="text" name="note" maxlength="500" class="mt-1 block h-10 w-full rounded-lg border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900"></label>

      <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
        <button type="button" @click="close()" class="rounded-lg border border-gray-300 px-4 py-2 font-medium dark:border-gray-700">Cancel</button>
        <button type="submit" :disabled="! (d && d.ok) || loading" class="rounded-lg bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600 disabled:opacity-40">Approve and extend</button>
      </div>
    </form>
  </div>
</div>

<script>
  function extendModal(previewUrl, decideUrl, token) {
    return {
      open: false, id: null, d: null, loading: false, mode: 'due', date: '', rate: '', token, submitUrl: '',
      async load(id) { this.id = id; this.open = true; this.d = null; this.mode = 'due'; this.date = ''; this.rate = ''; this.submitUrl = decideUrl.replace('__ID__', id); await this.preview(); },
      close() { this.open = false; },
      async preview() {
        this.loading = true;
        const q = new URLSearchParams();
        if (this.mode === 'pick' && this.date) q.set('date', this.date);
        if (this.rate !== '' && this.rate !== null) q.set('interest_rate', this.rate);
        try {
          const r = await fetch(previewUrl.replace('__ID__', this.id) + '?' + q, {headers: {Accept: 'application/json'}});
          this.d = await r.json();
          if (this.mode === 'pick' && ! this.date && this.d.cycle) this.date = this.d.cycle.due <= this.d.today ? this.d.cycle.due : this.d.today;
        } catch (e) { this.d = {ok: false, message: 'Could not load the request.'}; } finally { this.loading = false; }
      },
      money(v) { return v === null || v === undefined || isNaN(v) ? '—' : Number(v).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
      fmt(s) { return s ? new Date(s + 'T00:00:00').toLocaleDateString('en-GB', {day: 'numeric', month: 'short', year: 'numeric'}) : '—'; },
    };
  }
</script>
