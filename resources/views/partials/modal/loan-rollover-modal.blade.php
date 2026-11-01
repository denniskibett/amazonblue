{{-- Extend (roll over) a loan (owner, 23 Oct 2026). A right-side sheet that reads the real plan from the server
     (loans.rollover.preview, the same rules the booking uses) and lets staff set the dates when needed:
       - Booked on: the day the extension is recorded (today, or an earlier day inside the cycle);
       - New due date: one product period on by default, or any later date.
     The rate is kept for cycles 2–4 unless staff change it; from cycle 5 a rate is required. Staff may also roll into
     another product (owner, 1 Nov 2026: e.g. 10 days → 5 days), which sets the period and rate. Open with window.openRolloverModal(loanId). --}}
<script>
  window.rolloverModal = window.rolloverModal || function () {
    return {
      open: false, loading: false, saving: false, loanId: null,
      p: {}, error: '', errors: [],
      form: { date: '', due_date: '', interest_rate: '', waive_penalty: false, notes: '', loan_type_id: '' },
      defaults: { due_date: '' },
      _pickBook: false,
      // Booked on: the last due date, today, or a date picked on the calendar
      bookChoices() {
        const due = this.p && this.p.current_due_date_raw, start = this.p && this.p.cycle_start_date_raw;
        const dueOk = due && due <= this.today && (!start || due >= start);
        return [
          { key: 'due', label: 'Last due date' + (due ? ' · ' + this.day(due) : ''), date: due, off: !dueOk,
            why: dueOk ? '' : 'The due date has not passed yet, so the extension can be booked today at the earliest.' },
          { key: 'today', label: 'Today · ' + this.day(this.today), date: this.today, off: false },
          { key: 'pick', label: 'Another date', date: null, off: false },
        ];
      },
      bookMode() {
        if (this._pickBook) return 'pick';
        if (this.p && this.form.date === this.p.current_due_date_raw) return 'due';
        return this.form.date === this.today ? 'today' : 'pick';
      },
      pickBook(c) {
        this._pickBook = c.key === 'pick';
        if (c.date) { this.form.date = c.date; this.changed(); return; }
        // open the calendar on the visible date field
        this.$nextTick(() => { const el = document.getElementById('ro-date'); const fp = el && el._azPicker; fp ? fp.open() : (el && el.focus()); });
      },
      today: new Date().toLocaleDateString('en-CA'),
      timer: null,
      init() {
        window.openRolloverModal = (loanId) => this.show(loanId);
        window.azOn('open-rollover-modal', (e) => this.show(e.detail && (e.detail.loanId || e.detail.loan_id)));
      },
      show(loanId) {
        if (!loanId) return;
        this.loanId = loanId; this.p = {}; this.error = ''; this.errors = [];
        this.form = { date: this.today, due_date: '', interest_rate: '', waive_penalty: false, notes: '', loan_type_id: '' };
        this._pickBook = false; this._pickDue = false;
        this.open = true;
        document.body.style.overflow = 'hidden';
        this.preview(true);
      },
      close() { this.open = false; document.body.style.overflow = ''; },
      changed() { clearTimeout(this.timer); this.timer = setTimeout(() => this.preview(false), 350); },
      async preview(first) {
        this.loading = true; this.error = '';
        const q = new URLSearchParams();
        if (this.form.date && this.form.date !== this.today) q.set('date', this.form.date);
        if (this.form.due_date && this.form.due_date !== this.defaults.due_date) q.set('due_date', this.form.due_date);
        if (this.form.interest_rate !== '' && this.form.interest_rate !== null) q.set('interest_rate', this.form.interest_rate);
        if (this.form.waive_penalty) q.set('waive_penalty', 1);
        if (this.productChanged()) q.set('loan_type_id', this.form.loan_type_id);
        try {
          const res = await fetch(`/loans/${this.loanId}/rollover-preview?${q}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
          const body = await res.json().catch(() => ({}));
          if (!res.ok || !body.success) {
            this.error = body.message || (body.errors ? Object.values(body.errors).flat().join(' ') : 'The preview could not be worked out.');
            return;
          }
          this.p = body.data;
          // the product-period due date for this booking day; a date staff typed stays as it is
          if (first || !this.form.due_date || this.form.due_date === this.defaults.due_date) {
            this.defaults.due_date = this.p.new_due_date_raw;
            this.form.due_date = this.p.new_due_date_raw;
          }
          if (this.form.interest_rate === '') this.form.interest_rate = this.p.interest_rate;
          if (!this.form.loan_type_id) this.form.loan_type_id = String(this.p.current_loan_type_id || '');
        } catch (e) {
          this.error = 'Could not reach the server. Nothing was saved.';
        } finally { this.loading = false; }
      },
      // quick picks (owner, 1 Nov 2026): product pills "5 days · 10%", and the new due date counted from the due date or today
      _pickDue: false,
      product() { return (this.p.products || []).find(t => String(t.id) === String(this.form.loan_type_id)) || null; },
      productPills() {
        const seen = {}, out = [];
        (this.p.products || []).forEach(t => {
          const label = t.period + ' · ' + t.interest_rate + '%';
          if (seen[label] && String(t.id) !== String(this.p.current_loan_type_id)) return;
          if (seen[label]) out.splice(out.indexOf(seen[label]), 1);
          seen[label] = { id: String(t.id), label, name: t.name };
          out.push(seen[label]);
        });
        return out;
      },
      choosePill(t) { if (String(this.form.loan_type_id) === t.id) return; this.form.loan_type_id = t.id; this._pickDue = false; this.pickProduct(); },
      dueChoices() {
        const t = this.product(), n = t ? t.n : this.p.period, u = t ? t.unit : this.p.period_unit;
        const fromDue = window.azAddPeriod(this.p.current_due_date_raw, n, u), fromToday = window.azAddPeriod(this.today, n, u);
        return [
          { key: 'due', label: 'From due date · ' + this.day(fromDue), date: fromDue, off: fromDue <= (this.p.new_start_date_raw || '') },
          { key: 'today', label: 'From today · ' + this.day(fromToday), date: fromToday, off: fromToday <= (this.p.new_start_date_raw || '') },
          { key: 'pick', label: 'Pick a date', date: null },
        ];
      },
      dueMode() {
        if (this._pickDue) return 'pick';
        const c = this.dueChoices().find(c => c.date && c.date === this.form.due_date);
        return c ? c.key : (this.form.due_date === this.defaults.due_date ? '' : 'pick');
      },
      pickDue(c) {
        this._pickDue = c.key === 'pick';
        if (c.date) { this.form.due_date = c.date; this.changed(); return; }
        this.$nextTick(() => { const el = document.getElementById('ro-due'); const fp = el && el._azPicker; fp ? fp.open() : (el && el.focus()); });
      },
      productChanged() { return !!this.form.loan_type_id && String(this.form.loan_type_id) !== String(this.p.current_loan_type_id || ''); },
      // a new product brings its own period and rate: the due date and rate follow it until staff type their own
      pickProduct() { this.form.due_date = ''; this.defaults.due_date = ''; this.form.interest_rate = ''; this.changed(); },
      needsRate() { return (this.p.new_cycle || 0) > (this.p.same_rate_cycles || 4); },
      async submit() {
        if (this.saving) return;
        this.saving = true; this.errors = [];
        const body = { interest_type: 'compound', notes: this.form.notes, waive_penalty: this.form.waive_penalty };
        if (this.form.date && this.form.date !== this.today) body.rollover_date = this.form.date;
        if (this.form.due_date && this.form.due_date !== this.defaults.due_date) body.due_date = this.form.due_date;
        if (this.needsRate() || (this.form.interest_rate !== '' && Number(this.form.interest_rate) !== Number(this.p.interest_rate))) body.interest_rate = this.form.interest_rate;
        if (this.productChanged()) body.loan_type_id = this.form.loan_type_id;
        try {
          const res = await fetch(`/loans/${this.loanId}/rollover`, { method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(body) });
          const data = await res.json().catch(() => ({}));
          if (!res.ok || data.success === false) {
            this.errors = data.errors ? Object.values(data.errors).flat() : [data.message || 'The loan could not be extended.'];
            return;
          }
          window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: data.message || 'Loan extended.' } }));
          this.close();
          setTimeout(() => (window.azRefresh ? window.azRefresh() : location.reload()), 250);
        } catch (e) {
          this.errors = ['Could not reach the server. Nothing was saved.'];
        } finally { this.saving = false; }
      },
      kes(n) { return 'KES ' + Number(n || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
      day(d) { return d ? new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; },
    };
  };
</script>

<div x-data="rolloverModal()" x-init="init()" x-cloak @keydown.escape.window="open && close()">
  <div x-show="open" x-transition.opacity class="az-backdrop fixed inset-0 z-[99999]" @click="close()"></div>

  <section x-show="open" class="az-sheet flex flex-col bg-white dark:bg-gray-900" role="dialog" aria-modal="true" aria-label="Extend loan">
    <header class="sticky top-0 z-10 flex items-start justify-between gap-3 border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Extend loan <span x-text="loanId ? '#' + String(loanId).padStart(5, '0') : ''"></span></h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Close this cycle and start the next one with what is owed.</p>
      </div>
      <button type="button" @click="close()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800" aria-label="Close">✕</button>
    </header>

    <form @submit.prevent="submit()" class="flex-1 space-y-5 px-6 py-5">
      <div x-show="error" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300" x-text="error"></div>
      <div x-show="loading && !p.new_cycle" class="rounded-2xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 dark:bg-white/[0.03]">Working out the extension…</div>

      <div x-show="p.new_cycle" class="space-y-5">
        {{-- where the loan stands on the booking day --}}
        <div class="grid grid-cols-2 gap-3 rounded-2xl bg-gray-50 p-4 text-sm dark:bg-white/[0.03] sm:grid-cols-4">
          <div><p class="text-xs text-gray-500">Cycle</p><p class="font-semibold text-gray-900 dark:text-white" x-text="p.current_cycle"></p></div>
          <div><p class="text-xs text-gray-500">Due</p><p class="font-semibold text-gray-900 dark:text-white" x-text="p.current_due_date"></p></div>
          <div><p class="text-xs text-gray-500">Days late</p><p class="font-semibold" :class="p.days_overdue > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white'" x-text="p.days_overdue || 0"></p></div>
          <div><p class="text-xs text-gray-500">Owed that day</p><p class="font-semibold text-gray-900 dark:text-white" x-text="kes(p.final_outstanding)"></p></div>
        </div>

        {{-- the dates --}}
        <div class="rounded-2xl border border-gray-100 p-4 dark:border-gray-800">
          <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">Product and dates</p>
          <div class="mb-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="ro-product">Product for the new cycle</label>
            <div class="mb-2 flex flex-wrap gap-2" role="group" aria-label="Product">
              <template x-for="t in productPills()" :key="t.id">
                <button type="button" @click="choosePill(t)" :title="t.name"
                        :class="String(form.loan_type_id) === t.id ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5'"
                        class="rounded-full border px-3 py-1.5 text-xs font-medium" x-text="t.label"></button>
              </template>
            </div>
            <select id="ro-product" x-model="form.loan_type_id" @change="pickProduct()"
                    class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
              <template x-for="t in (p.products || [])" :key="t.id">
                <option :value="String(t.id)" :selected="String(t.id) === String(form.loan_type_id)" x-text="t.name + ' · ' + t.period + ' · ' + t.interest_rate + '%' + (String(t.id) === String(p.current_loan_type_id) ? ' (current)' : '')"></option>
              </template>
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="productChanged() ? 'The new cycle and the loan move to this product: its period, rate and penalty apply. You can still set your own due date or rate below.' : 'Same product as now. Pick another to shorten or lengthen the cycle (e.g. 10 days → 5 days).'"></p>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="ro-date">Booked on</label>
              {{-- the three usual choices (owner, 25 Oct 2026): the last due date, today, or a day picked on the calendar --}}
              <div class="mb-2 flex flex-wrap gap-2" role="group" aria-label="Booked on">
                <template x-for="c in bookChoices()" :key="c.key">
                  <button type="button" @click="pickBook(c)" :disabled="!!c.off" :title="c.why || ''"
                          :class="bookMode() === c.key ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5'"
                          class="rounded-full border px-3 py-1.5 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40">
                    <span x-text="c.label"></span>
                  </button>
                </template>
              </div>
              <input id="ro-date" type="date" x-model="form.date" @change="changed()" :min="p.cycle_start_date_raw" :max="today"
                     class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">What is owed is worked out to this day, and the new cycle starts from it.</p>
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="ro-due">New due date</label>
              <div class="mb-2 flex flex-wrap gap-2" role="group" aria-label="New due date">
                <template x-for="c in dueChoices()" :key="c.key">
                  <button type="button" @click="pickDue(c)" :disabled="!!c.off" :title="c.off ? 'Falls on or before the new cycle start' : ''"
                          :class="dueMode() === c.key ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5'"
                          class="rounded-full border px-3 py-1.5 text-xs font-medium disabled:cursor-not-allowed disabled:opacity-40" x-text="c.label"></button>
                </template>
              </div>
              <input id="ro-due" type="date" x-model="form.due_date" @change="changed()" :min="p.new_start_date_raw"
                     class="h-11 w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span x-text="form.due_date === defaults.due_date ? 'One product period (' + (p.period_display || '') + ') from the start.' : 'Custom date.'"></span>
                <button type="button" x-show="form.due_date !== defaults.due_date" @click="form.due_date = defaults.due_date; changed()" class="font-medium text-brand-600 hover:underline">Use the product period</button>
              </p>
            </div>
          </div>
          <p class="mt-3 rounded-xl bg-brand-50 px-3 py-2 text-xs text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
            The new cycle runs <b x-text="day(p.new_start_date_raw)"></b> to <b x-text="day(p.new_due_date_raw)"></b>.
            <span x-text="p.days_overdue > 0 ? 'It starts on the booking day because the cycle is late.' : 'It starts on the current due date.'"></span>
          </p>
        </div>

        {{-- rate and penalty --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="ro-rate">Interest rate (%)</label>
            <input id="ro-rate" type="number" step="0.01" min="0" max="100" x-model="form.interest_rate" @input="changed()"
                   class="bg-gray-50/70 h-11 w-full rounded-xl border border-gray-200 px-4 text-sm focus:border-brand-300 focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="needsRate() ? 'Cycle ' + p.new_cycle + ': set the rate for this cycle (or use a payment plan).' : (productChanged() ? 'The product\'s rate; change it if agreed otherwise.' : 'The previous rate; change it if agreed otherwise.')"></p>
          </div>
          <label class="flex items-start gap-3 rounded-2xl border border-amber-100 bg-amber-50/60 p-4 text-sm dark:border-amber-500/20 dark:bg-amber-500/5">
            <input type="checkbox" x-model="form.waive_penalty" @change="changed()" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600">
            <span><span class="font-medium text-gray-800 dark:text-white">Waive penalty on the new cycle</span>
              <span class="block text-xs text-gray-500 dark:text-gray-400">No late charges until the new due date.</span></span>
          </label>
        </div>

        {{-- the result --}}
        <div class="rounded-2xl border border-gray-100 p-4 dark:border-gray-800">
          <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-white">New cycle <span x-text="p.new_cycle"></span> <span x-show="loading" class="ml-1 text-xs font-normal text-gray-400">updating…</span></p>
          <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.03]"><dt class="text-xs text-gray-500">Carried over</dt><dd class="font-semibold text-gray-900 dark:text-white" x-text="kes(p.new_principal)"></dd></div>
            <div class="rounded-xl bg-gray-50 p-3 dark:bg-white/[0.03]"><dt class="text-xs text-gray-500" x-text="'Interest (' + p.interest_rate + '%)'"></dt><dd class="font-semibold text-brand-600" x-text="kes(p.interest_to_capitalize)"></dd></div>
            <div class="rounded-xl bg-success-50 p-3 dark:bg-success-500/10"><dt class="text-xs text-gray-500">New balance</dt><dd class="font-semibold text-success-700 dark:text-success-400" x-text="kes(p.new_balance)"></dd></div>
          </dl>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300" for="ro-notes">Notes <span class="font-normal text-gray-400">(optional)</span></label>
          <textarea id="ro-notes" rows="2" x-model="form.notes" class="w-full rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-2.5 text-sm focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white"></textarea>
        </div>
      </div>

      <div x-show="errors.length" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">
        <template x-for="e in errors" :key="e"><p x-text="e"></p></template>
      </div>

      <div class="sticky bottom-0 -mx-6 flex justify-end gap-3 border-t border-gray-100 bg-white/95 px-6 py-4 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
        <button type="button" @click="close()" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</button>
        <button type="submit" :disabled="saving || loading || !p.new_cycle || !!error" class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:opacity-50" x-text="saving ? 'Extending…' : 'Extend loan'"></button>
      </div>
    </form>
  </section>
</div>
