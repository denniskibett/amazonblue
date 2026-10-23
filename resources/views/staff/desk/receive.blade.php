{{-- Teller · receive payment. Loan search -> the loan's own cycles -> amount / reference / mode, posted by AJAX
     to the existing repayments.store (RepaymentController@store, unchanged). Reversal = existing destroy + reason. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $defaultCycle = $cycles->firstWhere('status', 'active') ?? $cycles->last();
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Receive payment', 'sub' => 'Find the loan, pick the cycle the money is for, and record it.'])
  @include('staff.desk._ajax')

  {{-- search and its results in one card (owner, 22 Oct 2026) --}}
  <section class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <form method="GET" action="{{ route('staff.repayments.receive') }}" class="flex flex-col gap-3 px-4 pt-4 pb-4 sm:flex-row sm:items-end sm:px-6 sm:pt-5" role="search">
      <div class="min-w-0 flex-1">
        <label for="q" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Loan number, borrower name, phone, email or ID number</label>
        <div class="relative">
          <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><x-ui.icon name="search" class="size-4" /></span>
          <input id="q" name="q" value="{{ $q }}" class="{{ $input }} pl-11" placeholder="e.g. 545 or Amina" autocomplete="off" autofocus>
        </div>
      </div>
      <button class="h-11 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600">Search</button>
    </form>
    <div class="border-t border-gray-100 dark:border-gray-800">
      @if ($q === '')
        @unless ($loan)
          <x-staff.empty title="Search for a loan to start" message="Payments are recorded against one of the loan's own cycles." />
        @else
          <p class="px-4 py-3 text-theme-sm text-gray-500 dark:text-gray-400 sm:px-6">Recording a payment on loan #{{ $loan->id }}. Search again to pick another loan.</p>
        @endunless
      @elseif ($results->isEmpty())
        <x-staff.empty title="No open loan found" message="Only disbursed, overdue, defaulted, recovery or forbearance loans take payments." />
      @else
        <div class="px-4 pb-3 sm:px-6">
          <p class="py-3 text-theme-sm text-gray-500 dark:text-gray-400">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('loan', $results->count()) }} found for “{{ $q }}”</p>
          <div class="max-w-full overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-theme-sm text-gray-700 dark:text-gray-300 [&_td]:whitespace-nowrap [&_td]:py-3 [&_td]:pr-4 [&_td:last-child]:pr-0">
              <caption class="sr-only">Search results</caption>
              <thead class="border-y border-gray-100 dark:border-gray-800"><tr>
                @foreach (['Borrower', 'Loan · product', 'Status', 'Amount', ''] as $h)<th scope="col" @class(['py-3 pr-4 text-theme-xs font-medium text-gray-500 dark:text-gray-400', 'text-right' => $h === 'Amount'])>{{ $h }}</th>@endforeach
              </tr></thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($results as $r)
                  <tr @class(['bg-brand-50/50 dark:bg-brand-500/10' => $loan && $loan->id === $r->id])>
                    <td><x-staff.person :user="$r->user" :sub="$r->user->phone ?? null" /></td>
                    <td><span class="font-medium text-gray-800 dark:text-white/90">Loan #{{ $r->id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $r->loanType->name ?? '—' }}</span></td>
                    <td><x-staff.status-badge :status="$r->status" /></td>
                    <td class="text-right"><x-staff.money :amount="$r->amount" class="text-gray-800 dark:text-white/90" /></td>
                    <td class="text-right">
                      @if ($loan && $loan->id === $r->id)
                        <x-staff.pill tone="brand">Selected</x-staff.pill>
                      @else
                        <a href="{{ route('staff.repayments.receive', ['q' => $q, 'loan' => $r->id]) }}" class="inline-flex rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">Select</a>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    </div>
  </section>

  @if ($loan)
    <script>
        window.splitPreview = window.splitPreview || function (url) {
          return {
            r: null, error: null, busy: false, seq: 0,
            kes(v) { return 'KES ' + Number(v || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            pct(v) { const t = this.r ? ['penalty', 'interest', 'principal', 'overpaid'].reduce((s, k) => s + Number(this.r.allocation[k] || 0), 0) : 0; return t ? (Number(v || 0) / t * 100).toFixed(2) : 0; },
            async update(e) {
              const form = this.$root.querySelector('form[data-ajax-form]');
              if (!form || (e && e.target && !form.contains(e.target))) return;
              const cycle = form.querySelector('input[name=loan_cycle_id]:checked')?.value;
              const amount = parseFloat(form.querySelector('[name=amount]')?.value || '');
              const date = form.querySelector('[name=repayment_date]')?.value || '';
              if (!cycle || !(amount > 0)) { this.r = null; this.error = null; return; }
              const my = ++this.seq; this.busy = true;
              try {
                const res = await fetch(url + '?' + new URLSearchParams({ loan_cycle_id: cycle, amount, date }), { headers: { Accept: 'application/json' } });
                const body = await res.json();
                if (my !== this.seq) return;
                if (!res.ok) { this.r = null; this.error = Object.values(body.errors || {})[0]?.[0] || 'Could not work out the split.'; return; }
                this.r = body; this.error = null;
              } catch (_) { if (my === this.seq) { this.r = null; this.error = 'Could not work out the split.'; } }
              finally { if (my === this.seq) this.busy = false; }
            },
          };
        };
      </script>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5" x-data="splitPreview(@js(route('staff.repayments.split-preview')))" @input.debounce.400ms="update($event)" @change="update($event)">
      <section class="{{ $card }} min-w-0 lg:col-span-3">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Loan #{{ $loan->id }} · {{ $loan->user->name ?? '' }}</h2>
          <x-staff.status-badge :status="$loan->status" />
        </div>

        @if (! in_array($loan->status, \App\Http\Controllers\Staff\CashDeskController::REPAYABLE, true))
          <x-staff.alert type="warn">This loan is {{ $loan->status }}; payments are taken only on open loans.</x-staff.alert>
        @elseif ($cycles->isEmpty())
          <x-staff.alert type="warn">This loan has no cycles, so a payment cannot be recorded against it. Open the loan page.</x-staff.alert>
        @elseif (auth()->user()->cannot('create repayments'))
          <x-staff.alert type="info" title="Read-only">You can see the loan but not record payments.</x-staff.alert>
        @else
          <form method="POST" action="{{ route('repayments.store') }}" data-ajax-form data-done="{{ route('staff.repayments.receive', ['loan' => $loan->id, 'done' => 'repaid']) }}" class="space-y-5">
            @csrf
            <input type="hidden" name="loan_id" value="{{ $loan->id }}">
            <fieldset>
              <legend class="mb-2 text-theme-sm font-medium text-gray-700 dark:text-gray-400">Cycle this payment is for <span class="text-error-500">*</span></legend>
              <div class="space-y-2">
                @foreach ($cycles as $c)
                  <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:border-gray-800 dark:has-[:checked]:bg-brand-500/10">
                    <input type="radio" name="loan_cycle_id" value="{{ $c->id }}" class="h-4 w-4 accent-brand-500" @checked($defaultCycle && $defaultCycle->id === $c->id) required>
                    <span class="min-w-0 flex-1">
                      <span class="block text-theme-sm font-medium text-gray-800 dark:text-white/90">Cycle {{ $c->cycle_number }} · due {{ \Illuminate\Support\Carbon::parse($c->due_date)->format('j M Y') }}</span>
                      <span class="block text-theme-xs text-gray-500">Balance <x-staff.money :amount="$c->new_balance" tone="muted" /> · paid <x-staff.money :amount="$c->paid_so_far" tone="muted" /></span>
                    </span>
                    <x-staff.status-badge :status="$c->status === 'completed' ? 'repaid' : ($c->status === 'active' ? 'disbursed' : $c->status)" :label="ucfirst($c->status)" />
                  </label>
                @endforeach
              </div>
            </fieldset>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <x-staff.field name="amount" label="Amount (KES)" type="number" step="0.01" min="0.01" required />
              <x-staff.field name="repayment_date" label="Date received" type="date" :value="now()->toDateString()" required />
            </div>
            {{-- mode and references; bank ↔ M-Pesa keeps both (owner, 23 Oct 2026) --}}
            <x-staff.pay-refs direction="in" mode="mpesa" date-field="repayment_date" />
            <div data-errors hidden class="rounded-lg border border-error-500/30 bg-error-50 p-3 text-theme-sm text-error-800 dark:bg-error-500/15 dark:text-error-400" role="alert"></div>
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-brand-500 px-5 py-3 text-theme-sm font-medium text-white hover:bg-brand-600 disabled:opacity-60 sm:w-auto">Record payment</button>
          </form>
        @endif
      </section>

      <aside class="min-w-0 space-y-6 lg:col-span-2">
        {{-- split preview (owner, 23 Oct 2026): the same waterfall and share rules as a real repayment, nothing saved --}}
        <section class="{{ $card }}" aria-live="polite">
          <div class="mb-1 flex items-center justify-between gap-2">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">How this payment splits</h2>
            <span x-show="busy" x-cloak class="text-theme-xs text-gray-400">Working it out…</span>
          </div>
          <p class="mb-4 text-theme-xs text-gray-500 dark:text-gray-400">Preview only. Pick the cycle and type the amount.</p>
          <template x-if="!r && !error">
            <div class="rounded-xl border border-dashed border-gray-200 p-4 text-center text-theme-sm text-gray-400 dark:border-gray-800">Enter an amount to see the split.</div>
          </template>
          <template x-if="error"><p class="text-theme-sm text-error-600" x-text="error"></p></template>
          <template x-if="r">
            <div class="space-y-4">
              <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
                <template x-for="row in [['Penalty', r.allocation.penalty, 'bg-error-500'], ['Interest', r.allocation.interest, 'bg-amber-500'], ['Principal', r.allocation.principal, 'bg-brand-500'], ['Overpaid (not owed)', r.allocation.overpaid, 'bg-gray-400']]" :key="row[0]">
                  <div class="flex items-center justify-between gap-3 py-2" x-show="row[0] !== 'Overpaid (not owed)' || row[1] > 0">
                    <dt class="flex items-center gap-2 text-gray-600 dark:text-gray-400"><span class="size-2 rounded-full" :class="row[2]"></span><span x-text="row[0]"></span></dt>
                    <dd class="font-outfit font-medium tabular-nums text-gray-800 dark:text-white/90" x-text="kes(row[1])"></dd>
                  </div>
                </template>
              </dl>
              <div class="flex h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800" aria-hidden="true">
                <div class="bg-error-500" :style="`width:${pct(r.allocation.penalty)}%`"></div>
                <div class="bg-amber-500" :style="`width:${pct(r.allocation.interest)}%`"></div>
                <div class="bg-brand-500" :style="`width:${pct(r.allocation.principal)}%`"></div>
                <div class="bg-gray-400" :style="`width:${pct(r.allocation.overpaid)}%`"></div>
              </div>
              <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-theme-sm dark:bg-white/[0.03]">
                <span class="text-gray-500 dark:text-gray-400" title="Includes penalty charged up to the date received">Cycle owes today</span>
                <span class="font-outfit tabular-nums"><span x-text="kes(r.owed_before)"></span> <span class="text-gray-400">→</span> <b class="text-gray-800 dark:text-white/90" x-text="kes(r.owed_after)"></b></span>
              </div>
              <div x-show="r.shares.length">
                <p class="mb-2 text-theme-xs font-medium text-gray-500 dark:text-gray-400">Interest and penalty shared</p>
                <ul class="space-y-2">
                  <template x-for="sh in r.shares" :key="sh.partner + sh.type">
                    <li class="flex items-start justify-between gap-3 text-theme-sm">
                      <span><span class="text-gray-800 dark:text-white/90" x-text="sh.house ? sh.partner + ' (house)' : sh.partner"></span><span class="block text-theme-xs text-gray-500 dark:text-gray-400" x-text="sh.why"></span></span>
                      <span class="font-outfit tabular-nums text-gray-800 dark:text-white/90" x-text="kes(sh.amount)"></span>
                    </li>
                  </template>
                </ul>
              </div>
              <p class="text-theme-xs text-gray-500 dark:text-gray-400" x-show="!r.split_on">Splitting is off on this system, so the shares are not posted to partners yet. The penalty, interest and principal figures still apply.</p>
            </div>
          </template>
        </section>
        <section class="{{ $card }}">
          <h2 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Borrower says they paid?</h2>
          <p class="text-theme-sm text-gray-500 dark:text-gray-400">“I've paid” claims and M-Pesa payments that didn't match a loan wait on <a class="font-medium text-brand-500 hover:underline" href="{{ route('staff.payments') }}">M-Pesa payments</a>. Check there before recording the same money here.</p>
        </section>
      </aside>
    </div>

    <section class="mt-6">
      @if ($recent->isEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No payments yet on this loan" /></div>
      @else
        <x-staff.table title="Recent payments on this loan" caption="Recent payments" :headers="['Payment', 'Cycle', 'Mode', 'Reference', ['Amount', 'num'], '']">
          @foreach ($recent as $p)
            <tr>
              <td data-sort-value="{{ \Illuminate\Support\Carbon::parse($p->repayment_date)->timestamp }}"><span class="font-medium text-gray-800 dark:text-white/90">#{{ $p->id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($p->repayment_date)->format('j M Y') }}</span></td>
              <td>Cycle {{ optional($cycles->firstWhere('id', $p->loan_cycle_id))->cycle_number ?? '—' }}</td>
              <td><x-staff.pill :tone="$p->mode === 'mpesa' ? 'green' : 'gray'">{{ ['mpesa' => 'M-Pesa', 'cash' => 'Cash', 'bank' => 'Bank', 'cheque' => 'Cheque'][$p->mode] ?? ($p->mode ?: '—') }}</x-staff.pill></td>
              <td class="font-mono text-theme-xs">{{ $p->transaction ?: '—' }}</td>
              <td class="num"><x-staff.money :amount="$p->amount" class="text-gray-800 dark:text-white/90" /></td>
              <td class="text-right">
                @can('delete repayments')
                  <x-staff.confirm id="reverse-{{ $p->id }}" :action="route('staff.repayments.reverse', $p)" title="Reverse payment #{{ $p->id }}?"
                    trigger="Reverse" confirm="Reverse payment" tone="danger"
                    trigger-class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                    KES {{ number_format($p->amount, 2) }} ({{ $p->transaction ?: 'no reference' }}) is removed through the existing repayment delete. The cycle and loan status are re-checked by that path. Your reason goes to the audit log.
                    <x-slot:fields>
                      <x-staff.field name="reason" id="reason-{{ $p->id }}" label="Reason" type="textarea" required minlength="5" maxlength="500" />
                    </x-slot:fields>
                  </x-staff.confirm>
                @endcan
              </td>
            </tr>
          @endforeach
        </x-staff.table>
        <p class="mt-2 text-theme-xs text-gray-500">Reversal deletes the row today (the audit log keeps the old values and your reason). Proper reversal entries that keep the original are Stage 4.</p>
      @endif
    </section>
  @endif
@endsection
