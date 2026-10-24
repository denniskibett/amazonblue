{{-- M-Pesa payouts (owner, 23 Oct 2026): every B2C payout asked for, sent, paid or failed. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'M-Pesa payouts', 'sub' => 'Loan money sent straight to the borrower\'s phone. One person asks, a second approves on Second sign-offs, Safaricom confirms.', 'metrics' => false])
  @unless ($enabled)
    <x-staff.alert type="info" class="mb-6" title="Switched off">Payouts need MPESA_B2C_ENABLED=true, the B2C shortcode, initiator and security credential, and second sign-offs on.</x-staff.alert>
  @endunless
  <x-staff.table title="Payouts" caption="M-Pesa payouts" :headers="['Borrower', ['Amount', 'num'], 'Phone', 'Status', 'Receipt', 'When']">
    @forelse ($rows as $r)
      @php
        [$tone, $label] = match (true) {
          $r->status === 'matched' => ['green', 'Paid'],
          $r->status === 'failed' => ['red', 'Not paid'],
          (bool) $r->conversation_id => ['blue', 'Sent'],
          default => ['amber', 'Waiting for sign-off'],
        };
      @endphp
      <tr>
        <td><x-staff.person :name="$r->borrower ?? '—'" :sub="'Loan #' . $r->loan_id" /> <a class="text-theme-xs text-brand-500 hover:underline" href="{{ route('loans.show', $r->loan_id) }}">Open</a></td>
        <td class="num"><x-staff.money :amount="$r->amount" /></td>
        <td class="font-mono text-theme-xs">{{ $r->msisdn }}</td>
        <td class="!whitespace-normal"><x-staff.pill :tone="$tone" dot>{{ $label }}</x-staff.pill>@if ($r->note)<span class="mt-1 block max-w-xs text-theme-xs text-gray-500">{{ $r->note }}</span>@endif</td>
        <td class="font-mono text-theme-xs">{{ $r->trans_id ?: '—' }}</td>
        <td class="text-theme-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M, H:i') }}</td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="6"><x-staff.empty title="No payouts yet" message="Approved loans show a Send by M-Pesa button once payouts are switched on." /></td></tr>
    @endforelse
  </x-staff.table>
@endsection
