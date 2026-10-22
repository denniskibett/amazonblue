{{-- Cashier · M-Pesa payments (Stage 4 Phase 4): unmatched payments to assign, borrowers' claims to confirm, today's
     matched payments, and the statement reconciliation. Every money move goes through Services\Mpesa\PaymentRecorder. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'M-Pesa payments', 'sub' => 'Payments M-Pesa reported that need a person, borrowers\' "I\'ve paid" claims, and the statement check.'])

  @unless ($mpesaOn)
    <x-staff.alert type="warn" class="mb-6">Automatic recording is off (MPESA_ENABLED). Payments Safaricom reports are kept here for you to assign.</x-staff.alert>
  @endunless

  @php
    $btn = 'inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600';
    $ghost = 'inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
    $field = 'h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 focus:border-brand-300 focus:outline-none focus:ring focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  @endphp

  <div class="space-y-6">
    {{-- 1. unmatched --}}
    <x-staff.table title="Not matched to a loan" caption="Unmatched M-Pesa payments" :headers="['Receipt', ['Amount', 'num'], 'Account no. · phone', 'Why', 'Record on loan']">
      @forelse ($unmatched as $t)
        @php $at = $t->trans_time ?? $t->created_at; @endphp
        <tr>
          <td data-sort-value="{{ optional($at)->timestamp }}"><span class="font-mono font-medium text-gray-800 dark:text-white/90">{{ $t->trans_id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ optional($at)->format('j M, H:i') }}</span></td>
          <td class="num" data-sort-value="{{ (float) $t->amount }}"><x-staff.money :amount="$t->amount" class="text-gray-800 dark:text-white/90" /></td>
          <td><span class="text-gray-800 dark:text-white/90">{{ $t->bill_ref ?: '—' }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $t->maskedMsisdn() ?: 'no phone' }}</span></td>
          <td class="max-w-[220px] !whitespace-normal"><x-staff.pill tone="amber">{{ ucfirst($t->status) }}</x-staff.pill><span class="mt-1 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $t->note }}</span></td>
          <td>
            <form method="POST" action="{{ route('staff.payments.assign', $t) }}" class="flex items-center gap-2">
              @csrf
              <input name="loan_id" type="number" min="1" required placeholder="Loan #" value="{{ preg_replace('/\D/', '', (string) $t->bill_ref) }}" class="{{ $field }} w-24" aria-label="Loan number for {{ $t->trans_id }}">
              <button class="{{ $btn }}">Record</button>
            </form>
          </td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="Nothing waiting" message="Every M-Pesa payment is matched to a loan." /></td></tr>
      @endforelse
    </x-staff.table>

    {{-- 2. claims --}}
    <x-staff.table title="Borrowers' “I've paid” claims" caption="Payment claims being checked" :headers="['Borrower', 'M-Pesa code', ['Amount', 'num'], 'Told us', '']">
      @forelse ($claims as $c)
        <tr>
          <td data-sort-value="{{ $c->user->name ?? '' }}"><x-staff.person :user="$c->user" :sub="'Loan #' . $c->loan_id" /></td>
          <td class="font-mono font-medium text-gray-800 dark:text-white/90">{{ $c->code }}</td>
          <td class="num" data-sort-value="{{ (float) $c->amount }}"><x-staff.money :amount="$c->amount" class="text-gray-800 dark:text-white/90" /></td>
          <td data-sort-value="{{ $c->created_at->timestamp }}"><span>{{ $c->created_at->diffForHumans(short: true) }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $c->created_at->format('j M, H:i') }}</span></td>
          <td class="text-right" data-actions>
            <div class="flex justify-end gap-2">
              <form method="POST" action="{{ route('staff.payments.claim', $c) }}">@csrf<input type="hidden" name="decision" value="match">
                <button class="{{ $btn }}">Match now</button></form>
              <x-staff.confirm id="claim-no-{{ $c->id }}" :action="route('staff.payments.claim', $c)" title="Code {{ $c->code }} not found?" trigger="Not found" confirm="Reject claim" tone="danger" :trigger-class="$ghost">
                The borrower sees your reason on their account.
                <x-slot:fields>
                  <input type="hidden" name="decision" value="reject">
                  <x-staff.field name="reason" id="claim-reason-{{ $c->id }}" label="Why (the borrower sees it)" type="textarea" required maxlength="200" />
                </x-slot:fields>
              </x-staff.confirm>
            </div>
          </td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="No claims waiting" /></td></tr>
      @endforelse
    </x-staff.table>

    {{-- 3. the M-Pesa statement: upload and results in one card --}}
    @php
      $issues = collect();
      if ($recon) {
        foreach (['on_statement_not_recorded' => ['On the statement, not recorded', 'red'], 'amount_differs' => ['Amount differs', 'amber'], 'recorded_not_on_statement' => ['Recorded, not on the statement', 'purple']] as $key => [$label, $tone]) {
          foreach ($recon[$key] as $row) {
            $issues->push(['label' => $label, 'tone' => $tone, 'row' => $row]);
          }
        }
      }
    @endphp
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-col gap-4 px-4 pt-4 pb-4 sm:flex-row sm:items-end sm:justify-between sm:px-6">
        <div>
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Check against the M-Pesa statement</h3>
          <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Upload the organisation statement (CSV). Each receipt is checked against what we recorded.</p>
        </div>
        <form method="POST" action="{{ route('staff.payments.reconcile') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2" data-no-swap>
          @csrf
          <label for="statement" class="sr-only">M-Pesa organisation statement (CSV)</label>
          <input id="statement" name="statement" type="file" accept=".csv,text/csv" required
            class="h-10 rounded-lg border border-gray-300 text-theme-sm text-gray-600 file:mr-3 file:h-full file:border-0 file:border-r file:border-gray-200 file:bg-gray-50 file:px-3 file:text-theme-sm file:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400">
          <button class="h-10 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600">Check</button>
        </form>
      </div>
      @if ($recon)
        <div class="grid grid-cols-2 gap-px border-y border-gray-100 bg-gray-100 dark:border-gray-800 dark:bg-gray-800 sm:grid-cols-4">
          @foreach ([['On the statement', $recon['statement_rows'], 'gray'], ['Recorded exactly', $recon['ok'], 'green'], ['Not recorded', count($recon['on_statement_not_recorded']), 'red'], ['Differ or missing', count($recon['amount_differs']) + count($recon['recorded_not_on_statement']), 'amber']] as [$l, $n, $tone])
            <div class="bg-white px-4 py-3 dark:bg-gray-900 sm:px-6"><p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $l }}</p><p class="font-outfit text-xl font-semibold text-gray-800 dark:text-white/90">{{ $n }}</p></div>
          @endforeach
        </div>
        <div class="px-4 pb-3 sm:px-6">
          <p class="py-3 text-theme-xs text-gray-500 dark:text-gray-400">{{ $recon['from'] ? 'Statement covers ' . $recon['from'] . ' to ' . $recon['to'] . '.' : '' }}</p>
          @if ($issues->isEmpty())
            <x-staff.empty title="Everything matches" message="Every receipt on the statement is recorded with the same amount." />
          @else
            <div class="max-w-full overflow-x-auto">
              <table class="w-full min-w-[640px] text-left text-theme-sm text-gray-700 dark:text-gray-300 [&_td]:py-3 [&_td]:pr-4">
                <caption class="sr-only">Statement differences</caption>
                <thead class="border-y border-gray-100 dark:border-gray-800"><tr>
                  @foreach (['Issue', 'Receipt', 'Details'] as $h)<th scope="col" class="py-3 pr-4 text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ $h }}</th>@endforeach
                </tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  @foreach ($issues as $i)
                    <tr>
                      <td class="whitespace-nowrap"><x-staff.pill :tone="$i['tone']" dot>{{ $i['label'] }}</x-staff.pill></td>
                      <td class="whitespace-nowrap font-mono font-medium text-gray-800 dark:text-white/90">{{ $i['row']['receipt'] ?? '—' }}</td>
                      <td class="text-theme-xs text-gray-600 dark:text-gray-400">
                        @foreach (collect($i['row'])->except('receipt') as $k => $v)
                          <span class="mr-3 inline-block"><span class="text-gray-400">{{ str_replace('_', ' ', $k) }}</span> {{ is_numeric($v) && in_array($k, ['amount', 'statement', 'recorded'], true) ? number_format((float) $v, 2) : $v }}</span>
                        @endforeach
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      @endif
    </section>

    {{-- 4. matched --}}
    <x-staff.table title="Recently matched" caption="Matched M-Pesa payments" :headers="['Receipt', 'Type', ['Amount', 'num'], 'Loan · repayment', 'How']">
      @forelse ($matched as $t)
        <tr>
          <td><span class="font-mono font-medium text-gray-800 dark:text-white/90">{{ $t->trans_id }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ optional($t->updated_at)->format('j M, H:i') }}</span></td>
          <td><x-staff.pill tone="green">{{ strtoupper($t->type) }}</x-staff.pill></td>
          <td class="num" data-sort-value="{{ (float) $t->amount }}"><x-staff.money :amount="$t->amount" class="text-gray-800 dark:text-white/90" /></td>
          <td>Loan #{{ $t->loan_id }}<span class="block text-theme-xs text-gray-500 dark:text-gray-400">Repayment #{{ $t->repayment_id }}</span></td>
          <td class="max-w-[260px] !whitespace-normal text-theme-xs text-gray-500 dark:text-gray-400">{{ $t->note }}</td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="None yet" /></td></tr>
      @endforelse
    </x-staff.table>
  </div>
@endsection
