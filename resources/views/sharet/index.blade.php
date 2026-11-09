{{-- Sharet Pay (9 Nov 2026): the second rail beside the own M-Pesa paybill. Pay links borrowers were given, and money
     sent out through Sharet. docs/SHARET_PAYMENTS.md --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Sharet Pay', 'sub' => 'Repayments through Sharet pay links, and approved disbursements and partner payouts sent through Sharet at 08:00, 12:00 and 16:00. The own paybill works as before.', 'metrics' => false])
  @unless ($available)
    <x-staff.alert type="info" class="mb-6" title="Switched off">Sharet Pay needs its API key and its switch on, on Integrations › Sharet Pay.</x-staff.alert>
  @else
    <p class="mb-6 text-theme-sm text-gray-500 dark:text-gray-400">Borrowers see {{ $rail === 'sharet' ? 'Sharet Pay' : 'the own paybill' }} first. Payouts through Sharet are {{ $payoutsOn ? 'on' : 'off' }}.</p>
  @endunless

  <x-staff.table title="Pay links" caption="Sharet pay links" :headers="['Borrower', ['Amount', 'num'], 'Status', 'Repayment', 'Sharet ref', 'When']">
    @forelse ($links as $l)
      @php [$tone, $label] = match ($l->status) { 'paid' => [$l->repayment_id ? 'green' : 'red', $l->repayment_id ? 'Paid' : 'Paid, not recorded'], 'open' => ['blue', 'Open'], default => ['gray', ucfirst($l->status)] }; @endphp
      <tr>
        <td><x-staff.person :name="$l->loan?->user?->name ?? '—'" :sub="'Loan #' . $l->loan_id" /> <a class="text-theme-xs text-brand-500 hover:underline" href="{{ route('loans.show', $l->loan_id) }}">Open</a></td>
        <td class="num"><x-staff.money :amount="$l->amount" /></td>
        <td class="!whitespace-normal"><x-staff.pill :tone="$tone" dot>{{ $label }}</x-staff.pill>@if ($l->note)<span class="mt-1 block max-w-xs text-theme-xs text-gray-500">{{ $l->note }}</span>@endif</td>
        <td class="text-theme-xs">{{ $l->repayment_id ? '#' . $l->repayment_id : '—' }}</td>
        <td class="font-mono text-theme-xs">{{ $l->sharet_transaction_id ?: $l->sharet_id }}</td>
        <td class="text-theme-xs text-gray-500">{{ $l->created_at->format('j M, H:i') }}</td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="6"><x-staff.empty title="No pay links yet" message="Borrowers get one from their repay screen; staff send one from the loan page." /></td></tr>
    @endforelse
  </x-staff.table>

  <div class="mt-6"></div>
  <x-staff.table title="Payouts" caption="Payouts through Sharet" :headers="['For', ['Amount', 'num'], 'To', 'Status', 'Sharet ref', 'When', '']">
    @forelse ($payouts as $p)
      @php [$tone, $label] = match ($p->status) { 'paid' => ['green', 'Paid'], 'failed' => ['red', 'Failed'], 'paying' => ['blue', 'Sending'], 'queued' => ['amber', 'Next run'],
        'awaiting_approval' => ['amber', $p->kind === 'disbursement' ? 'Waiting for sign-off' : 'Waiting for the withdrawal\'s approval'], default => ['gray', ucfirst($p->status)] }; @endphp
      <tr>
        <td>@if ($p->kind === 'disbursement')<x-staff.person :name="$p->loan?->user?->name ?? '—'" :sub="'Loan #' . $p->loan_id . ' · disbursement'" />@else<x-staff.person :name="$p->partner?->name ?? '—'" :sub="'Partner payout · request #' . $p->partner_request_id" />@endif</td>
        <td class="num"><x-staff.money :amount="$p->amount" /></td>
        <td class="font-mono text-theme-xs">{{ $p->destinationLabel() }}</td>
        <td class="!whitespace-normal"><x-staff.pill :tone="$tone" dot>{{ $label }}</x-staff.pill>@if ($p->failure)<span class="mt-1 block max-w-xs text-theme-xs text-gray-500">{{ $p->failure }}</span>@endif</td>
        <td class="font-mono text-theme-xs">{{ $p->sharet_transaction_id ?: ($p->sharet_payment_id ?: '—') }}</td>
        <td class="text-theme-xs text-gray-500">{{ $p->created_at->format('j M, H:i') }}</td>
        <td class="text-right" data-actions>
          @if ($p->status === 'failed')
            <form method="POST" action="{{ route('sharet.payouts.retry', $p) }}" class="inline">@csrf<button class="text-theme-xs text-brand-500 hover:underline">Try again</button></form>
          @endif
          @if (in_array($p->status, ['awaiting_approval', 'queued', 'failed'], true))
            <form method="POST" action="{{ route('sharet.payouts.cancel', $p) }}" class="inline">@csrf<button class="ml-2 text-theme-xs text-error-500 hover:underline" data-confirm="Cancel this payout? Nothing is sent.">Cancel</button></form>
          @endif
        </td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="7"><x-staff.empty title="No payouts yet" message="With payouts through Sharet on, approved loans show Send through Sharet, and partner withdrawals can be paid through Sharet." /></td></tr>
    @endforelse
  </x-staff.table>
@endsection
