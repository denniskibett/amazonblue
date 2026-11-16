{{-- A partner's investment lines (owner, 16 Nov 2026; Figma "Partners redesign"): each line shows what it holds now, and
     opens (View, or a click on the row) to the loans it funded, whether and when they were paid. Every form is a button
     that opens a right-side sheet (x-staff.sheet): record money sent, record payout, edit terms / paid back from,
     part of a loan, the house settling a slice. Routes, permissions and field names are the ones the inline forms used. --}}
@php
  $plines = app(\App\Services\Partners\PartnerLines::class);
  $lines = \App\Models\Investment::whereIn('return_type', ['loan_share', 'fixed_return'])
    ->whereHas('fundings', fn ($q) => $q->where('partner_id', $partner->id))->orderByDesc('id')->get()
    ->map(fn ($l) => ['line' => $l, 's' => $plines->status($l)]);
  $input_ = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $label = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
  $btnSm = 'inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
  $btnSmPrimary = 'inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white shadow-theme-xs hover:bg-brand-600';
  $primaryBtn = 'inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600';
  $ghostBtn = 'inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
  $kes = fn ($n) => number_format((float) $n, 2);
  // old input belongs to the sheet that was sent; the other sheets keep their own defaults
  $old = fn ($sheet, $key, $def = null) => old('_sheet') === $sheet ? old($key, $def) : $def;
  $sheetError = fn ($sheet) => old('_sheet') === $sheet && $errors->any() ? $errors->first() : null;
  $final = \App\Models\Loan::STATUS_FINAL_LIST;
  $open = $lines->where('line.status', 'active');
  $openLines = $plines->openLines();
  $rows = $lines->map(function ($row) use ($plines, $partner, $final) {
    ['line' => $l, 's' => $s] = $row;
    $fixed = $l->return_type === 'fixed_return';
    $due = $fixed ? $plines->fixedDue($l) : null;
    $slicesOf = \Illuminate\Support\Facades\DB::table('loan_fundings as f')->join('loans as l', 'l.id', '=', 'f.loan_id')->join('users as u', 'u.id', '=', 'l.user_id')
      ->leftJoin('disbursements as d', 'd.id', '=', 'f.disbursement_id')->where('f.investment_id', $l->id)->orderBy('f.id')
      ->get(['f.*', 'l.status as loan_status', 'u.id as borrower_id', 'u.name as borrower', 'u.phone as borrower_phone', 'd.disburse_date', 'd.transaction as d_ref']);
    $loanIds = $slicesOf->pluck('loan_id')->unique();
    $multi = \Illuminate\Support\Facades\DB::table('loan_fundings')->whereIn('loan_id', $loanIds)->groupBy('loan_id')->havingRaw('count(*) > 1')->pluck('loan_id')->all();
    // what the borrower paid on each loan and the last day they paid, and what this partner earned from it
    $paid = \Illuminate\Support\Facades\DB::table('repayments')->whereIn('loan_id', $loanIds)->whereNull('deleted_at')->groupBy('loan_id')
      ->selectRaw('loan_id, sum(amount) as total, max(repayment_date) as last_on')->get()->keyBy('loan_id');
    $theirs = \App\Models\PartnerTransaction::where('partner_id', $partner->id)->whereIn('loan_id', $loanIds)->whereIn('type', ['profit_distribution', 'penalty_share'])
      ->groupBy('loan_id')->selectRaw('loan_id, sum(amount) as total')->pluck('total', 'loan_id');
    $settle = $slicesOf->filter(fn ($sl) => ! ($sl->settled_on ?? null) && ! in_array($sl->loan_status, $final, true) && in_array($sl->loan_id, $multi))->pluck('id')->all();

    return compact('l', 's', 'fixed', 'due', 'slicesOf', 'paid', 'theirs', 'settle');
  })->values();
@endphp
@include('partials.ui.partner-funding-js')

<div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
  <div>
    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Investment lines</h3>
    <p class="text-theme-sm text-gray-500 dark:text-gray-400">
      {{ $open->count() }} open {{ \Illuminate\Support\Str::plural('line', $open->count()) }} ·
      KES {{ $kes($open->sum('s.out')) }} lent now · KES {{ $kes($open->sum('s.idle')) }} free
    </p>
  </div>
  @can('edit partners')
    <div class="flex flex-wrap gap-2">
      @if ($lines->isNotEmpty())<button type="button" class="{{ $ghostBtn }}" @click="$dispatch('open-sheet', 'part-of-loan')">Part of a loan…</button>@endif
      <button type="button" class="{{ $primaryBtn }}" @click="$dispatch('open-sheet', 'record-money')"><x-ui.icon name="plus" class="size-4" />Record money sent</button>
    </div>
  @endcan
</div>

<div class="max-w-full overflow-x-auto" tabindex="0" aria-label="Investment lines">
  <table class="w-full min-w-[960px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
    <thead class="border-y border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.02]">
      <tr class="text-theme-xs font-medium text-gray-500 dark:text-gray-400">
        <th class="w-12 py-3 pl-4 sm:pl-6"><span class="sr-only">Open</span></th>
        <th class="py-3 pr-4">Line</th><th class="py-3 pr-4 text-right">Put in</th><th class="py-3 pr-4 text-right">Lent now</th>
        <th class="py-3 pr-4 text-right">Free</th><th class="py-3 pr-4 text-right">Earned</th><th class="py-3 pr-4">Back by</th>
        <th class="py-3 pr-4">Status</th><th class="py-3 pr-4 sm:pr-6"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    @forelse ($rows as $i => $r)
      @php extract($r); @endphp
      <tbody x-data="{ open: @js($i === 0 && $slicesOf->isNotEmpty() && $rows->count() <= 3) }" class="border-b border-gray-100 dark:border-gray-800" data-line="{{ $l->id }}">
        <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.02]" :class="open && 'bg-brand-50/40 dark:bg-white/[0.02]'" @click="if (! $event.target.closest('button, a, form')) open = ! open">
          <td class="py-3.5 pl-4 sm:pl-6">
            <button type="button" @click="open = ! open" :aria-expanded="open" aria-label="Show the loans line #{{ $l->id }} funded"
              class="flex size-7 items-center justify-center rounded-md border border-gray-200 text-gray-500 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-white/5">
              <x-ui.icon name="chevron-down" class="size-4 transition-transform" ::class="open ? '' : '-rotate-90'" />
            </button>
          </td>
          <td class="py-3.5 pr-4">
            <p class="font-medium text-gray-800 dark:text-white/90">#{{ $l->id }}
              @if ($fixed){{ \App\Services\Partners\PartnerLines::fixedLabel((float) $l->interest_rate, (float) $l->partner_share_rate, (int) $l->duration_days) }}@else{{ \App\Services\Partners\PartnerLines::TERMS[$l->facility_type] ?? 'Share of what its loans earn' }}@endif</p>
            <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $s['loans'] }} {{ \Illuminate\Support\Str::plural('loan', $s['loans']) }}{{ $s['open_loans'] ? ', ' . $s['open_loans'] . ' open' : '' }}@if ($fixed) · = {{ rtrim(rtrim(number_format((float) $l->interest_rate, 2, '.', ''), '0'), '.') }}% of the money @endif</span>
          </td>
          <td class="py-3.5 pr-4 text-right font-outfit tabular-nums">{{ $kes($s['committed']) }}</td>
          <td class="py-3.5 pr-4 text-right font-outfit tabular-nums">{{ $kes($s['out']) }}</td>
          <td class="py-3.5 pr-4 text-right font-outfit tabular-nums">{{ $kes($s['idle']) }}@if (($s['paid_out'] ?? 0) > 0)<span class="block text-theme-xs text-gray-500">{{ $kes($s['paid_out']) }} paid to other investors</span>@endif</td>
          <td class="py-3.5 pr-4 text-right font-outfit tabular-nums">
            @if ($fixed){{ $kes($due[1]) }}<span class="block text-theme-xs text-gray-500">fixed, due {{ $kes($due[2]) }}</span>
            @else{{ $kes($s['earned']) }}<span class="block text-theme-xs text-gray-500">{{ rtrim(rtrim(number_format((float) $l->partner_share_rate, 2, '.', ''), '0'), '.') ?: '—' }}% of interest</span>@endif
          </td>
          <td class="whitespace-nowrap py-3.5 pr-4">{{ $l->maturity_date?->format('j M Y') ?? '—' }}</td>
          <td class="py-3.5 pr-4">
            @if ($l->status === 'active' && $fixed && $l->maturity_date?->isPast())<x-staff.pill tone="amber" dot>Due</x-staff.pill>
            @elseif ($l->status === 'active')<x-staff.pill tone="blue" dot>Open</x-staff.pill>
            @else<x-staff.pill tone="green" dot>Closed {{ $l->exit_date?->format('j M') }}</x-staff.pill>@if ($s['shortfall'] > 0)<span class="block text-theme-xs text-error-600">KES {{ $kes($s['shortfall']) }} not paid back</span>@endif
            @endif
          </td>
          <td class="py-3.5 pr-4 sm:pr-6">
            <div class="flex items-center justify-end gap-2 whitespace-nowrap">
              @can('edit partners')
                @if ($l->status === 'active' && $fixed)<button type="button" class="{{ $btnSmPrimary }}" @click="$dispatch('open-sheet', 'payout-{{ $l->id }}')">Record payout</button>@endif
                @if ($fixed)<button type="button" class="{{ $btnSm }}" @click="$dispatch('open-sheet', 'terms-{{ $l->id }}')">Terms</button>@endif
              @endcan
              <button type="button" class="{{ $btnSm }}" @click="open = ! open" x-text="open ? 'Hide' : 'View'">View</button>
            </div>
          </td>
        </tr>
        <tr x-show="open" x-cloak class="bg-brand-50/40 dark:bg-white/[0.02]">
          <td colspan="9" class="px-4 pb-4 sm:pl-16 sm:pr-6">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
              <table class="w-full text-theme-sm">
                <caption class="sr-only">Loans line #{{ $l->id }} funded</caption>
                <thead class="bg-gray-50 text-left text-theme-xs font-medium text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
                  <tr><th class="py-2.5 pl-5 pr-4">Loan</th><th class="py-2.5 pr-4">Borrower</th><th class="py-2.5 pr-4 text-right">Funded</th><th class="py-2.5 pr-4">Disbursed</th>
                    <th class="py-2.5 pr-4">Paid back</th><th class="py-2.5 pr-4 text-right">Borrower paid</th><th class="py-2.5 pr-4">Last paid on</th><th class="py-2.5 pr-4 text-right">Their share</th><th class="py-2.5 pr-5"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  @forelse ($slicesOf as $sl)
                    @php $p = $paid[$sl->loan_id] ?? null; @endphp
                    <tr>
                      <td class="py-2.5 pl-5 pr-4"><a class="font-medium text-brand-500 hover:underline" href="{{ route('loans.show', $sl->loan_id) }}">#{{ $sl->loan_id }}</a></td>
                      <td class="py-2.5 pr-4"><x-staff.person :name="$sl->borrower" :id="$sl->borrower_id" :sub="$sl->borrower_phone" /></td>
                      <td class="py-2.5 pr-4 text-right font-outfit tabular-nums">{{ $kes($sl->amount) }}</td>
                      <td class="whitespace-nowrap py-2.5 pr-4 text-theme-xs text-gray-500 dark:text-gray-400">@if ($sl->disbursement_id)#{{ $sl->disbursement_id }} {{ $sl->d_ref }}<span class="block">{{ $sl->disburse_date ? \Illuminate\Support\Carbon::parse($sl->disburse_date)->format('j M Y') : '' }}</span>@else—@endif</td>
                      <td class="py-2.5 pr-4">
                        @if ($sl->settled_on ?? null)<x-staff.pill tone="green" dot>House settled {{ \Illuminate\Support\Carbon::parse($sl->settled_on)->format('j M Y') }}</x-staff.pill><span class="block text-theme-xs text-gray-500">KES {{ $kes($sl->settled_interest) }} interest</span>
                        @elseif (in_array($sl->loan_status, $final, true))<x-staff.pill tone="green" dot>Paid back</x-staff.pill>
                        @else<x-staff.pill tone="blue" dot>Out on the loan</x-staff.pill>@endif
                      </td>
                      <td class="py-2.5 pr-4 text-right font-outfit tabular-nums">{{ $p ? $kes($p->total) : '—' }}</td>
                      <td class="whitespace-nowrap py-2.5 pr-4">{{ $p && $p->last_on ? \Illuminate\Support\Carbon::parse($p->last_on)->format('j M Y') : '—' }}</td>
                      <td class="py-2.5 pr-4 text-right font-outfit tabular-nums">{{ isset($theirs[$sl->loan_id]) ? $kes($theirs[$sl->loan_id]) : '—' }}</td>
                      <td class="py-2.5 pr-5 text-right">
                        @can('edit partners')
                          @if (in_array($sl->id, $settle))<button type="button" class="{{ $btnSm }}" @click="$dispatch('open-sheet', 'settle-{{ $sl->id }}')">House settles…</button>@endif
                        @endcan
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="9" class="px-5 py-4 text-center text-theme-sm text-gray-500">This line has not funded a loan yet.</td></tr>
                  @endforelse
                </tbody>
                @if ($slicesOf->isNotEmpty())
                  @php $back = $slicesOf->filter(fn ($sl) => ($sl->settled_on ?? null) || in_array($sl->loan_status, $final, true)); @endphp
                  <tfoot><tr><td colspan="9" class="bg-gray-50 px-5 py-2.5 text-theme-xs text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
                    {{ $slicesOf->count() }} {{ \Illuminate\Support\Str::plural('loan', $slicesOf->count()) }} · KES {{ $kes($slicesOf->sum('amount')) }} funded ·
                    KES {{ $kes($back->sum('amount')) }} paid back · KES {{ $kes($slicesOf->sum('amount') - $back->sum('amount')) }} still out
                  </td></tr></tfoot>
                @endif
              </table>
            </div>
          </td>
        </tr>
      </tbody>

    @empty
      <tbody><tr><td colspan="9"><x-staff.empty title="No money recorded as lines yet" message="Record money they sent and it shows here as a line." /></td></tr></tbody>
    @endforelse
  </table>
</div>

@can('edit partners')
  @foreach ($rows as $r)
    @php extract($r); @endphp
        @if ($l->status === 'active' && $fixed)
          {{-- fixed return paid back (owner, 14 Nov 2026): capital and return leave their wallets, the line closes --}}
          <x-staff.sheet :name="'payout-' . $l->id" :title="'Record payout · line #' . $l->id" :sub="$partner->name . ' · ' . \App\Services\Partners\PartnerLines::fixedLabel((float) $l->interest_rate, (float) $l->partner_share_rate, (int) $l->duration_days)" :action="route('staff.partners.lines.pay-back', [$partner, $l])">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
              <p class="font-semibold text-gray-800 dark:text-white/90">Due back KES {{ $kes($due[2]) }}</p>
              <p class="mt-0.5 text-theme-xs text-gray-500">KES {{ $kes($due[0] ?? $s['committed']) }} capital + KES {{ $kes($due[1]) }} return{{ $l->maturity_date ? ', due ' . $l->maturity_date->format('j M Y') : '' }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div><label class="{{ $label }}" for="pb-paid-{{ $l->id }}">Paid (KES)</label><input id="pb-paid-{{ $l->id }}" name="paid" type="number" step="0.01" min="1" required value="{{ $old('payout-' . $l->id, 'paid', $due[2]) }}" class="{{ $input_ }}"></div>
              <div><label class="{{ $label }}" for="pb-date-{{ $l->id }}">On</label><input id="pb-date-{{ $l->id }}" name="date" type="date" required max="{{ now()->toDateString() }}" value="{{ $old('payout-' . $l->id, 'date', min(now()->toDateString(), $l->maturity_date?->toDateString() ?? now()->toDateString())) }}" class="{{ $input_ }}"></div>
            </div>
            <div><label class="{{ $label }}" for="pb-code-{{ $l->id }}">Payout code</label><input id="pb-code-{{ $l->id }}" name="code" required maxlength="40" value="{{ $old('payout-' . $l->id, 'code') }}" class="{{ $input_ }} uppercase">
              <p class="mt-1 text-theme-xs text-gray-500">The M-Pesa or bank reference.</p></div>
            <div><label class="{{ $label }}" for="pb-from-{{ $l->id }}">Paid from</label>
              <select id="pb-from-{{ $l->id }}" name="from_line" class="{{ $input_ }}">
                <option value="">Our money</option>
                @foreach ($openLines as $o)@if ($o->line->id !== $l->id)<option value="{{ $o->line->id }}" @selected($old('payout-' . $l->id, 'from_line') == $o->line->id)>#{{ $o->line->id }} {{ $o->partner?->name }} · KES {{ $kes($o->room) }} free</option>@endif @endforeach
              </select>
              <p class="mt-1 text-theme-xs text-gray-500">Or another open line with free money.</p></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
              <p class="font-semibold">This closes line #{{ $l->id }}</p><p class="text-theme-xs">Capital and return leave their wallets on the day paid.</p>
            </div>
            @if ($e = $sheetError('payout-' . $l->id))<x-staff.alert type="err">{{ $e }}</x-staff.alert>@endif
            <x-slot:footer><button type="submit" class="{{ $primaryBtn }}" data-confirm="Record that {{ $partner->name }} was paid back on line #{{ $l->id }} and close it?">Record payout</button></x-slot:footer>
          </x-staff.sheet>
        @endif

        @if ($fixed)
          {{-- a fixed line's terms, and whose money paid it back (owner, 15 Nov 2026) --}}
          @php
            $lf = $l->fundings()->where('partner_id', $partner->id)->first();
            $gross = (float) $l->partner_share_rate > 0 ? round((float) $l->interest_rate * 100 / (float) $l->partner_share_rate, 2) : (float) $l->interest_rate;
            $tName = 'terms-' . $l->id;
          @endphp
          <x-staff.sheet :name="$tName" :title="'Edit terms · line #' . $l->id" sub="Fixed return lines only">
            <form method="POST" action="{{ route('staff.partners.lines.terms', [$partner, $l]) }}" class="space-y-4">@csrf<input type="hidden" name="_sheet" value="{{ $tName }}">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div><label class="{{ $label }}" for="t-gross-{{ $l->id }}">Rate on the money (%)</label><input id="t-gross-{{ $l->id }}" name="gross" type="number" step="0.01" min="0.01" required value="{{ $old($tName, 'gross', $gross) }}" class="{{ $input_ }}"></div>
                <div><label class="{{ $label }}" for="t-share-{{ $l->id }}">Their share (%)</label><input id="t-share-{{ $l->id }}" name="share" type="number" step="0.01" min="0.01" max="100" required value="{{ $old($tName, 'share', (float) $l->partner_share_rate > 0 ? (float) $l->partner_share_rate : 100) }}" class="{{ $input_ }}"></div>
                <div><label class="{{ $label }}" for="t-days-{{ $l->id }}">Days</label><input id="t-days-{{ $l->id }}" name="days" type="number" min="1" required value="{{ $old($tName, 'days', $l->duration_days) }}" class="{{ $input_ }}"></div>
              </div>
              @if (old('_sheet') === $tName && $errors->has('terms'))<x-staff.alert type="err">{{ $errors->first('terms') }}</x-staff.alert>@endif
              <button type="submit" class="{{ $primaryBtn }}"{!! $l->status !== 'active' ? ' data-confirm="Line #' . $l->id . ' is paid back; this changes its terms and label, not what was paid."' : '' !!}>Save terms</button>
            </form>
            @if ($l->status !== 'active')
              <form method="POST" action="{{ route('staff.partners.lines.payout-source', [$partner, $l]) }}" class="space-y-3 border-t border-gray-100 pt-5 dark:border-gray-800">@csrf<input type="hidden" name="_sheet" value="{{ $tName }}">
                <div><label class="{{ $label }}" for="t-from-{{ $l->id }}">Paid back from</label>
                  <select id="t-from-{{ $l->id }}" name="from_line" class="{{ $input_ }}">
                    <option value="">Our money</option>
                    @foreach (\App\Models\Investment::whereIn('return_type', ['loan_share', 'fixed_return'])->where('status', 'active')->where('id', '!=', $l->id)->orderByDesc('id')->get() as $src)
                      <option value="{{ $src->id }}" @selected((int) $lf?->paid_from_investment_id === $src->id)>#{{ $src->id }} {{ $src->name }}</option>
                    @endforeach
                  </select>
                  @if ($lf?->paid_from_investment_id)<p class="mt-1 text-theme-xs text-gray-500">KES {{ $kes($plines->paidOut($l)) }} came out of line #{{ $lf->paid_from_investment_id }}.</p>@endif
                </div>
                @if (old('_sheet') === $tName && $errors->has('from_line'))<x-staff.alert type="err">{{ $errors->first('from_line') }}</x-staff.alert>@endif
                <button type="submit" class="{{ $ghostBtn }}">Save</button>
              </form>
            @endif
          </x-staff.sheet>
        @endif

        @foreach ($slicesOf->whereIn('id', $settle) as $sl)
          @php $sName = 'settle-' . $sl->id; @endphp
          <x-staff.sheet :name="$sName" :title="'House settles loan #' . $sl->loan_id" :sub="$sl->borrower . ' · KES ' . $kes($sl->amount) . ' of line #' . $l->id" :action="route('staff.partners.slices.settle', [$partner, $sl->id])">
            <div><label class="{{ $label }}" for="s-date-{{ $sl->id }}">Day the house paid</label><input id="s-date-{{ $sl->id }}" type="date" name="date" required max="{{ now()->toDateString() }}" value="{{ $old($sName, 'date') }}" class="{{ $input_ }}"></div>
            <div><label class="{{ $label }}" for="s-int-{{ $sl->id }}">Their interest share (KES)</label><input id="s-int-{{ $sl->id }}" type="number" name="interest" step="0.01" min="0" value="{{ $old($sName, 'interest', app(\App\Services\Partners\PartnerSlices::class)->shareDue($sl)) }}" class="{{ $input_ }}"></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">The house pays {{ $partner->name }} out of this slice and owns it from that day.</div>
            @if (old('_sheet') === $sName)@include('partners._slice-preview')@endif
            @if ($e = $sheetError($sName))<x-staff.alert type="err">{{ $e }}</x-staff.alert>@endif
            <x-slot:footer>
              <button name="mode" value="preview" class="{{ $ghostBtn }}">Preview</button>
              <button type="submit" name="mode" value="save" class="{{ $primaryBtn }}" data-confirm="The house pays {{ $partner->name }} out of this slice and owns it from that day?">House settles</button>
            </x-slot:footer>
          </x-staff.sheet>
        @endforeach
  @endforeach
@endcan

@can('edit partners')
  @if ($lines->isNotEmpty())
    {{-- a line paid for part of a loan (owner, 3 Nov 2026): that part comes out of the house's share --}}
    <x-staff.sheet name="part-of-loan" title="Their money paid for part of a loan" sub="The part comes out of the house's share of the loan; every repayment of the loan is re-split." :action="route('staff.partners.slices.fund', $partner)">
      <div><label class="{{ $label }}" for="pol-line">Line</label>
        <select id="pol-line" name="line" required class="{{ $input_ }}">@foreach ($lines as ['line' => $l, 's' => $s])<option value="{{ $l->id }}" @selected($old('part-of-loan', 'line') == $l->id)>#{{ $l->id }} {{ $l->name }} · KES {{ $kes($s['idle']) }} free</option>@endforeach</select></div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><label class="{{ $label }}" for="pol-loan">Loan #</label><input id="pol-loan" name="loan" type="number" min="1" required value="{{ $old('part-of-loan', 'loan') }}" class="{{ $input_ }}"></div>
        <div><label class="{{ $label }}" for="pol-disb">Disbursement #</label><input id="pol-disb" name="disbursement" type="number" min="1" required value="{{ $old('part-of-loan', 'disbursement') }}" class="{{ $input_ }}"></div>
      </div>
      <div><label class="{{ $label }}" for="pol-amt">Amount (KES)</label><input id="pol-amt" name="amount" type="number" step="0.01" min="1" required value="{{ $old('part-of-loan', 'amount') }}" class="{{ $input_ }}"></div>
      @if (old('_sheet') === 'part-of-loan')@include('partners._slice-preview')@endif
      @if ($e = $sheetError('part-of-loan'))<x-staff.alert type="err">{{ $e }}</x-staff.alert>@endif
      <x-slot:footer>
        <button name="mode" value="preview" class="{{ $ghostBtn }}">Preview</button>
        <button name="mode" value="save" class="{{ $primaryBtn }}">Save</button>
      </x-slot:footer>
    </x-staff.sheet>
  @endif

  {{-- money a partner sent, read from the M-Pesa SMS (owner, 23 Oct 2026); what they earn: a share or a fixed return --}}
  @php $rm = old('_sheet') === 'record-money'; @endphp
  <x-staff.sheet name="record-money" :title="'Record money ' . $partner->name . ' sent'" sub="Paste the M-Pesa message and check what was read" width="max-w-2xl" :action="route('staff.partners.lines.store', $partner)" id="record-capital"
    x-data="disburseFunding({{ \Illuminate\Support\Js::from(['house' => null, 'mode' => 'new', 'maturity' => $rm ? old('maturity', '') : '', 'read' => route('staff.mpesa.read-sms'), 'amount' => $rm ? old('amount', '') : '', 'code' => $rm ? old('code', '') : '', 'date' => $rm ? old('date', '') : '', 'term' => $rm ? old('term', 'short_term') : 'short_term']) }})">
    <div>
      <label for="pl-sms" class="{{ $label }}">Paste the M-Pesa message</label>
      <textarea id="pl-sms" rows="3" x-model="sms" @input.debounce.500ms="read()" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
      <p class="mt-1 text-theme-xs" :class="readOk ? 'text-success-600' : 'text-amber-700 dark:text-amber-400'" x-text="readMsg" x-show="readMsg"></p>
    </div>
    <div class="space-y-5" x-data="{ kind: @js($rm ? old('kind', 'loan_share') : 'loan_share'), gross: 20, cut: 40, rate: @js($rm ? old('fixed_rate', 8) : 8), days: @js($rm ? old('fixed_days', 10) : 10),
        calc() { this.rate = Math.round(this.gross * this.cut) / 100 },
        get ret() { return Math.round((+this.amount || 0) * (+this.rate || 0)) / 100 },
        get due() { const d = new Date((this.date || new Date().toISOString().slice(0, 10)) + 'T00:00:00'); d.setDate(d.getDate() + (+this.days || 0)); return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) },
        kes(n) { return (+n || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) } }">
      <input type="hidden" name="kind" :value="kind">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div><label for="pl-a" class="{{ $label }}">Amount (KES)</label><input id="pl-a" name="amount" type="number" step="0.01" min="1" x-model="amount" required class="{{ $input_ }}"></div>
        <div><label for="pl-c" class="{{ $label }}">Code</label><input id="pl-c" name="code" x-model="code" maxlength="40" required class="{{ $input_ }} uppercase"></div>
        <div><label for="pl-d" class="{{ $label }}">Date received</label><input id="pl-d" name="date" type="date" x-model="date" class="{{ $input_ }}"></div>
      </div>
      <div><span class="{{ $label }}">What they earn</span>
        <div class="flex flex-wrap gap-2 text-theme-sm">
          <button type="button" @click="kind = 'loan_share'" :class="kind === 'loan_share' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">A share of what their loans earn</button>
          <button type="button" @click="kind = 'fixed_return'" :class="kind === 'fixed_return' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">A fixed return</button>
        </div></div>
      {{-- fixed return calculator (owner, 14 Nov 2026): e.g. 20% x 40% = 8% for 10 days; 20% x 20% = 4% for 1 day --}}
      <div x-show="kind === 'fixed_return'" x-cloak class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-white/[0.02]">
        <p class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Return calculator</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">Rate on the money (%)<input type="number" step="0.01" min="0" x-model.number="gross" @input="calc()" class="{{ $input_ }} mt-1"></label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">Their share of it (%)<input name="fixed_share" type="number" step="0.01" min="0" max="100" x-model.number="cut" @input="calc()" class="{{ $input_ }} mt-1"></label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">= Their return (%)<input name="fixed_rate" type="number" step="0.01" min="0" x-model.number="rate" :required="kind === 'fixed_return'" class="{{ $input_ }} mt-1 font-semibold"></label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">For (days)<input name="fixed_days" type="number" min="1" x-model.number="days" :required="kind === 'fixed_return'" class="{{ $input_ }} mt-1"></label>
        </div>
        <p class="mt-3 text-theme-sm text-gray-700 dark:text-gray-300">They get back <b x-text="'KES ' + kes((+amount || 0) + ret)"></b> on <span x-text="due"></span>: <span x-text="'KES ' + kes(amount)"></span> plus <span x-text="'KES ' + kes(ret)"></span> return. Their money can still fund loans; we keep what those loans earn.</p>
      </div>
      <div x-show="kind !== 'fixed_return'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><span class="{{ $label }}">How long</span><input type="hidden" name="term" :value="term">
          <div class="flex gap-2 text-theme-sm">
            <button type="button" @click="term = 'short_term'" :class="term === 'short_term' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Short term</button>
            <button type="button" @click="term = 'long_term'" :class="term === 'long_term' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Long term</button>
          </div></div>
        <div><label for="pl-m" class="{{ $label }}">Pay back by</label><input id="pl-m" name="maturity" type="date" x-model="maturity" :required="kind !== 'fixed_return' && term === 'long_term'" class="{{ $input_ }}">
          <p class="mt-1 text-theme-xs text-gray-500">Needed for long term. Short term closes when its loans are repaid.</p></div>
        {{-- their share on what this money funds (owner, 3 Nov 2026): 60% of interest and 50% of penalty when the client is ours --}}
        @php $defShare = (float) $partner->profit_share_rate > 0 ? (float) $partner->profit_share_rate : (float) config('lending.partner_funding.line_share_rate', 60); @endphp
        <div><label for="pl-sr" class="{{ $label }}">Their share of the interest (%)</label><input id="pl-sr" name="share_rate" type="number" step="0.01" min="0" max="100" value="{{ $rm ? old('share_rate', $defShare) : $defShare }}" class="{{ $input_ }}">
          <p class="mt-1 text-theme-xs text-gray-500">We keep the rest. A broker's client: the broker's rate applies instead.</p></div>
        <div><label for="pl-pr" class="{{ $label }}">Their share of the penalty (%)</label><input id="pl-pr" name="penalty_rate" type="number" step="0.01" min="0" max="100" value="{{ $rm ? old('penalty_rate', config('lending.partner_funding.line_penalty_rate', 50)) : config('lending.partner_funding.line_penalty_rate', 50) }}" class="{{ $input_ }}"></div>
      </div>
      <div><label for="pl-n" class="{{ $label }}">Note (optional)</label><input id="pl-n" name="note" maxlength="500" value="{{ $rm ? old('note') : '' }}" class="{{ $input_ }}"></div>
    </div>
    @if ($e = $sheetError('record-money'))<x-staff.alert type="err">{{ $e }}</x-staff.alert>@endif
    <x-slot:footer><button type="submit" class="{{ $primaryBtn }}">Record the money</button></x-slot:footer>
  </x-staff.sheet>
@endcan
