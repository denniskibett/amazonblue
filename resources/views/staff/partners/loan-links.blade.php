{{-- Partner links for one loan (owner, 23 Oct 2026): who brought it in and whose money funded it, with a dry run
     of how every share moves before anything is saved. Used for new links and to backfill past loans. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $input_ = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $label = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
  $types = \App\Services\PartnerPortfolio::TYPES;
  $kes = fn ($n) => number_format((float) $n, 2);
  $broker = $loan->broker_status && $loan->partner_id ? $partners[$loan->partner_id] ?? ('Partner #' . $loan->partner_id) : null;
  $lineNames = \App\Models\Investment::whereIn('id', $disbursements->pluck('investment_id')->filter())->pluck('name', 'id');
  $q = request()->query();
@endphp

@section('content')
  @include('partials.ui.partner-funding-js')
  @include('staff.desk._header', ['title' => 'Partners on loan #' . $loan->id, 'sub' => ($loan->user->name ?? 'Borrower') . ' · ' . ($loan->loanType->name ?? 'loan') . ' · ' . ucfirst($loan->status), 'metrics' => false,
    'actions' => '<a href="' . route('users.loans.show', [$loan->user_id, $loan->id]) . '" class="text-theme-sm font-medium text-brand-500 hover:underline">← Back to the loan</a>'])

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
    <div class="min-w-0 space-y-6 xl:col-span-2">
      <section class="{{ $card }}">
        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Now</h2>
        <dl class="divide-y divide-gray-100 text-theme-sm dark:divide-gray-800">
          <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Brought in by</dt><dd class="text-right">{{ $broker ?? 'Nobody (our client)' }}@if ($broker)<span class="block text-theme-xs text-gray-500">{{ rtrim(rtrim(number_format((float) $loan->broker_rate, 2), '0'), '.') }}% of interest · {{ rtrim(rtrim(number_format((float) ($loan->broker_penalty_rate ?? 50), 2), '0'), '.') }}% of penalty</span>@endif</dd></div>
          @if ($own)<div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">Borrower</dt><dd class="text-right">{{ $own->name }}'s own loan<span class="block text-theme-xs text-gray-500">{{ (int) $loan->partner_id === $own->id ? 'gets their broker share back' : 'not getting their broker share back yet' }}</span></dd></div>@endif
          @forelse ($disbursements as $d)
            <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500">{{ ucfirst($d->kind ?? 'cash') }} {{ optional($d->disburse_date)->format('j M Y') }}</dt>
              <dd class="text-right"><span class="font-outfit">KES {{ $kes($d->amount) }}</span><span class="block text-theme-xs text-gray-500">{{ $d->investment_id ? ($lineNames[$d->investment_id] ?? 'line #' . $d->investment_id) : 'no line' }}</span></dd></div>
          @empty
            <div class="py-2 text-gray-500">Not disbursed yet.</div>
          @endforelse
        </dl>
      </section>
      <section class="{{ $card }}">
        <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Shares posted so far</h2>
        @forelse ($journal as $name => $byType)
          <div class="flex justify-between gap-4 border-b border-gray-100 py-2 text-theme-sm last:border-0 dark:border-gray-800">
            <span class="text-gray-700 dark:text-gray-300">{{ $name }}</span>
            <span class="text-right">@foreach ($byType as $t => $v)<span class="block"><span class="text-theme-xs text-gray-500">{{ $types[$t] ?? $t }}</span> <span class="font-outfit">{{ $kes($v) }}</span></span>@endforeach</span>
          </div>
        @empty
          <p class="text-theme-sm text-gray-500">No repayments split on this loan yet.</p>
        @endforelse
      </section>
    </div>

    <div class="min-w-0 space-y-6 xl:col-span-3">
      <section class="{{ $card }}">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white/90">Change</h2>
        <p class="mb-4 text-theme-sm text-gray-500 dark:text-gray-400">Nothing is saved until you check the preview and confirm. Every repayment on the loan is then re-split, so each share moves by exactly the difference.</p>
        <form method="GET" action="{{ route('staff.loans.partners', $loan) }}" class="space-y-4"
          x-data="disburseFunding(@js(['house' => null, 'maturity' => $q['new_maturity'] ?? $defaultMaturity, 'read' => route('staff.mpesa.read-sms'), 'mode' => $q['mode'] ?? 'keep', 'term' => $q['new_term'] ?? 'short_term', 'amount' => $q['new_amount'] ?? '', 'code' => $q['new_code'] ?? '', 'date' => $q['new_date'] ?? '']))">
          <input type="hidden" name="preview" value="1">
          <div>
            <label for="f-broker" class="{{ $label }}">Brought in by</label>
            <select id="f-broker" name="broker" class="{{ $input_ }}">
              <option value="keep" @selected(($q['broker'] ?? 'keep') === 'keep')>Leave as it is</option>
              <option value="0" @selected(($q['broker'] ?? '') === '0')>Nobody: our own client</option>
              @foreach ($partners as $pid => $pname)<option value="{{ $pid }}" @selected(($q['broker'] ?? '') === (string) $pid)>{{ $pname }}{{ $own && $own->id === (int) $pid ? ' (their own loan: broker share back)' : '' }}</option>@endforeach
            </select>
          </div>
          <div>
            <span class="{{ $label }}">Funded by</span>
            <input type="hidden" name="mode" :value="mode">
            <div class="flex flex-wrap gap-2 text-theme-sm">
              @foreach (['keep' => 'Leave as it is', 'line' => 'Move to a line', 'new' => 'Partner money (SMS)'] as $k => $l)
                <button type="button" @click="mode = '{{ $k }}'" :class="mode === '{{ $k }}' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">{{ $l }}</button>
              @endforeach
            </div>
          </div>
          <div x-show="mode === 'line'" x-cloak>
            <label for="f-line" class="{{ $label }}">Line</label>
            <select id="f-line" name="line" :disabled="mode !== 'line'" class="{{ $input_ }}">
              @foreach ($lineOptions as $id => $name)<option value="{{ $id }}" @selected((string) ($q['line'] ?? $currentLine) === (string) $id)>{{ $name }}</option>@endforeach
            </select>
          </div>
          <div x-show="mode === 'new'" x-cloak class="space-y-4">
            <div>
              <label for="f-sms" class="{{ $label }}">Paste the M-Pesa message</label>
              <textarea id="f-sms" rows="3" x-model="sms" @input.debounce.500ms="read()" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
              <p class="mt-1 text-theme-xs" :class="readOk ? 'text-success-600' : 'text-amber-700 dark:text-amber-400'" x-text="readMsg" x-show="readMsg"></p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div><label for="f-np" class="{{ $label }}">Partner who sent it</label>
                <select id="f-np" name="new_partner" :disabled="mode !== 'new'" class="{{ $input_ }}"><option value="">Choose…</option>@foreach ($partners as $pid => $pname)<option value="{{ $pid }}" @selected(($q['new_partner'] ?? '') === (string) $pid)>{{ $pname }}</option>@endforeach</select></div>
              <div><label for="f-na" class="{{ $label }}">Amount received (KES)</label><input id="f-na" name="new_amount" type="number" step="0.01" min="1" x-model="amount" :disabled="mode !== 'new'" class="{{ $input_ }}"></div>
              <div><label for="f-nc" class="{{ $label }}">M-Pesa or bank code</label><input id="f-nc" name="new_code" x-model="code" maxlength="40" :disabled="mode !== 'new'" class="{{ $input_ }} uppercase"></div>
              <div><label for="f-nd" class="{{ $label }}">Date received</label><input id="f-nd" name="new_date" type="date" x-model="date" :disabled="mode !== 'new'" class="{{ $input_ }}"></div>
              <div><span class="{{ $label }}">How long</span><input type="hidden" name="new_term" :value="term" :disabled="mode !== 'new'">
                <div class="flex gap-2 text-theme-sm">
                  <button type="button" @click="term = 'short_term'; maturity = defaults.maturity" :class="term === 'short_term' ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Short term</button>
                  <button type="button" @click="term = 'long_term'" :class="term === 'long_term' ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Long term</button>
                </div></div>
              <div><label for="f-nm" class="{{ $label }}">Pay back by</label><input id="f-nm" name="new_maturity" type="date" x-model="maturity" :disabled="mode !== 'new'" class="{{ $input_ }}"></div>
            </div>
          </div>
          <button type="submit" class="rounded-lg border border-brand-500 px-5 py-2.5 text-theme-sm font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">Preview (nothing is saved)</button>
        </form>
      </section>

      @if ($preview)
        <section class="{{ $card }}">
          <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">Preview</h2>
          @if ($preview['error'])
            <x-staff.alert type="error" title="This can't be done">{{ $preview['error'] }}</x-staff.alert>
          @elseif (! $preview['changes'])
            <x-staff.alert type="info" title="Nothing would change">The loan already has these links.</x-staff.alert>
          @else
            <ul class="mb-4 list-disc pl-5 text-theme-sm text-gray-700 dark:text-gray-300">@foreach ($preview['changes'] as $c)<li>{{ ucfirst($c) }}</li>@endforeach</ul>
            @php
              $rows = [];
              foreach (array_unique(array_merge(array_keys($preview['before']), array_keys($preview['after']))) as $name) {
                foreach (array_unique(array_merge(array_keys($preview['before'][$name] ?? []), array_keys($preview['after'][$name] ?? []))) as $t) {
                  $b = $preview['before'][$name][$t] ?? 0; $a = $preview['after'][$name][$t] ?? 0;
                  $rows[] = [$name, $types[$t] ?? $t, $b, $a, round($a - $b, 2)];
                }
              }
            @endphp
            <div class="overflow-x-auto">
              <table class="w-full text-theme-sm">
                <thead><tr class="border-b border-gray-100 text-left text-theme-xs text-gray-500 dark:border-gray-800"><th class="py-2 pr-3">Who</th><th class="py-2 pr-3">What</th><th class="py-2 pr-3 text-right">Before</th><th class="py-2 pr-3 text-right">After</th><th class="py-2 text-right">Moves</th></tr></thead>
                <tbody>
                  @foreach ($rows as [$name, $what, $b, $a, $diff])
                    <tr class="border-b border-gray-100 dark:border-gray-800"><td class="py-2 pr-3 text-gray-800 dark:text-white/90">{{ $name }}</td><td class="py-2 pr-3 text-gray-500">{{ $what }}</td>
                      <td class="py-2 pr-3 text-right font-outfit">{{ $kes($b) }}</td><td class="py-2 pr-3 text-right font-outfit">{{ $kes($a) }}</td>
                      <td class="py-2 text-right font-outfit {{ $diff > 0 ? 'text-success-600' : ($diff < 0 ? 'text-error-600' : 'text-gray-400') }}">{{ $diff > 0 ? '+' : '' }}{{ $kes($diff) }}</td></tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <form method="POST" action="{{ route('staff.loans.partners.apply', $loan) }}" class="mt-4 space-y-3">
              @csrf
              @foreach (['broker', 'mode', 'line', 'new_partner', 'new_amount', 'new_code', 'new_date', 'new_term', 'new_maturity'] as $k)
                @if (isset($q[$k]))<input type="hidden" name="{{ $k }}" value="{{ $q[$k] }}">@endif
              @endforeach
              <label class="flex items-start gap-2 text-theme-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="confirm" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300"> I checked the preview. Post these changes.</label>
              @error('confirm')<p class="text-theme-xs text-error-600">{{ $message }}</p>@enderror
              @error('line')<p class="text-theme-xs text-error-600">{{ $message }}</p>@enderror
              <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Apply</button>
            </form>
          @endif
        </section>
      @endif
    </div>
  </div>

@endsection
