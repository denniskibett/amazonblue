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
<div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  <div class="border-b border-gray-200 p-4 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Money, clients and own loans</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      {{ $lines->where('line.status', 'active')->count() }} open line(s) ·
      {{ $clients }} client loan(s) brought in ·
      {{ $ownLoans->count() }} own loan(s){{ $ownLoans->count() ? ', ' . $ownBack . ' getting the introducer share back' : '' }}
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
          {{-- where this line's money went (owner, 3 Nov 2026): each slice, who paid it back, and the house settling one --}}
          @php
            $slicesOf = \Illuminate\Support\Facades\DB::table('loan_fundings as f')->join('loans as l', 'l.id', '=', 'f.loan_id')->join('users as u', 'u.id', '=', 'l.user_id')
              ->leftJoin('disbursements as d', 'd.id', '=', 'f.disbursement_id')->where('f.investment_id', $l->id)->orderBy('f.id')
              ->get(['f.*', 'l.status as loan_status', 'u.name as borrower', 'd.disburse_date', 'd.transaction as d_ref']);
            $multi = \Illuminate\Support\Facades\DB::table('loan_fundings')->whereIn('loan_id', $slicesOf->pluck('loan_id'))->groupBy('loan_id')->havingRaw('count(*) > 1')->pluck('loan_id')->all();
          @endphp
          @foreach ($slicesOf as $sl)
            <tr class="border-b border-gray-50 bg-gray-50/60 text-theme-xs dark:border-gray-800 dark:bg-white/[0.02]">
              <td class="px-4 py-1.5 pl-8 text-gray-600 dark:text-gray-400" colspan="2">↳ <a class="text-brand-500 hover:underline" href="{{ route('loans.show', $sl->loan_id) }}">#{{ $sl->loan_id }}</a> {{ $sl->borrower }}
                @if ($sl->disbursement_id)<span class="text-gray-400">· disb. #{{ $sl->disbursement_id }} {{ $sl->d_ref }} {{ $sl->disburse_date ? \Illuminate\Support\Carbon::parse($sl->disburse_date)->format('j M') : '' }}</span>@endif</td>
              <td class="px-4 py-1.5 text-right font-outfit">{{ $kes($sl->amount) }}</td>
              <td class="px-4 py-1.5" colspan="3">
                @if ($sl->settled_on ?? null)<x-staff.pill tone="green">House settled {{ \Illuminate\Support\Carbon::parse($sl->settled_on)->format('j M') }} · KES {{ $kes($sl->settled_interest) }} interest</x-staff.pill>
                @elseif (in_array($sl->loan_status, \App\Models\Loan::STATUS_FINAL_LIST, true))<x-staff.pill tone="green">Paid back by the borrower</x-staff.pill>
                @else<x-staff.pill tone="blue">Out on the loan</x-staff.pill>@endif
              </td>
              <td class="px-4 py-1.5" colspan="2">
                @if (! ($sl->settled_on ?? null) && ! in_array($sl->loan_status, \App\Models\Loan::STATUS_FINAL_LIST, true) && in_array($sl->loan_id, $multi))
                  @can('edit partners')
                    <form method="POST" action="{{ route('staff.partners.slices.settle', [$partner, $sl->id]) }}" class="flex flex-wrap items-center gap-1.5">@csrf
                      <input type="date" name="date" required max="{{ now()->toDateString() }}" aria-label="Day the house paid" class="h-8 rounded-md border border-gray-300 px-1.5 dark:border-gray-700 dark:bg-gray-900">
                      <input type="number" name="interest" step="0.01" min="0" value="{{ app(\App\Services\Partners\PartnerSlices::class)->shareDue($sl) }}" aria-label="Their interest share" class="h-8 w-24 rounded-md border border-gray-300 px-1.5 dark:border-gray-700 dark:bg-gray-900">
                      <button name="mode" value="preview" class="h-8 rounded-md border border-gray-300 px-2 dark:border-gray-700">Preview</button>
                      <button name="mode" value="save" class="h-8 rounded-md bg-brand-500 px-2 text-white" data-confirm="The house pays {{ $partner->name }} out of this slice and owns it from that day?">House settles</button>
                    </form>
                  @endcan
                @endif
              </td>
            </tr>
          @endforeach
        @empty
          <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No money recorded as lines yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if (session('slice_preview'))
    <div class="border-t border-gray-200 p-4 dark:border-gray-700">
      <h4 class="mb-2 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Preview: the journal rows this would post (nothing saved)</h4>
      <table class="w-full text-theme-xs"><tbody>
        @forelse (session('slice_preview') as [$n, $w, $t, $amt, $loanId, $day, $ref])
          <tr class="border-t border-gray-100 dark:border-gray-800"><td class="py-1">{{ $n }}</td><td>{{ $w }}</td><td>{{ $t }}</td><td class="text-right font-outfit">{{ $kes($amt) }}</td><td>{{ $loanId ? '#' . $loanId : '' }}</td><td>{{ $day }}</td><td class="font-mono">{{ $ref }}</td></tr>
        @empty
          <tr><td class="py-1 text-gray-500">Nothing to post.</td></tr>
        @endforelse
      </tbody></table>
    </div>
  @endif

  @can('edit partners')
    @if ($lines->isNotEmpty())
      {{-- a line paid for part of a loan (owner, 3 Nov 2026): that part comes out of the house's share --}}
      <form method="POST" action="{{ route('staff.partners.slices.fund', $partner) }}" class="grid grid-cols-1 items-end gap-3 border-t border-gray-200 p-4 dark:border-gray-700 sm:grid-cols-6">@csrf
        <div class="sm:col-span-6"><h4 class="text-theme-sm font-semibold text-gray-800 dark:text-white/90">Their money paid for part of a loan</h4>
          <p class="text-theme-xs text-gray-500">The part comes out of the house's share of the loan; every repayment of the loan is re-split.</p></div>
        <label class="text-theme-xs text-gray-600 dark:text-gray-400 sm:col-span-2">Line<select name="line" required class="{{ $input_ }} mt-1">@foreach ($lines as ['line' => $l])<option value="{{ $l->id }}" @selected(old('line') == $l->id)>#{{ $l->id }} {{ $l->name }}</option>@endforeach</select></label>
        <label class="text-theme-xs text-gray-600 dark:text-gray-400">Loan #<input name="loan" type="number" min="1" required value="{{ old('loan') }}" class="{{ $input_ }} mt-1"></label>
        <label class="text-theme-xs text-gray-600 dark:text-gray-400">Disbursement #<input name="disbursement" type="number" min="1" required value="{{ old('disbursement') }}" class="{{ $input_ }} mt-1"></label>
        <label class="text-theme-xs text-gray-600 dark:text-gray-400">Amount (KES)<input name="amount" type="number" step="0.01" min="1" required value="{{ old('amount') }}" class="{{ $input_ }} mt-1"></label>
        <div class="flex gap-2"><button name="mode" value="preview" class="h-11 rounded-lg border border-gray-300 px-3 text-theme-sm dark:border-gray-700">Preview</button>
          <button name="mode" value="save" class="h-11 rounded-lg bg-brand-500 px-3 text-theme-sm font-medium text-white">Save</button></div>
      </form>
    @endif
    <form id="record-capital" method="POST" action="{{ route('staff.partners.lines.store', $partner) }}" class="space-y-4 border-t border-gray-200 p-4 dark:border-gray-700"
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
        {{-- their share on what this money funds (owner, 3 Nov 2026): 60% of interest and 50% of penalty when the client is ours --}}
        @php $defShare = (float) $partner->profit_share_rate > 0 ? (float) $partner->profit_share_rate : (float) config('lending.partner_funding.line_share_rate', 60); @endphp
        <div><label for="pl-sr" class="{{ $label }}">Their share of the interest (%)</label><input id="pl-sr" name="share_rate" type="number" step="0.01" min="0" max="100" value="{{ old('share_rate', $defShare) }}" class="{{ $input_ }}">
          <p class="mt-1 text-theme-xs text-gray-500">We keep the rest. A broker's client: the broker's rate applies instead.</p></div>
        <div><label for="pl-pr" class="{{ $label }}">Their share of the penalty (%)</label><input id="pl-pr" name="penalty_rate" type="number" step="0.01" min="0" max="100" value="{{ old('penalty_rate', config('lending.partner_funding.line_penalty_rate', 50)) }}" class="{{ $input_ }}"></div>
      </div>
      @if ($errors->any())<div class="rounded-lg bg-error-50 p-3 text-theme-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">{{ $errors->first() }}</div>@endif
      <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Record the money</button>
    </form>
  @endcan
</div>
