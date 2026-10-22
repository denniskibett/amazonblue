@php
  $groups = \App\Services\Loans\LoanRepair::GROUPS;
  $canFix = auth()->user()->canAny(['edit loans', 'manage loan cycles']);
  $rows = $repairs->map(fn ($r) => $r + ['group_label' => $groups[$r['group']] ?? $r['group']])->values();
  $counts = $rows->countBy('group');
@endphp
<div x-data="loanRepairs(@js($rows), @js($canFix), @js(route('staff.operations-review.repair', ['loan' => '__LOAN__'])), @js(csrf_token()))"
     class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">

  <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Loans whose books don't match their cycles</h3>
      <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
        Target: every loan's ledger receivable equals what its cycles say is owed. <strong class="text-gray-700 dark:text-gray-200" x-text="all.length"></strong> left
        (net KES <span class="tabular-nums" x-text="money(all.reduce((t, r) => t + r.difference, 0))"></span>).
        Each row has a suggested fix: <em>Preview</em> shows what the loan would owe and saves nothing; <em>Apply</em> saves it, audited.
      </p>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Showing <span x-text="from"></span> to <span x-text="to"></span> of <span x-text="filtered.length"></span> entries</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
      <label for="repairs-per-page" class="hidden text-sm text-gray-500 dark:text-gray-400 sm:inline">Show:</label>
      <select id="repairs-per-page" x-model.number="perPage" @change="page = 1" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        <template x-for="n in [10, 25, 50, 100]" :key="n"><option :value="n" x-text="n"></option></template>
      </select>
      <div class="relative min-w-[180px] flex-1">
        <input id="repairs-search" type="text" x-model.debounce.200ms="q" @input="page = 1" placeholder="Search borrower or loan #"
               class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
        <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>
      <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300"><input id="repairs-has-fix" type="checkbox" x-model="onlySuggested" @change="page = 1" class="rounded border-gray-300"> Has a suggested fix</label>
    </div>
  </div>

  <div class="mb-4 flex flex-wrap gap-2 text-sm">
    <button type="button" @click="group = ''; page = 1" :class="group === '' ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300'" class="rounded-full px-3 py-1">All <span class="opacity-70" x-text="all.length"></span></button>
    @foreach ($groups as $k => $v)
      @if (($counts[$k] ?? 0) > 0)
        <button type="button" @click="group = @js($k); page = 1" :class="group === @js($k) ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900' : 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300'" class="rounded-full px-3 py-1">{{ $v }} <span class="opacity-70">{{ $counts[$k] }}</span></button>
      @endif
    @endforeach
  </div>

    <div x-show="canFix && ticked.length" x-cloak class="mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
    <span><strong x-text="ticked.length"></strong> loan(s) ticked. Each one is balanced on the server (payments to their cycles, no penalty days, closed loans end at 0).</span>
    <button type="button" @click="sendBatch()" class="rounded-md bg-emerald-600 px-3 py-1.5 text-theme-xs font-medium text-white hover:bg-emerald-700">Balance ticked loans</button>
    <button type="button" @click="ticked = []" class="text-theme-xs underline">Clear</button>
  </div>
  <div class="w-full overflow-x-auto">
    <table class="min-w-full text-left">
      <thead>
        <tr class="border-y border-gray-100 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
          <th class="px-2 py-3" x-show="canFix"><input id="repairs-tick-page" type="checkbox" class="rounded border-gray-300" title="Tick every row on this page that has a suggested fix"
              @change="const ids = pageRows.map(r => r.loan_id); ticked = $event.target.checked ? [...new Set([...ticked, ...ids])] : ticked.filter(i => ! ids.includes(i))"></th>
          <template x-for="c in columns" :key="c.key">
            <th class="cursor-pointer whitespace-nowrap px-2 py-3" :class="c.num ? 'text-right' : ''" @click="sortBy(c.key)">
              <span x-text="c.label"></span><span class="ml-1 text-gray-400" x-text="sort === c.key ? (dir > 0 ? '▲' : '▼') : '↕'"></span>
            </th>
          </template>
          <th class="px-2 py-3">Suggested fix</th>
          <th class="px-2 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        <template x-for="r in pageRows" :key="r.loan_id">
          <tr class="align-top text-theme-sm text-gray-700 dark:text-gray-300" :id="'loan-' + r.loan_id">
            <td class="px-2 py-3" x-show="canFix"> <template x-if="true"><input type="checkbox" class="rounded border-gray-300" :value="r.loan_id" x-model.number="ticked" :aria-label="'Tick loan ' + r.loan_id"></template></td>
            <td class="px-2 py-3"><a :href="'/loans/' + r.loan_id" class="font-medium text-brand-600 hover:underline" x-text="r.borrower"></a><div class="text-theme-xs text-gray-500" x-text="'Loan #' + r.loan_id"></div></td>
            <td class="px-2 py-3"><span class="rounded-full bg-amber-50 px-2 py-0.5 text-theme-xs text-amber-700 dark:bg-amber-500/15 dark:text-amber-300" x-text="r.group_label"></span></td>
            <td class="px-2 py-3"><span class="rounded-full px-2 py-0.5 text-theme-xs font-medium" :class="statusClass(r.status)" x-text="r.status"></span></td>
            <td class="whitespace-nowrap px-2 py-3 text-right tabular-nums" x-text="money(r.owed)"></td>
            <td class="whitespace-nowrap px-2 py-3 text-right tabular-nums" x-text="money(r.ledger)"></td>
            <td class="whitespace-nowrap px-2 py-3 text-right font-semibold tabular-nums" :class="r.difference > 0 ? 'text-amber-600' : 'text-blue-600'" x-text="money(r.difference)"></td>
            <td class="min-w-[260px] px-2 py-3">
              <template x-if="r.suggestion">
                <div>
                  <div class="font-medium text-gray-800 dark:text-white/90" x-text="r.suggestion.label"></div>
                  <div class="mt-0.5 text-theme-xs text-gray-500" x-text="r.suggestion.why"></div>
                  <div class="mt-2 flex gap-2" x-show="canFix">
                    <button type="button" @click="send(r.suggestion, true)" class="rounded-md border border-gray-300 px-2.5 py-1 text-theme-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-white/5">Preview</button>
                    <button type="button" @click="send(r.suggestion, false)" class="rounded-md bg-emerald-600 px-2.5 py-1 text-theme-xs font-medium text-white hover:bg-emerald-700">Apply</button>
                  </div>
                </div>
              </template>
              <template x-if="! r.suggestion"><span class="text-theme-xs text-gray-500">No safe guess: open the details and choose.</span></template>
            </td>
            <td class="px-2 py-3"><button type="button" @click="$dispatch('open-loan', {id: r.loan_id})" class="block whitespace-nowrap rounded-md bg-brand-600 px-2.5 py-1 text-theme-xs font-semibold text-white hover:bg-brand-700">Details &amp; fix</button><button type="button" @click="open = open === r.loan_id ? null : r.loan_id" class="mt-1 text-theme-xs font-medium text-brand-600 hover:underline" x-text="open === r.loan_id ? 'Hide' : 'Quick options'"></button></td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>

  {{-- details of the opened loan --}}
  <template x-if="openRow">
    <div class="mt-4 rounded-xl border border-gray-200 p-4 text-theme-xs dark:border-gray-800">
      <div class="mb-3 flex flex-wrap items-baseline gap-3">
        <span class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="openRow.borrower + ' · loan #' + openRow.loan_id"></span>
        <span class="text-gray-500" x-text="'cash sent ' + money(openRow.cash_sent) + ' · cycle 1 lent ' + money(openRow.first_principal) + ' · cash repaid ' + money(openRow.cash_repaid)"></span>
      </div>
      <div class="grid gap-4 lg:grid-cols-3">
        <div class="min-w-0"><p class="mb-1 font-medium text-gray-600 dark:text-gray-300">Cycles</p>
          <div class="overflow-x-auto"><table class="min-w-full tabular-nums"><template x-for="c in openRow.cycles" :key="c.id"><tr><td class="pr-2" x-text="'c' + c.n"></td><td class="pr-2" x-text="c.start + ' → ' + c.due"></td><td class="pr-2 text-right" x-text="money(c.principal)"></td><td class="pr-2" x-text="c.rate + '%'"></td><td class="text-gray-500" x-text="c.status"></td></tr></template></table></div></div>
        <div class="min-w-0"><p class="mb-1 font-medium text-gray-600 dark:text-gray-300">Disbursements</p>
          <div class="overflow-x-auto"><table class="min-w-full tabular-nums"><template x-for="d in openRow.disbursements" :key="d.id"><tr class="align-top"><td class="pr-2" x-text="d.date"></td><td class="pr-2 text-right" x-text="money(d.amount)"></td><td class="pr-2" x-text="d.kind"></td><td class="pr-2 text-gray-500" x-text="(d.transaction || '').slice(0, 18)"></td>
            <td><button type="button" x-show="canFix && d.kind === 'cash' && openRow.group === 'cash'" @click="choose({action: 'remove_disbursement', row: d.id}, 'Remove disbursement #' + d.id + ' as recorded by mistake')" class="text-brand-600 hover:underline">Recorded by mistake</button></td></tr></template></table></div></div>
        <div class="min-w-0"><p class="mb-1 font-medium text-gray-600 dark:text-gray-300">Book entries (not cash)</p>
          <div class="overflow-x-auto"><table class="min-w-full tabular-nums"><template x-for="b in openRow.book" :key="b.id"><tr class="align-top"><td class="pr-2" x-text="b.date"></td><td class="pr-2 text-right" x-text="money(b.amount)"></td><td class="pr-2" x-text="b.kind"></td>
            <td class="flex flex-wrap gap-x-2" x-show="canFix">
              <template x-for="o in bookOptions(b)" :key="o.action"><button type="button" @click="choose({action: o.action, row: b.id}, o.label + ' (' + money(b.amount) + ')')" class="text-brand-600 hover:underline" x-text="o.label"></button></template>
            </td></tr></template>
            <tr x-show="openRow.book.length === 0"><td class="text-gray-500">none</td></tr></table></div></div>
      </div>
      <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-3 dark:border-gray-800" x-show="canFix">
        <button type="button" x-show="@js((bool) config('repairs.allow_add_extra', false)) && openRow.cash_sent > 0 && Math.abs(openRow.cash_sent - openRow.first_principal) >= 1" @click="choose({action: 'rebuild'}, openRow.cash_sent > openRow.first_principal ? 'Add the extra cash to the loan' : 'Lend only what was sent')" class="rounded-md border border-gray-300 px-2.5 py-1 font-medium dark:border-gray-700" x-text="openRow.cash_sent > openRow.first_principal ? 'Add the extra cash to the loan' : 'Lend only what was sent'"></button>
        <button type="button" x-show="openRow.first_principal - openRow.cash_sent >= 0.01" @click="choose({action: 'add_disbursement'}, 'Record the missing disbursement')" class="rounded-md border border-gray-300 px-2.5 py-1 font-medium dark:border-gray-700">Record the missing disbursement</button>
        <span class="ml-auto inline-flex flex-wrap items-center gap-1">
          <label for="repairs-move-to">Move receivable to loan #</label><input id="repairs-move-to" type="number" min="1" x-model.number="moveTo" class="w-20 rounded-md border-gray-300 py-1 text-theme-xs dark:border-gray-700 dark:bg-gray-900">
          <label for="repairs-move-amt">KES</label><input id="repairs-move-amt" type="number" step="0.01" min="0.01" x-model.number="moveAmount" class="w-28 rounded-md border-gray-300 py-1 text-theme-xs dark:border-gray-700 dark:bg-gray-900">
          <button type="button" @click="choose({action: 'move', to: moveTo, amount: moveAmount}, 'Move KES ' + money(moveAmount) + ' to #' + moveTo)" class="rounded-md border border-gray-300 px-2.5 py-1 font-medium dark:border-gray-700">Move</button>
        </span>
      </div>
      <div class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5" x-show="picked">
        <span class="font-medium text-gray-800 dark:text-white/90" x-text="picked && picked.label"></span>
        <button type="button" @click="send(picked.params, true)" class="rounded-md border border-gray-300 px-2.5 py-1 font-medium dark:border-gray-700">Preview</button>
        <button type="button" @click="send(picked.params, false)" class="rounded-md bg-emerald-600 px-2.5 py-1 font-medium text-white">Apply</button>
        <button type="button" @click="picked = null" class="text-gray-500 hover:underline">Cancel</button>
      </div>
    </div>
  </template>

  <div class="mt-4 flex items-center justify-between text-sm" x-show="pages > 1">
    <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page === 1" class="rounded-lg border border-gray-300 px-3 py-1.5 disabled:opacity-40 dark:border-gray-700">Previous</button>
    <span class="text-gray-500" x-text="'Page ' + page + ' of ' + pages"></span>
    <button type="button" @click="page = Math.min(pages, page + 1)" :disabled="page === pages" class="rounded-lg border border-gray-300 px-3 py-1.5 disabled:opacity-40 dark:border-gray-700">Next</button>
  </div>
</div>

<script>
  function loanRepairs(all, canFix, url, token) {
    return {
      all, canFix, ticked: [], q: '', group: '', onlySuggested: false, sort: 'difference', dir: -1, page: 1, perPage: 25, open: null, picked: null, moveTo: null, moveAmount: null,
      columns: [
        {key: 'borrower', label: 'Borrower'}, {key: 'group_label', label: 'Problem'}, {key: 'status', label: 'Status'},
        {key: 'owed', label: 'Owed (cycles)', num: true}, {key: 'ledger', label: 'Ledger', num: true}, {key: 'difference', label: 'Difference', num: true},
      ],
      get filtered() {
        const q = this.q.trim().toLowerCase();
        let rows = this.all.filter(r => (! this.group || r.group === this.group) && (! this.onlySuggested || r.suggestion)
          && (! q || (r.borrower || '').toLowerCase().includes(q) || String(r.loan_id) === q.replace('#', '')));
        const k = this.sort, d = this.dir, abs = k === 'difference';
        return rows.sort((a, b) => { let x = a[k], y = b[k]; if (abs) { x = Math.abs(x); y = Math.abs(y); } return (x > y ? 1 : x < y ? -1 : 0) * d; });
      },
      get pages() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)); },
      get pageRows() { return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage); },
      get from() { return this.filtered.length ? (this.page - 1) * this.perPage + 1 : 0; },
      get to() { return Math.min(this.page * this.perPage, this.filtered.length); },
      get openRow() { return this.all.find(r => r.loan_id === this.open) || null; },
      sortBy(k) { this.dir = this.sort === k ? -this.dir : -1; this.sort = k; },
      money(v) { return v === null || v === undefined || isNaN(v) ? '—' : Number(v).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
      statusClass(s) {
        return ({overdue: 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500', defaulted: 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
          repaid: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400', disbursed: 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
          forbearance: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'})[s] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
      },
      bookOptions(b) {
        const r = this.openRow, o = [];
        if (b.kind === 'non_cash') o.push({action: 'broker_kept', label: 'Broker kept it (AC cost)'}, {action: 'book_discount', label: 'AC discount'}, {action: 'book_remove', label: 'Still owed'});
        if (b.kind === 'rollover') o.push({action: 'book_bad_debt', label: 'Write-off'}, {action: 'book_remove', label: 'Reopen'});
        if ((r.owed || 0) < -1) o.push({action: 'book_cut', label: 'Cut to what was owed'});
        return o;
      },
      choose(params, label) { this.picked = {params: Object.assign({loan: this.open}, params), label}; },
      sendBatch() {
        if (! this.ticked.length) return;
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = @js(route('staff.operations-review.repair-batch'));
        const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
        add('_token', token);
        this.ticked.forEach(id => add('loan_ids[]', id));
        document.body.appendChild(f);
        f.submit();
      },
      send(p, preview) {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = url.replace('__LOAN__', p.loan);
        const add = (n, v) => { if (v === undefined || v === null || v === '') return; const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
        add('_token', token); add('action', p.action); add('row', p.row); add('to', p.to); add('amount', p.amount); if (preview) add('preview', 1);
        document.body.appendChild(f);
        f.submit();
      },
    };
  }
</script>
