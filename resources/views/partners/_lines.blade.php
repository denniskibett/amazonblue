{{-- A partner's three sides in one card (owner, 23 Oct 2026): money they put in (lines), clients they brought in,
     and their own borrowing (full rate, broker share back). Record new money from the M-Pesa SMS here. --}}
@php
  $plines = app(\App\Services\Partners\PartnerLines::class);
  $lines = \App\Models\Investment::where('return_type', 'loan_share')
    ->whereHas('fundings', fn ($q) => $q->where('partner_id', $partner->id))->orderByDesc('id')->get()
    ->map(fn ($l) => ['line' => $l, 's' => $plines->status($l)]);
  $ownLoans = $partner->user_id ? \App\Models\Loan::where('user_id', $partner->user_id)->get(['id', 'status', 'broker_status', 'partner_id', 'amount']) : collect();
  $ownBack = $ownLoans->filter(fn ($l) => $l->broker_status && (int) $l->partner_id === $partner->id)->count();
  $clients = \App\Models\Loan::where('broker_status', 1)->where('partner_id', $partner->id)->when($partner->user_id, fn ($q) => $q->where('user_id', '!=', $partner->user_id))->count();
  $input_ = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $label = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
  $kes = fn ($n) => number_format((float) $n, 2);
@endphp
@include('partials.ui.partner-funding-js')
<div class="mt-6 rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
  <div class="border-b border-gray-200 p-4 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Money, clients and own loans</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      {{ $lines->where('line.status', 'active')->count() }} open line(s) ·
      {{ $clients }} client loan(s) brought in ·
      {{ $ownLoans->count() }} own loan(s){{ $ownLoans->count() ? ', ' . $ownBack . ' getting the broker share back' : '' }}
      @if ($ownLoans->count() > $ownBack)<span class="text-amber-700 dark:text-amber-400"> (run <code>partners:own-loans --partner={{ $partner->id }}</code> for the rest)</span>@endif
    </p>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-theme-sm">
      <thead><tr class="border-b border-gray-100 text-left text-theme-xs text-gray-500 dark:border-gray-700">
        <th class="px-4 py-2">Line</th><th class="px-4 py-2">Term</th><th class="px-4 py-2 text-right">Put in</th><th class="px-4 py-2 text-right">Lent now</th>
        <th class="px-4 py-2 text-right">Free</th><th class="px-4 py-2 text-right">Earned</th><th class="px-4 py-2">Back by</th><th class="px-4 py-2">Status</th></tr></thead>
      <tbody>
        @forelse ($lines as ['line' => $l, 's' => $s])
          <tr class="border-b border-gray-100 dark:border-gray-700">
            <td class="px-4 py-2 text-gray-800 dark:text-white/90">#{{ $l->id }} <span class="block text-theme-xs text-gray-500">{{ $s['loans'] }} loan(s){{ $s['open_loans'] ? ', ' . $s['open_loans'] . ' open' : '' }}</span></td>
            <td class="px-4 py-2">{{ \App\Services\Partners\PartnerLines::TERMS[$l->facility_type] ?? '—' }}</td>
            <td class="px-4 py-2 text-right font-outfit">{{ $kes($s['committed']) }}</td>
            <td class="px-4 py-2 text-right font-outfit">{{ $kes($s['out']) }}</td>
            <td class="px-4 py-2 text-right font-outfit">{{ $kes($s['idle']) }}</td>
            <td class="px-4 py-2 text-right font-outfit">{{ $kes($s['earned']) }}</td>
            <td class="px-4 py-2">{{ $l->maturity_date?->format('j M Y') ?? '—' }}</td>
            <td class="px-4 py-2">
              @if ($l->status === 'active')<x-staff.pill tone="blue" dot>Open</x-staff.pill>
              @else<x-staff.pill tone="green" dot>Closed {{ $l->exit_date?->format('j M') }}</x-staff.pill>@if ($s['shortfall'] > 0)<span class="block text-theme-xs text-error-600">KES {{ $kes($s['shortfall']) }} not paid back</span>@endif
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No money recorded as lines yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @can('edit partners')
    <form method="POST" action="{{ route('staff.partners.lines.store', $partner) }}" class="space-y-4 border-t border-gray-200 p-4 dark:border-gray-700"
      x-data="disburseFunding(@js(['house' => null, 'mode' => 'new', 'maturity' => '', 'read' => route('staff.mpesa.read-sms'), 'amount' => old('amount', ''), 'code' => old('code', ''), 'date' => old('date', ''), 'term' => old('term', 'short_term')]))">
      @csrf
      <p class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Record money {{ $partner->name }} sent</p>
      <div>
        <label for="pl-sms" class="{{ $label }}">Paste the M-Pesa message</label>
        <textarea id="pl-sms" rows="3" x-model="sms" @input.debounce.500ms="read()" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
        <p class="mt-1 text-theme-xs" :class="readOk ? 'text-success-600' : 'text-amber-700 dark:text-amber-400'" x-text="readMsg" x-show="readMsg"></p>
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div><label for="pl-a" class="{{ $label }}">Amount (KES)</label><input id="pl-a" name="amount" type="number" step="0.01" min="1" x-model="amount" required class="{{ $input_ }}"></div>
        <div><label for="pl-c" class="{{ $label }}">Code</label><input id="pl-c" name="code" x-model="code" maxlength="40" required class="{{ $input_ }} uppercase"></div>
        <div><label for="pl-d" class="{{ $label }}">Date received</label><input id="pl-d" name="date" type="date" x-model="date" class="{{ $input_ }}"></div>
        <div><span class="{{ $label }}">How long</span><input type="hidden" name="term" :value="term">
          <div class="flex gap-2 text-theme-sm">
            <button type="button" @click="term = 'short_term'" :class="term === 'short_term' ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Short term</button>
            <button type="button" @click="term = 'long_term'" :class="term === 'long_term' ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Long term</button>
          </div></div>
        <div><label for="pl-m" class="{{ $label }}">Pay back by</label><input id="pl-m" name="maturity" type="date" x-model="maturity" :required="term === 'long_term'" class="{{ $input_ }}">
          <p class="mt-1 text-theme-xs text-gray-500">Needed for long term. Short term closes when its loans are repaid.</p></div>
        <div><label for="pl-n" class="{{ $label }}">Note (optional)</label><input id="pl-n" name="note" maxlength="500" class="{{ $input_ }}"></div>
      </div>
      @if ($errors->any())<div class="rounded-lg bg-error-50 p-3 text-theme-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">{{ $errors->first() }}</div>@endif
      <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Record the money</button>
    </form>
  @endcan
</div>
