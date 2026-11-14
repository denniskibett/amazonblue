{{-- A facility's own split (owner, 14 Nov 2026): who put in what, how many cycles of interest count and at what rate,
     the total to collect, how the penalty is shared and what each side already had. The calculator works the figures
     out as you type; "Preview" asks the server, "Save" replaces any earlier split and re-splits from the start day. --}}
@php
  $fs = app(\App\Services\Loans\FacilitySplitter::class);
  $split = $fs->for($loan);
  $status = $split ? $fs->status($split) : null;
  $houseId = \App\Models\Partner::where('is_house_account', true)->orderByRaw("account_type = 'principal' desc")->orderBy('id')->value('id');
  $everyone = \App\Models\Partner::orderByDesc('is_house_account')->orderBy('name')->get(['id', 'name', 'is_house_account']);
  $cashSent = (float) $disbursements->filter(fn ($d) => $d->kind === 'cash')->sum('amount');
  if ($split) {
      $rows = $split->parties->map(fn ($p) => ['partner_id' => $p->partner_id, 'principal' => (float) $p->principal, 'penalty_pct' => (float) $p->penalty_pct, 'received_before' => (float) $p->received_before])->all();
  } else {
      $rows = \Illuminate\Support\Facades\DB::table('loan_fundings')->where('loan_id', $loan->id)->orderBy('id')->get()
          ->map(fn ($f) => ['partner_id' => (int) ($f->partner_id ?: $houseId), 'principal' => (float) $f->amount, 'penalty_pct' => 0, 'received_before' => 0])->all();
      if (count($rows) < 2) {
          $rows = [['partner_id' => $houseId, 'principal' => $cashSent, 'penalty_pct' => 50, 'received_before' => 0], ['partner_id' => null, 'principal' => 0, 'penalty_pct' => 50, 'received_before' => 0]];
      } else {
          $rows = array_map(fn ($r) => $r + ['penalty_pct' => 0], $rows);
          $each = round(100 / count($rows), 2);
          foreach ($rows as $i => $r) { $rows[$i]['penalty_pct'] = $each; }
      }
  }
  $rows = old('parties', $rows);
  $firstRate = (float) ($loan->cycles()->orderBy('cycle_number')->value('interest_rate') ?? 20);
  $init = [
      'parties' => array_values($rows),
      'cycles' => 3, 'rate' => $firstRate, 'compound' => true,
      'target' => (float) old('pi_target', $split?->pi_target ?? 0),
      'cap' => (float) old('total_cap', $split?->total_cap ?? 0),
  ];
  $preview = session('facility_preview');
@endphp
<section class="{{ $card }} lg:col-span-2" x-data="facilitySplit(@js($init))" x-init="if (!target) useCalc()">
  <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div>
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Facility split</h3>
      <p class="text-theme-sm text-gray-500 dark:text-gray-400">For a facility shared with a partner: principal plus interest first, by what each put in (a side that got less earlier catches up), then the penalty by the shares below, up to the total. No broker fee is taken.</p>
    </div>
    @if ($split)
      <x-staff.pill tone="green" dot>On since {{ $split->counted_from->format('j M Y') }}</x-staff.pill>
    @endif
  </div>

  @if ($status)
    <div class="mb-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
      <table class="w-full text-theme-sm">
        <thead class="bg-gray-50 text-left text-theme-xs text-gray-500 dark:bg-white/[0.02]"><tr>
          <th class="px-3 py-2">Party</th><th class="px-3 py-2 text-right">Principal</th><th class="px-3 py-2 text-right">P+I</th><th class="px-3 py-2 text-right">Penalty</th>
          <th class="px-3 py-2 text-right">Final total</th><th class="px-3 py-2 text-right">Had before</th><th class="px-3 py-2 text-right">Since</th><th class="px-3 py-2 text-right">Still to come</th></tr></thead>
        <tbody>
          @foreach ($status['parties'] as $r)
            <tr class="border-t border-gray-100 dark:border-gray-800"><td class="px-3 py-2">{{ $r['name'] }}</td>
              <td class="px-3 py-2 text-right font-outfit">{{ $kes($r['principal']) }}</td><td class="px-3 py-2 text-right font-outfit">{{ $kes($r['pi_entitled']) }}</td>
              <td class="px-3 py-2 text-right font-outfit">{{ $kes($r['penalty_entitled']) }}</td><td class="px-3 py-2 text-right font-outfit font-semibold">{{ $kes($r['entitled']) }}</td>
              <td class="px-3 py-2 text-right font-outfit">{{ $kes($r['received_before']) }}</td><td class="px-3 py-2 text-right font-outfit">{{ $kes($r['received_since']) }}</td>
              <td class="px-3 py-2 text-right font-outfit font-semibold">{{ $kes($r['to_come']) }}</td></tr>
          @endforeach
        </tbody>
      </table>
      <p class="border-t border-gray-100 px-3 py-2 text-theme-xs text-gray-500 dark:border-gray-800">Collected since the start day: KES {{ $kes($status['collected_since']) }} · still to come: KES {{ $kes($status['to_come']) }}{{ $status['in_penalty'] ? ' · now in the penalty part' : '' }}</p>
    </div>
  @endif

  @can('edit partners')
    <form method="POST" action="{{ route('staff.loans.facility-split', $loan) }}" class="space-y-5">@csrf
      {{-- 1 and 5: who put in what, and what each already had --}}
      <div>
        <p class="{{ $label }}">Who put in what</p>
        <div class="space-y-2">
          <template x-for="(p, i) in parties" :key="i">
            <div class="grid grid-cols-2 items-end gap-2 sm:grid-cols-[2fr_1fr_1fr_1fr_auto]">
              <label class="col-span-2 text-theme-xs text-gray-500 sm:col-span-1">Party
                <select :name="`parties[${i}][partner_id]`" x-model.number="p.partner_id" class="{{ $input_ }} mt-1">
                  <option value="">Choose…</option>
                  @foreach ($everyone as $pt)<option value="{{ $pt->id }}">{{ $pt->is_house_account ? 'Aimzone (house)' : $pt->name }}</option>@endforeach
                </select></label>
              <label class="text-theme-xs text-gray-500">Put in (KES)<input :name="`parties[${i}][principal]`" type="number" step="0.01" min="0" x-model.number="p.principal" class="{{ $input_ }} mt-1"></label>
              <label class="text-theme-xs text-gray-500">Penalty share (%)<input :name="`parties[${i}][penalty_pct]`" type="number" step="0.01" min="0" max="100" x-model.number="p.penalty_pct" class="{{ $input_ }} mt-1"></label>
              <label class="text-theme-xs text-gray-500">Already had (KES)<input :name="`parties[${i}][received_before]`" type="number" step="0.01" min="0" x-model.number="p.received_before" class="{{ $input_ }} mt-1"></label>
              <button type="button" @click="parties.splice(i, 1)" x-show="parties.length > 2" class="h-11 rounded-lg px-3 text-theme-sm text-gray-500 hover:text-error-600" aria-label="Remove this party">✕</button>
            </div>
          </template>
        </div>
        <button type="button" @click="parties.push({ partner_id: null, principal: 0, penalty_pct: 0, received_before: 0 })" class="mt-2 text-theme-sm font-medium text-brand-500 hover:underline">+ Add a party</button>
        <p class="mt-1 text-theme-xs" :class="Math.abs(penSum - 100) < 0.01 ? 'text-gray-500' : 'text-error-600'" x-text="'Penalty shares add up to ' + penSum + '% (must be 100%) · principal KES ' + kes(principal) + ' (cash sent on the loan: KES {{ $kes($cashSent) }})'"></p>
      </div>

      {{-- 2 and 3: the calculator --}}
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-white/[0.02]">
        <p class="mb-3 text-theme-sm font-semibold text-gray-800 dark:text-white/90">Calculator</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">Cycles of interest<input type="number" min="0" max="60" x-model.number="cycles" @input="useCalc()" class="{{ $input_ }} mt-1"></label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">Rate a cycle (%)<input type="number" step="0.01" min="0" x-model.number="rate" @input="useCalc()" class="{{ $input_ }} mt-1"></label>
          <label class="col-span-2 flex items-center gap-2 self-end pb-3 text-theme-sm text-gray-700 dark:text-gray-300"><input type="checkbox" x-model="compound" @change="useCalc()" class="size-4 rounded border-gray-300"> Each cycle's interest is added to the balance</label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">= Principal + interest (KES)<input name="pi_target" type="number" step="0.01" min="1" x-model.number="target" required class="{{ $input_ }} mt-1 font-semibold"></label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">Total to collect (KES)<input name="total_cap" type="number" step="0.01" min="1" x-model.number="cap" required class="{{ $input_ }} mt-1 font-semibold"></label>
          <label class="text-theme-xs text-gray-600 dark:text-gray-400">Counts from<input name="counted_from" type="date" required value="{{ old('counted_from', $split?->counted_from?->toDateString() ?? now()->toDateString()) }}" class="{{ $input_ }} mt-1"></label>
          <div class="text-theme-xs text-gray-600 dark:text-gray-400">Penalty part<span class="mt-1 block h-11 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" x-text="'KES ' + kes(penaltyBand)"></span></div>
        </div>
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-theme-xs">
            <thead class="text-left text-gray-500"><tr><th class="py-1">Party</th><th class="py-1 text-right">P+I</th><th class="py-1 text-right">Penalty</th><th class="py-1 text-right">Final total</th><th class="py-1 text-right">Still to come</th></tr></thead>
            <tbody>
              <template x-for="(r, i) in rows" :key="i">
                <tr class="border-t border-gray-200 dark:border-gray-700"><td class="py-1" x-text="r.name"></td><td class="py-1 text-right font-outfit" x-text="kes(r.pi)"></td>
                  <td class="py-1 text-right font-outfit" x-text="kes(r.pen)"></td><td class="py-1 text-right font-outfit font-semibold" x-text="kes(r.total)"></td><td class="py-1 text-right font-outfit" x-text="kes(r.toCome)"></td></tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label class="text-theme-xs text-gray-600 dark:text-gray-400">Note<input name="note" maxlength="500" value="{{ old('note', $split?->notes) }}" placeholder="What was agreed, and with whom" class="{{ $input_ }} mt-1"></label>
        @if ($loan->broker_status)
          <label class="flex items-center gap-2 self-end pb-3 text-theme-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="drop_broker" value="1" class="size-4 rounded border-gray-300"> It is a partner split: stop the broker fee and reverse the ones already taken</label>
        @endif
      </div>

      @error('facility')<div class="rounded-lg bg-error-50 p-3 text-theme-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">{{ $message }}</div>@enderror
      @if ($preview)
        <div class="rounded-lg border border-brand-200 bg-brand-50/50 p-3 text-theme-sm dark:border-brand-500/30 dark:bg-brand-500/10">
          <p class="mb-1 font-semibold">Preview (nothing saved)</p>
          @foreach ($preview['parties'] as $r)<p>{{ $r['name'] }}: final KES {{ $kes($r['entitled']) }}, still to come KES {{ $kes($r['to_come']) }}</p>@endforeach
          <p class="text-theme-xs text-gray-500">Still to come in all: KES {{ $kes($preview['to_come']) }}</p>
        </div>
      @endif

      <div class="flex flex-wrap gap-2">
        <button name="mode" value="preview" class="rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Preview</button>
        <button name="mode" value="save" class="rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600" data-confirm="Save this split for loan #{{ $loan->id }}? Payments from the start day are re-split.">Save the split</button>
      </div>
    </form>
    @if ($split)
      <form method="POST" action="{{ route('staff.loans.facility-split.destroy', $loan) }}" class="mt-3">@csrf @method('DELETE')
        <button class="text-theme-sm text-error-600 hover:underline" data-confirm="Remove the facility split? Payments from {{ $split->counted_from->format('j M Y') }} go back to the normal split." data-confirm-danger>Remove the facility split</button>
      </form>
    @endif
  @endcan
</section>

@once
<script>
  function facilitySplit(init) {
    const names = @js($everyone->mapWithKeys(fn ($p) => [$p->id => $p->is_house_account ? 'Aimzone (house)' : $p->name]));
    return {
      ...init,
      get principal() { return this.parties.reduce((s, p) => s + (+p.principal || 0), 0) },
      get received() { return this.parties.reduce((s, p) => s + (+p.received_before || 0), 0) },
      get penSum() { return Math.round(this.parties.reduce((s, p) => s + (+p.penalty_pct || 0), 0) * 100) / 100 },
      get penaltyBand() { return Math.max(0, (+this.cap || 0) - Math.max(+this.target || 0, this.received)) },
      useCalc() {
        const r = (+this.rate || 0) / 100, n = +this.cycles || 0;
        const t = this.compound ? this.principal * Math.pow(1 + r, n) : this.principal * (1 + r * n);
        this.target = Math.round(t * 100) / 100;
      },
      get rows() {
        return this.parties.filter(p => p.partner_id).map(p => {
          const pi = this.principal > 0 ? (+this.target || 0) * (+p.principal || 0) / this.principal : 0;
          const pen = this.penaltyBand * (+p.penalty_pct || 0) / 100;
          const total = pi + pen;
          return { name: names[p.partner_id] || ('#' + p.partner_id), pi, pen, total, toCome: total - (+p.received_before || 0) };
        });
      },
      kes(n) { return (+n || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    };
  }
</script>
@endonce
