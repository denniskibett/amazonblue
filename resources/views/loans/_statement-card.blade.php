{{-- The full loan statement card (owner, 16 Nov 2026): shared by the staff page and the borrower's link. --}}
@php
  $kes = fn ($n) => number_format((float) $n, 2);
@endphp
<div class="az-statement rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  {{-- letterhead (owner, 16 Nov 2026): logo top left, compact so the statement fits one page --}}
  <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-800">
    <img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures Capital" class="h-7 w-auto">
    <div class="text-right">
      <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Loan Statement</h3>
      <p class="text-xs text-gray-500">#{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }} · as at {{ now()->format('j M Y') }}</p>
    </div>
  </div>

  <div class="p-5">
    <div class="mb-2 grid grid-cols-3 gap-4 text-xs text-gray-500 dark:text-gray-400">
      <div>
        <p class="font-medium text-gray-700 dark:text-gray-300">From</p>
        <p class="font-semibold text-gray-800 dark:text-white/90">Aimzone Ventures Capital</p>
        <p>G.P.O 50054 - 00100, Nairobi, Kenya</p>
      </div>
      <div>
        <p class="font-medium text-gray-700 dark:text-gray-300">To</p>
        <p class="font-semibold text-gray-800 dark:text-white/90">{{ $loan->user->name ?? 'N/A' }}</p>
        <p>{{ $loan->user->phone ?? '' }}@if ($loan->user?->email) · {{ $loan->user->email }}@endif</p>
      </div>
      <div class="text-right">
        <p><span class="font-medium text-gray-700 dark:text-gray-300">Issued</span> {{ \Carbon\Carbon::parse($loan->borrow_date)->format('j M Y') }}</p>
        <p><span class="font-medium text-gray-700 dark:text-gray-300">Due</span> {{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('j M Y') : 'N/A' }}</p>
        <p><span class="font-medium text-gray-700 dark:text-gray-300">Product</span> {{ $loan->loanType->name ?? '—' }}</p>
      </div>
    </div>

    {{-- the loan's status and what it means (owner, 16 Nov 2026) --}}
    <div class="mb-2 flex items-start gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-1.5 text-xs dark:border-gray-800 dark:bg-white/[0.02]">
      <span class="shrink-0 rounded-full px-2 py-0.5 font-semibold {{ $status['tone'] }}">{{ $status['label'] }}</span>
      <span class="text-gray-600 dark:text-gray-300">{{ $status['means'] }}</span>
    </div>

    {{-- the whole loan at a glance --}}
    <div class="mb-2 grid grid-cols-4 gap-3 rounded-lg border border-gray-100 px-3 py-1.5 dark:border-gray-800">
      <div><p class="text-[11px] text-gray-500">Cycles</p><p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $cycleCount }}</p></div>
      <div><p class="text-[11px] text-gray-500">Loan sent</p><p class="text-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $kes($totals['lent']) }}</p></div>
      <div><p class="text-[11px] text-gray-500">Interest + penalty</p><p class="text-sm font-semibold tabular-nums text-gray-800 dark:text-white/90">{{ $kes($totals['interest'] + $totals['penalty']) }}</p></div>
      <div><p class="text-[11px] text-gray-500">Paid</p><p class="text-sm font-semibold tabular-nums text-emerald-600">{{ $kes($totals['paid']) }}</p></div>
    </div>

    {{-- the ledger: cycle is a column, the balance runs down (owner, 16 Nov 2026) --}}
    <div class="az-ledger mb-3 overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800">
      <table class="w-full table-fixed whitespace-nowrap text-xs">
        <colgroup><col style="width:11%"><col style="width:6%"><col><col style="width:12%"><col style="width:12%"><col style="width:13%"></colgroup>
        <thead class="bg-gray-50 text-left dark:bg-white/[0.02]">
          <tr class="text-gray-700 dark:text-gray-400">
            <th class="px-3 py-2.5 font-medium">Date</th>
            <th class="px-2 py-2.5 font-medium">Cycle</th>
            <th class="px-2 py-2.5 font-medium">Description</th>
            <th class="px-2 py-2.5 text-right font-medium">Charges</th>
            <th class="px-2 py-2.5 text-right font-medium">Payments</th>
            <th class="px-3 py-2.5 text-right font-medium">Balance (KES)</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($lines as $i => $l)
            @php $newCycle = $i > 0 && $lines[$i - 1]->n !== $l->n; @endphp
            <tr class="az-line border-t {{ $newCycle ? 'border-gray-300 dark:border-gray-600' : 'border-gray-100 dark:border-gray-800' }}">
              <td class="whitespace-nowrap px-3 py-2 text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($l->date)->format('j M Y') }}</td>
              <td class="px-2 py-2 text-gray-500 dark:text-gray-400">#{{ $l->n }}</td>
              @php $block = $l->ref && str_contains($l->text, 'djustment'); $wide = $l->note && $l->charge == 0 && $l->payment == 0; @endphp
              <td @if ($wide) colspan="3" @endif class="w-full px-2 py-2 {{ $block ? 'whitespace-normal' : 'truncate' }} {{ $l->note ? 'italic text-gray-400' : 'text-gray-700 dark:text-gray-300' }}" @if ($l->ref) title="{{ $l->text }} · {{ $l->ref }}" @endif><span>{{ $l->text }}@if ($l->ref && ! $block)<span class="not-italic text-gray-400"> · {{ $l->ref }}</span>@endif</span>@if ($block)<span class="block text-[11px] not-italic leading-snug text-gray-400">{{ $l->ref }}</span>@endif</td>
              @unless ($wide)
              <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $l->charge > 0 ? $kes($l->charge) : '' }}</td>
              <td class="whitespace-nowrap px-2 py-2 text-right tabular-nums text-emerald-600">{{ $l->payment > 0 ? $kes($l->payment) : '' }}</td>
              @endunless
              <td class="whitespace-nowrap px-3 py-2 text-right font-medium tabular-nums text-gray-800 dark:text-white/90">{{ $kes($l->balance) }}</td>
            </tr>
          @endforeach
          <tr class="az-line border-t border-gray-200 bg-gray-50 font-semibold text-gray-800 dark:border-gray-700 dark:bg-white/[0.02] dark:text-white/90">
            <td class="px-4 py-3" colspan="3">Totals</td>
            <td class="whitespace-nowrap px-2 py-2.5 text-right tabular-nums">{{ $kes(collect($lines)->sum('charge')) }}</td>
            <td class="whitespace-nowrap px-2 py-2.5 text-right tabular-nums text-emerald-600">{{ $kes(collect($lines)->sum('payment')) }}</td>
            <td class="whitespace-nowrap px-3 py-2.5 text-right tabular-nums">{{ $kes($balance) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-800">
      <span class="text-sm font-semibold text-gray-800 dark:text-white/90">Balance due</span>
      <span class="text-base font-bold tabular-nums {{ $balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">KES {{ $kes($balance) }}</span>
    </div>
    <p class="mt-1 text-[11px] text-gray-400">A rollover carries the balance into the next cycle; no money changes hands. Printed {{ now()->format('j M Y, H:i') }}.</p>
    {{-- a statement is a legal document: it ends visibly so nothing can be added below (owner, 16 Nov 2026) --}}
    <div class="mt-2 flex items-center gap-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400">
      <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>End of statement<span class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></span>
    </div>
  </div>
</div>
