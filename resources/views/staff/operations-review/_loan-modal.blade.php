{{-- Loan detail modal (22 Oct 2026 repairs plan): the issue, the loan, its cycles, its money rows, and the fields to change.
     Open it from any row with: $dispatch('open-loan', {id: <loan id>}) --}}
@php $canFix = auth()->user()->canAny(['edit loans', 'manage loan cycles']); @endphp
<style>[x-cloak]{display:none!important}</style>
<div x-data="loanModal(@js(route('staff.operations-review.detail', ['loan' => '__LOAN__'])), @js(route('staff.operations-review.repair', ['loan' => '__LOAN__'])), @js(csrf_token()), @js($canFix))"
     @open-loan.window="load($event.detail.id)" @keydown.escape.window="close()" x-cloak>
  <div x-show="id" class="fixed inset-0 z-99999 flex items-start justify-center overflow-y-auto bg-gray-900/50 p-3 backdrop-blur-sm sm:p-6" @click.self="close()">
    <div class="relative w-full max-w-6xl rounded-2xl bg-white shadow-xl dark:bg-gray-900" role="dialog" aria-modal="true" :aria-label="'Loan #' + id">

      {{-- header --}}
      <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <div class="min-w-0">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90"><span x-text="d ? d.loan.borrower : 'Loading…'"></span> <span class="text-gray-400" x-text="'· loan #' + id"></span></h3>
          <p class="mt-0.5 text-theme-xs text-gray-500" x-show="d" x-text="d && [d.loan.product, d.loan.status, d.loan.broker ? 'broker ' + d.loan.broker : null].filter(Boolean).join(' · ')"></p>
          <p class="mt-1 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-theme-xs text-gray-700 dark:bg-white/10 dark:text-gray-300" x-show="d && d.recent">Started in the last 3 months: its rollovers are left as the system made them</p>
        </div>
        <div class="flex items-center gap-3">
          <a :href="'/loans/' + id" target="_blank" class="text-theme-xs font-medium text-brand-600 hover:underline">Open loan page ↗</a>
          <button type="button" @click="close()" class="rounded-full p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/10" aria-label="Close">✕</button>
        </div>
      </div>

      <div x-show="loading" class="p-6 text-sm text-gray-500">Loading the loan, its cycles and the plan…</div>
      <div x-show="error" class="m-5 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300" x-text="error"></div>

      <template x-if="d && ! loading">
        <div class="space-y-5 px-5 py-4 text-sm">

          {{-- 1. the issue --}}
          <section>
            <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">1 · The issue</h4>
            <div class="grid gap-3 lg:grid-cols-3">
              <div class="rounded-xl border p-3 lg:col-span-2" :class="toneBox(d.plan && d.plan.tone)">
                <template x-if="d.plan">
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="rounded-full px-2 py-0.5 text-theme-xs font-semibold" :class="toneChip(d.plan.tone)" x-text="d.plan.group_label"></span>
                      <span class="text-theme-xs text-gray-500" x-text="'Repairs plan, ' + d.plan.plan_date"></span>
                    </div>
                    <p class="mt-2 font-medium text-gray-800 dark:text-white/90" x-text="d.plan.issue"></p>
                    <p class="mt-1 text-gray-700 dark:text-gray-300"><span class="font-semibold">What to do:</span> <span x-text="d.plan.todo"></span></p>
                    <p class="mt-1 text-theme-xs text-gray-500" x-text="d.plan.group_meaning"></p>
                    <p class="mt-2 text-theme-xs text-gray-600 dark:text-gray-400" x-show="d.plan.live_before !== undefined">
                      Live before: <strong class="tabular-nums" x-text="money(d.plan.live_before)"></strong> · rehearsal result: <strong class="tabular-nums" x-text="money(d.plan.rehearsal)"></strong>
                    </p>
                  </div>
                </template>
                <template x-if="! d.plan"><p class="text-gray-600 dark:text-gray-300">Not in the 22 Oct repairs plan. Read the finding and the cycles below; decide with the owner.</p></template>
              </div>
              <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                <p class="text-theme-xs text-gray-500" x-text="d.finding ? 'System finding: ' + d.finding.group_label : 'The ledger matches the cycles'"></p>
                <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 tabular-nums">
                  <dt class="text-gray-500">Owed (cycles)</dt><dd class="text-right" x-text="money(d.finding ? d.finding.owed : d.owed_now)"></dd>
                  <dt class="text-gray-500">Ledger</dt><dd class="text-right" x-text="money(d.finding && d.finding.ledger)"></dd>
                  <dt class="text-gray-500">Difference</dt><dd class="text-right font-semibold" :class="d.finding && Math.abs(d.finding.difference) >= 0.01 ? 'text-red-600' : 'text-emerald-600'" x-text="money(d.finding ? d.finding.difference : 0)"></dd>
                  <dt class="text-gray-500">Cash sent</dt><dd class="text-right" x-text="money(d.finding && d.finding.cash_sent)"></dd>
                  <dt class="text-gray-500">Cycle 1 lent</dt><dd class="text-right" x-text="money(d.finding && d.finding.first_principal)"></dd>
                  <dt class="text-gray-500">Cash repaid</dt><dd class="text-right" x-text="money(d.finding && d.finding.cash_repaid)"></dd>
                </dl>
                <p class="mt-2 text-theme-xs text-amber-700 dark:text-amber-300" x-show="d.agreed">Owner-agreed terms (forbearance, waiver or payment plan): cycles are not edited here.</p>
              </div>
            </div>
          </section>

          {{-- 2. the loan --}}
          <section>
            <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">2 · The loan</h4>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-1 rounded-xl border border-gray-200 p-3 tabular-nums dark:border-gray-800 sm:grid-cols-4">
              <template x-for="f in loanFields()" :key="f[0]"><div class="flex justify-between gap-2"><dt class="text-gray-500" x-text="f[0]"></dt><dd class="text-right text-gray-800 dark:text-white/90" x-text="f[1]"></dd></div></template>
            </dl>
          </section>

          {{-- 2b. the borrower's other loans: differences only need to cancel across the borrower --}}
          <section x-show="d.borrower_loans.length > 1">
            <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">The borrower's loans <span class="normal-case text-gray-400">(moves only shift a difference between these; what is left in the net needs a real fix)</span></h4>
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
              <table class="min-w-full text-theme-xs tabular-nums">
                <thead class="bg-gray-50 text-gray-500 dark:bg-white/[0.02]"><tr><th class="px-2 py-2 text-left">Loan</th><th class="px-2 py-2 text-left">Borrowed</th><th class="px-2 py-2 text-left">Status</th><th class="px-2 py-2 text-left">Problem</th><th class="px-2 py-2 text-right">Cash sent</th><th class="px-2 py-2 text-right">Cycle 1 lent</th><th class="px-2 py-2 text-right">Difference</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <template x-for="l in d.borrower_loans.filter(x => Math.abs(x.difference) >= 0.01 || x.id === d.loan.id)" :key="l.id">
                    <tr :class="l.id === d.loan.id ? 'bg-brand-50/50 font-medium dark:bg-brand-500/10' : ''">
                      <td class="px-2 py-1.5"><button type="button" class="text-brand-600 hover:underline" @click="load(l.id)" x-text="'#' + l.id"></button><span x-show="l.recent" class="ml-1 text-gray-400">(recent)</span></td>
                      <td class="px-2 py-1.5" x-text="l.borrow_date || '—'"></td><td class="px-2 py-1.5" x-text="l.status"></td><td class="px-2 py-1.5" x-text="l.group || '—'"></td>
                      <td class="px-2 py-1.5 text-right" x-text="money(l.cash_sent)"></td><td class="px-2 py-1.5 text-right" x-text="money(l.first_principal)"></td>
                      <td class="px-2 py-1.5 text-right" :class="Math.abs(l.difference) >= 0.01 ? 'text-red-600' : ''" x-text="money(l.difference)"></td>
                    </tr>
                  </template>
                  <tr class="bg-gray-50 font-semibold dark:bg-white/[0.02]"><td class="px-2 py-1.5" colspan="6">Net for the borrower</td><td class="px-2 py-1.5 text-right" :class="Math.abs(d.borrower_net) >= 0.01 ? 'text-red-600' : 'text-emerald-600'" x-text="money(d.borrower_net)"></td></tr>
                </tbody>
              </table>
            </div>
          </section>

          {{-- 3. the cycles --}}
          <section>
            <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">3 · Loan cycles <span class="normal-case text-gray-400" x-show="previewCycles">(preview shown in green where it changes)</span></h4>
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
              <table class="min-w-full text-theme-xs tabular-nums">
                <thead class="bg-gray-50 text-gray-500 dark:bg-white/[0.02]"><tr>
                  <th class="px-2 py-2 text-left">#</th><th class="px-2 py-2 text-left">Start → due</th><th class="px-2 py-2 text-left">Status</th>
                  <th class="px-2 py-2 text-right">Principal</th><th class="px-2 py-2 text-right">Rate</th><th class="px-2 py-2 text-right">Interest</th>
                  <th class="px-2 py-2 text-right">Penalty</th><th class="px-2 py-2 text-right">Paid</th><th class="px-2 py-2 text-right">Owed / on close</th>
                  <th class="px-2 py-2 text-right">Next took</th><th class="px-2 py-2 text-left">Grace / waiver</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <template x-for="c in d.cycles" :key="c.id">
                    <tr class="text-gray-700 dark:text-gray-300">
                      <td class="px-2 py-1.5" x-text="c.n"></td>
                      <td class="whitespace-nowrap px-2 py-1.5" x-text="c.start + ' → ' + c.due + (c.closed_on ? ' (closed ' + c.closed_on + ')' : '')"></td>
                      <td class="px-2 py-1.5" x-text="c.status"></td>
                      <td class="px-2 py-1.5 text-right" x-html="cell(c, 'principal')"></td>
                      <td class="px-2 py-1.5 text-right" x-text="c.rate + '%'"></td>
                      <td class="px-2 py-1.5 text-right" x-html="cell(c, 'interest')"></td>
                      <td class="px-2 py-1.5 text-right" x-html="cell(c, 'penalty')"></td>
                      <td class="px-2 py-1.5 text-right" x-text="money(c.paid)"></td>
                      <td class="px-2 py-1.5 text-right font-medium" x-html="c.owed_on_close !== null ? cell(c, 'owed_on_close') : cell(c, 'owed')"></td>
                      <td class="px-2 py-1.5 text-right" :class="c.carry_difference && Math.abs(c.carry_difference) >= 1 ? 'text-amber-600' : ''" x-text="c.carried !== null ? money(c.carried) + (c.carry_difference && Math.abs(c.carry_difference) >= 1 ? ' (' + money(c.carry_difference) + ')' : '') : '—'"></td>
                      <td class="px-2 py-1.5" x-text="[c.grace_days ? c.grace_days + ' grace days' : null, c.waived_until ? 'waived to ' + c.waived_until : null].filter(Boolean).join(' · ') || '—'"></td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </section>

          {{-- 4. money rows --}}
          <section class="grid gap-4 lg:grid-cols-2">
            <div>
              <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">4a · Disbursements</h4>
              <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800"><table class="min-w-full text-theme-xs tabular-nums">
                <template x-for="r in d.disbursements" :key="r.id"><tr class="border-b border-gray-100 dark:border-gray-800" :class="r.deleted ? 'text-gray-400 line-through' : 'text-gray-700 dark:text-gray-300'">
                  <td class="px-2 py-1.5" x-text="'#' + r.id"></td><td class="px-2 py-1.5" x-text="r.date"></td><td class="px-2 py-1.5 text-right" x-text="money(r.amount)"></td>
                  <td class="px-2 py-1.5" x-text="r.kind"></td><td class="px-2 py-1.5" x-text="(r.transaction || '').slice(0, 28)"></td>
                  <td class="whitespace-nowrap px-2 py-1.5" x-show="canFix && ! r.deleted && d.borrower_loans.length > 1">
                    <span class="text-gray-500">to #</span><input type="number" min="1" x-model.number="form['dm' + r.id]" class="w-16 rounded border-gray-300 py-0.5 text-theme-xs dark:border-gray-700 dark:bg-gray-800" :aria-label="'Move disbursement ' + r.id + ' to loan'">
                    <button type="button" class="text-brand-600 hover:underline" @click="send({action: 'move_disbursement', row: r.id, to: form['dm' + r.id]}, true)">Preview</button>
                    <button type="button" class="text-brand-600 hover:underline" @click="send({action: 'move_disbursement', row: r.id, to: form['dm' + r.id]}, false)">Save</button>
                  </td></tr></template>
                <tr x-show="! d.disbursements.length"><td class="px-2 py-2 text-gray-500">none recorded</td></tr>
              </table></div>
            </div>
            <div>
              <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">4b · Repayments and book rows</h4>
              <div class="max-h-64 overflow-auto rounded-xl border border-gray-200 dark:border-gray-800"><table class="min-w-full text-theme-xs tabular-nums">
                <template x-for="r in d.repayments" :key="r.id"><tr class="border-b border-gray-100 dark:border-gray-800" :class="r.deleted ? 'text-gray-400 line-through' : (r.kind === 'cash' ? 'text-gray-700 dark:text-gray-300' : 'bg-amber-50/60 text-gray-800 dark:bg-amber-500/5 dark:text-gray-200')">
                  <td class="px-2 py-1.5" x-text="'#' + r.id"></td><td class="px-2 py-1.5" x-text="r.date"></td><td class="px-2 py-1.5 text-right" x-text="money(r.amount)"></td>
                  <td class="px-2 py-1.5" x-text="r.kind"></td><td class="px-2 py-1.5" x-text="(r.transaction || '').slice(0, 30)"></td><td class="px-2 py-1.5 text-gray-400" x-text="cycleNo(r.cycle_id)"></td></tr></template>
                <tr x-show="! d.repayments.length"><td class="px-2 py-2 text-gray-500">none</td></tr>
              </table></div>
              <p class="mt-1 text-theme-xs text-gray-500">Shaded rows are book entries (not cash); struck-through rows are deleted.</p>
            </div>
          </section>

          {{-- 5. what needs to be done --}}
          <section>
            <h4 class="mb-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500">5 · What needs to be done</h4>
            <div class="space-y-3 rounded-xl border border-gray-200 p-3 dark:border-gray-800">
              {{-- the one button: balance the loan (owner, 22 Oct 2026) --}}
              <template x-if="d.balance && d.balance.action !== 'error'">
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-500/30 dark:bg-blue-500/10">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-blue-900 dark:text-blue-100">Balance this loan</span>
                    <span class="rounded-full px-2 py-0.5 text-theme-xs font-semibold" :class="Math.abs(d.balance.difference_after || 0) < 0.01 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                          x-text="Math.abs(d.balance.difference_after || 0) < 0.01 ? 'balances to 0' : 'still off ' + money(d.balance.difference_after) + ': owner decides below'"></span>
                    <span class="ml-auto flex gap-2" x-show="canFix && ['balance', 'rebook'].includes(d.balance.action)">
                      <button type="button" @click="send({action: 'balance'}, true)" class="rounded-md border border-blue-300 bg-white px-2.5 py-1 text-theme-xs font-medium dark:bg-transparent">Preview</button>
                      <button type="button" @click="send({action: 'balance'}, false)" class="rounded-md bg-blue-600 px-2.5 py-1 text-theme-xs font-medium text-white">Balance</button>
                    </span>
                  </div>
                  <p class="mt-1 text-theme-xs text-blue-800 dark:text-blue-200">Every payment and every shilling sent goes to the cycle running on its date; each cycle carries what the one before still owed plus any new money; no penalty days except the ones the borrower's own cash paid for; a closed loan ends at exactly 0. Cash and cycle 1 stay as lent.</p>
                  <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-0.5 text-theme-xs tabular-nums sm:grid-cols-4">
                    <dt class="text-gray-500">Owed</dt><dd x-text="money(d.balance.owed_before) + ' → ' + money(d.balance.owed_after)"></dd>
                    <dt class="text-gray-500">Penalty</dt><dd x-text="money(d.balance.penalty_before) + ' → ' + money(d.balance.penalty_after)"></dd>
                    <dt class="text-gray-500">Ledger</dt><dd x-text="money(d.balance.ledger_before) + ' → ' + money(d.balance.ledger_after)"></dd>
                    <dt class="text-gray-500">Difference</dt><dd class="font-semibold" x-text="money(d.balance.difference_before) + ' → ' + money(d.balance.difference_after)"></dd>
                  </dl>
                  <ul class="mt-2 list-disc space-y-0.5 pl-5 text-theme-xs text-gray-700 dark:text-gray-300">
                    <template x-for="t in [].concat((d.balance.steps || '').split('; '), (d.balance.changes || '').split('; ')).filter(Boolean)" :key="t"><li x-text="t"></li></template>
                    <li x-show="d.balance.action === 'skip' || d.balance.action === 'nothing'" x-text="d.balance.reason || 'Nothing to change: it already balances.'"></li>
                  </ul>
                </div>
              </template>
              <template x-if="d.balance && d.balance.action === 'error'"><p class="rounded-lg bg-red-50 px-3 py-2 text-theme-xs text-red-700" x-text="'Could not work out the balance: ' + d.balance.reason"></p></template>

              {{-- what only the owner can decide --}}
              <template x-if="d.options && d.options.length">
                <div class="rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2 dark:border-amber-500/30 dark:bg-amber-500/5">
                  <p class="font-semibold text-amber-900 dark:text-amber-100">Owner decides</p>
                  <p class="text-theme-xs text-amber-800 dark:text-amber-200" x-text="d.balance && d.balance.flags ? d.balance.flags : ''"></p>
                  <template x-for="(o, k) in d.options" :key="k">
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 rounded-md px-2 py-1" :class="o.best ? 'ring-1 ring-amber-400' : ''">
                      <span class="min-w-0 flex-1 text-theme-xs"><span class="font-medium text-gray-800 dark:text-white/90" x-text="o.label"></span> <span class="text-gray-500" x-text="o.why"></span></span>
                      <span class="flex gap-2" x-show="canFix && o.action">
                        <button type="button" @click="send(o, true)" class="rounded-md border border-gray-300 bg-white px-2 py-1 text-theme-xs dark:border-gray-700 dark:bg-transparent">Preview</button>
                        <button type="button" @click="send(o, false)" class="rounded-md bg-amber-600 px-2 py-1 text-theme-xs text-white">Apply</button>
                      </span>
                    </div>
                  </template>
                </div>
              </template>

              <p class="rounded-lg bg-amber-50 px-3 py-2 text-theme-xs text-amber-800 dark:bg-amber-500/10 dark:text-amber-200" x-show="d.plan && d.plan.group !== 'A'">This loan is in group <strong x-text="d.plan && d.plan.group"></strong> of the plan: change it only once the owner has agreed the numbers.</p>

              <div x-show="canFix" class="grid gap-3 lg:grid-cols-2">
                {{-- book rows --}}
                <div>
                  <p class="mb-1 text-theme-xs font-medium text-gray-600 dark:text-gray-300">Book rows (not cash): set the amount</p>
                  <template x-for="e in d.edits.filter(x => x.type === 'book')" :key="'b' + e.id">
                    <div class="mb-1.5 flex flex-wrap items-center gap-2 rounded-md px-2 py-1" :class="e.highlight ? 'ring-1 ring-emerald-400' : ''">
                      <span class="min-w-0 flex-1 truncate text-theme-xs" x-text="e.label" :title="e.label"></span>
                      <input type="number" step="0.01" min="0" x-model.number="form['book' + e.id]" class="w-28 rounded-md border-gray-300 py-1 text-right text-theme-xs dark:border-gray-700 dark:bg-gray-800">
                      <button type="button" @click="send({action: 'book_set', row: e.id, amount: form['book' + e.id]}, true)" class="rounded-md border border-gray-300 px-2 py-1 text-theme-xs dark:border-gray-700">Preview</button>
                      <button type="button" @click="send({action: 'book_set', row: e.id, amount: form['book' + e.id]}, false)" class="rounded-md bg-gray-800 px-2 py-1 text-theme-xs text-white dark:bg-white/20">Save</button>
                    </div>
                  </template>
                  <p class="text-theme-xs text-gray-500" x-show="! d.edits.some(x => x.type === 'book')">No book rows on this loan. Cash rows are never edited here.</p>
                </div>
                {{-- cycles --}}
                <div>
                  <p class="mb-1 text-theme-xs font-medium text-gray-600 dark:text-gray-300">Cycles: set principal / rate (only that cycle changes, nothing is re-carried)</p>
                  <template x-for="e in d.edits.filter(x => x.type === 'cycle')" :key="'c' + e.id">
                    <div class="mb-1.5 flex flex-wrap items-center gap-2 px-2 py-1">
                      <span class="min-w-0 flex-1 truncate text-theme-xs" x-text="e.label"></span>
                      <input type="number" step="0.01" min="0.01" x-model.number="form['cp' + e.id]" class="w-28 rounded-md border-gray-300 py-1 text-right text-theme-xs dark:border-gray-700 dark:bg-gray-800" :aria-label="e.label + ' principal'">
                      <input type="number" step="0.01" min="0" max="100" x-model.number="form['cr' + e.id]" class="w-16 rounded-md border-gray-300 py-1 text-right text-theme-xs dark:border-gray-700 dark:bg-gray-800" :aria-label="e.label + ' rate %'"><span class="text-theme-xs">%</span>
                      <button type="button" @click="send({action: 'cycle_set', cycle: e.id, principal: form['cp' + e.id], rate: form['cr' + e.id]}, true)" class="rounded-md border border-gray-300 px-2 py-1 text-theme-xs dark:border-gray-700">Preview</button>
                      <button type="button" @click="send({action: 'cycle_set', cycle: e.id, principal: form['cp' + e.id], rate: form['cr' + e.id]}, false)" class="rounded-md bg-gray-800 px-2 py-1 text-theme-xs text-white dark:bg-white/20">Save</button>
                    </div>
                  </template>
                  <p class="text-theme-xs text-gray-500" x-show="d.agreed">Agreed terms: change the cycle from the loan page instead.</p>
                </div>
                {{-- disbursement and move --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-2 dark:border-gray-800">
                  <span class="text-theme-xs font-medium text-gray-600 dark:text-gray-300">Record a missing disbursement</span>
                  <input type="number" step="0.01" min="0.01" x-model.number="form.disbAmount" placeholder="KES" class="w-28 rounded-md border-gray-300 py-1 text-right text-theme-xs dark:border-gray-700 dark:bg-gray-800" aria-label="Disbursement amount">
                  <input type="date" x-model="form.disbDate" class="rounded-md border-gray-300 py-1 text-theme-xs dark:border-gray-700 dark:bg-gray-800" aria-label="Disbursement date">
                  <button type="button" @click="send({action: 'add_disbursement', amount: form.disbAmount, date: form.disbDate}, true)" class="rounded-md border border-gray-300 px-2 py-1 text-theme-xs dark:border-gray-700">Preview</button>
                  <button type="button" @click="send({action: 'add_disbursement', amount: form.disbAmount, date: form.disbDate}, false)" class="rounded-md bg-gray-800 px-2 py-1 text-theme-xs text-white dark:bg-white/20">Save</button>
                </div>
                <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-2 dark:border-gray-800">
                  <span class="text-theme-xs font-medium text-gray-600 dark:text-gray-300">Move receivable to loan #</span>
                  <input type="number" min="1" x-model.number="form.moveTo" class="w-20 rounded-md border-gray-300 py-1 text-theme-xs dark:border-gray-700 dark:bg-gray-800" aria-label="Move to loan">
                  <input type="number" step="0.01" min="0.01" x-model.number="form.moveAmount" placeholder="KES" class="w-28 rounded-md border-gray-300 py-1 text-right text-theme-xs dark:border-gray-700 dark:bg-gray-800" aria-label="Move amount">
                  <button type="button" @click="send({action: 'move', to: form.moveTo, amount: form.moveAmount}, true)" class="rounded-md border border-gray-300 px-2 py-1 text-theme-xs dark:border-gray-700">Preview</button>
                  <button type="button" @click="send({action: 'move', to: form.moveTo, amount: form.moveAmount}, false)" class="rounded-md bg-gray-800 px-2 py-1 text-theme-xs text-white dark:bg-white/20">Save</button>
                </div>
              </div>
              <label class="block text-theme-xs text-gray-600 dark:text-gray-300" x-show="canFix">Reason (saved in the audit log)
                <input type="text" maxlength="500" x-model="form.note" placeholder="e.g. 22 Oct plan, group A, agreed with owner" class="mt-1 block w-full rounded-md border-gray-300 py-1 text-theme-xs dark:border-gray-700 dark:bg-gray-800">
              </label>
              <p x-show="! canFix" class="text-theme-xs text-gray-500">You can read this loan; changing it needs the edit loans or manage loan cycles permission.</p>

              <div x-show="result" class="rounded-lg px-3 py-2 text-theme-xs" :class="result && result.ok ? (result.preview ? 'bg-blue-50 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200' : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200') : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300'">
                <span x-text="result && result.message"></span>
                <span x-show="result && result.preview && result.difference_after !== null && result.difference_after !== undefined" x-text="' Ledger difference after: ' + money(result && result.difference_after) + '.'"></span>
                <span x-show="result && result.other_after" x-text="result && result.other_after ? ' Loan #' + result.other_after.loan + ' difference after: ' + money(result.other_after.difference) + '.' : ''"></span>
              </div>
            </div>
          </section>
        </div>
      </template>
    </div>
  </div>
</div>

<script>
  function loanModal(detailUrl, repairUrl, token, canFix) {
    return {
      id: null, d: null, loading: false, error: null, result: null, previewCycles: null, canFix, form: {},
      async load(id) {
        this.id = id; this.d = null; this.error = null; this.result = null; this.previewCycles = null; this.loading = true; this.form = {};
        try {
          const r = await fetch(detailUrl.replace('__LOAN__', id), {headers: {Accept: 'application/json'}});
          if (! r.ok) throw new Error('Could not load loan #' + id + ' (' + r.status + ')');
          this.d = await r.json();
          this.d.edits.forEach(e => {
            if (e.type === 'book') this.form['book' + e.id] = e.suggested;
            if (e.type === 'cycle') { this.form['cp' + e.id] = e.principal; this.form['cr' + e.id] = e.rate; }
          });
          const s = this.d.suggestion;
          if (s && s.action === 'move') { this.form.moveTo = s.to; this.form.moveAmount = s.amount; }
          if (this.d.finding && this.d.finding.first_principal > this.d.finding.cash_sent) {
            this.form.disbAmount = Math.round((this.d.finding.first_principal - this.d.finding.cash_sent) * 100) / 100;
            this.form.disbDate = this.d.cycles.length ? this.d.cycles[0].start : '';
          }
        } catch (e) { this.error = e.message; } finally { this.loading = false; }
      },
      close() { this.id = null; this.d = null; },
      async send(p, preview) {
        if (! preview && ! confirm('Save this change to loan #' + this.id + '? It is audited and re-books the ledger, wallet and split.')) return;
        const body = new FormData();
        body.append('_token', token);
        Object.entries(Object.assign({}, p, {note: this.form.note || ''})).forEach(([k, v]) => { if (v !== undefined && v !== null && v !== '' && k !== 'loan' && k !== 'label' && k !== 'why' && k !== 'best') body.append(k, v); });
        if (preview) body.append('preview', 1);
        this.result = null;
        try {
          const r = await fetch(repairUrl.replace('__LOAN__', p.loan || this.id), {method: 'POST', body, headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
          const j = await r.json().catch(() => ({ok: false, message: 'The server answered ' + r.status}));
          if (r.status === 422 && j.errors) j.message = Object.values(j.errors).flat().join(' ');
          this.result = Object.assign({ok: r.ok}, j);
          this.previewCycles = preview && j.cycles ? j.cycles : null;
          if (r.ok && ! preview) { const keep = this.result; await this.load(this.id); this.result = keep; }
        } catch (e) { this.result = {ok: false, message: e.message}; }
      },
      cell(c, key) {
        const v = this.money(c[key]);
        if (! this.previewCycles) return v;
        const p = this.previewCycles.find(x => x.id === c.id);
        if (! p || Math.abs((p[key] ?? 0) - (c[key] ?? 0)) < 0.005) return v;
        return '<span class="text-gray-400 line-through">' + v + '</span> <span class="font-semibold text-emerald-600">' + this.money(p[key]) + '</span>';
      },
      cycleNo(cid) { const c = this.d.cycles.find(x => x.id === cid); return c ? 'c' + c.n : ''; },
      loanFields() {
        const l = this.d.loan;
        return [['Status', l.status], ['Product', l.product || '—'], ['Amount', this.money(l.amount)], ['Original', this.money(l.original_amount)],
          ['Borrowed', l.borrow_date || '—'], ['Due', l.due_date || '—'], ['Forbearance to', l.forbearance_until || '—'], ['Owed now', this.money(this.d.owed_now)]];
      },
      money(v) { return v === null || v === undefined || isNaN(v) ? '—' : Number(v).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}); },
      toneBox(t) { return ({green: 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-500/30 dark:bg-emerald-500/5', amber: 'border-amber-200 bg-amber-50/50 dark:border-amber-500/30 dark:bg-amber-500/5', blue: 'border-blue-200 bg-blue-50/50 dark:border-blue-500/30 dark:bg-blue-500/5'})[t] || 'border-gray-200 dark:border-gray-800'; },
      toneChip(t) { return ({green: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200', amber: 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200', blue: 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-200'})[t] || 'bg-gray-100 text-gray-700'; },
    };
  }
</script>
