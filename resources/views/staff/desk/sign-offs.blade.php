{{-- Manager · second sign-offs (Stage 4 Phase 5): what other people asked for that needs a second person. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Second sign-offs', 'sub' => 'Money actions another person asked for. You can\'t decide your own.'])
  @unless ($on)
    <x-staff.alert type="warn" class="mb-6">Maker-checker is off (LENDING_APPROVALS_ENABLED): one person can still do these alone.</x-staff.alert>
  @endunless

  @php $labels = \App\Services\Approvals::ACTIONS; $subject = fn ($a) => match ($a->subject_type) {
    'loans' => 'Loan #' . $a->subject_id, 'repayments' => 'Repayment #' . $a->subject_id,
    'debt_recovery_cases' => 'Case #' . $a->subject_id, 'partner_requests' => 'Partner request #' . $a->subject_id, default => $a->subject_type . ' #' . $a->subject_id }; @endphp

  <section class="mb-8">
    @if ($waiting->isEmpty())
      <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="Nothing waiting for you" /></div>
    @else
      <x-staff.table caption="Waiting for a second person" :headers="['What', 'For', ['Amount', 'num'], 'Asked by', 'When', 'Note', 'Decision']">
        @foreach ($waiting as $a)
          @php $p = json_decode((string) $a->payload, true) ?: []; $own = (int) $a->requested_by === (int) auth()->id(); @endphp
          <tr>
            <td class="font-medium">{{ $labels[$a->action]['label'] ?? $a->action }}</td>
            <td>{{ $subject($a) }}</td>
            <td class="num">@if ($a->amount !== null)<x-staff.money :amount="$a->amount" />@endif</td>
            <td>{{ $names[$a->requested_by] ?? '#' . $a->requested_by }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($a->created_at)->diffForHumans() }}</td>
            <td class="text-theme-xs text-gray-500">{{ $p['reason'] ?? '' }}</td>
            <td>
              @if ($own)
                <span class="text-theme-xs text-gray-500">Yours: someone else decides</span>
              @else
                <div class="flex flex-wrap items-center gap-2">
                  <form method="POST" action="{{ route('staff.sign-offs.decide', $a->id) }}">@csrf<input type="hidden" name="decision" value="approve">
                    <button class="rounded-lg bg-brand-500 px-3 py-1.5 text-theme-xs font-medium text-white hover:bg-brand-600">Approve</button></form>
                  <form method="POST" action="{{ route('staff.sign-offs.decide', $a->id) }}" class="flex items-center gap-2">@csrf<input type="hidden" name="decision" value="reject">
                    <input name="reason" required maxlength="500" placeholder="Why not" class="h-9 w-44 rounded-lg border border-gray-300 px-2 text-theme-sm dark:border-gray-700 dark:bg-gray-900" aria-label="Reason">
                    <button class="rounded-lg border border-gray-300 px-3 py-1.5 text-theme-xs font-medium dark:border-gray-700">Reject</button></form>
                </div>
              @endif
            </td>
          </tr>
        @endforeach
      </x-staff.table>
    @endif
  </section>

  <section>
    <h2 class="mb-3 text-theme-lg font-semibold text-gray-800 dark:text-white/90">Recently decided</h2>
    <x-staff.table caption="Decided sign-offs" :headers="['What', 'For', ['Amount', 'num'], 'Asked by', 'Decided by', 'Outcome']">
      @forelse ($decided as $a)
        <tr><td>{{ $labels[$a->action]['label'] ?? $a->action }}</td><td>{{ $subject($a) }}</td>
          <td class="num">@if ($a->amount !== null)<x-staff.money :amount="$a->amount" />@endif</td>
          <td>{{ $names[$a->requested_by] ?? '' }}</td><td>{{ $names[$a->decided_by] ?? '' }}</td>
          <td>{{ ucfirst($a->status) }}{{ $a->reason ? ': ' . $a->reason : '' }}</td></tr>
      @empty
        <tr data-table-skip><td colspan="6" class="text-theme-sm text-gray-500">None yet.</td></tr>
      @endforelse
    </x-staff.table>
  </section>
@endsection
