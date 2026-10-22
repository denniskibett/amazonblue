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

  @php
    $btn = 'inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600';
    $ghost = 'inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300';
    $outTone = ['approved' => 'green', 'rejected' => 'red', 'cancelled' => 'gray', 'expired' => 'gray'];
  @endphp
  <div class="space-y-6">
    <x-staff.table title="Waiting for a second person" caption="Waiting for a second person" :headers="['Asked by', 'What', ['Amount', 'num'], 'Note', 'When', '']">
      @forelse ($waiting as $a)
        @php $p = json_decode((string) $a->payload, true) ?: []; $own = (int) $a->requested_by === (int) auth()->id(); $at = \Illuminate\Support\Carbon::parse($a->created_at); @endphp
        <tr>
          <td data-sort-value="{{ $names[$a->requested_by] ?? '' }}"><x-staff.person :name="$names[$a->requested_by] ?? '#' . $a->requested_by" :id="$a->requested_by" :sub="$own ? 'You' : 'Staff'" /></td>
          <td data-filter-value="{{ $labels[$a->action]['label'] ?? $a->action }}"><span class="font-medium text-gray-800 dark:text-white/90">{{ $labels[$a->action]['label'] ?? $a->action }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $subject($a) }}</span></td>
          <td class="num" data-sort-value="{{ (float) $a->amount }}">@if ($a->amount !== null)<x-staff.money :amount="$a->amount" class="text-gray-800 dark:text-white/90" />@else<span class="text-gray-400">—</span>@endif</td>
          <td class="max-w-[240px] !whitespace-normal text-theme-xs text-gray-600 dark:text-gray-400"><span class="line-clamp-2" title="{{ $p['reason'] ?? '' }}">{{ $p['reason'] ?? '—' }}</span></td>
          <td data-sort-value="{{ $at->timestamp }}">{{ $at->diffForHumans(short: true) }}<span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $at->format('j M, H:i') }}</span></td>
          <td class="text-right" data-actions>
            @if ($own)
              <x-staff.pill tone="gray">Yours: someone else decides</x-staff.pill>
            @else
              <div class="flex justify-end gap-2">
                <form method="POST" action="{{ route('staff.sign-offs.decide', $a->id) }}">@csrf<input type="hidden" name="decision" value="approve"><button class="{{ $btn }}">Approve</button></form>
                <x-staff.confirm id="signoff-no-{{ $a->id }}" :action="route('staff.sign-offs.decide', $a->id)" title="Reject this sign-off?" trigger="Reject" confirm="Reject" tone="danger" :trigger-class="$ghost">
                  {{ $names[$a->requested_by] ?? 'The person who asked' }} sees your reason.
                  <x-slot:fields>
                    <input type="hidden" name="decision" value="reject">
                    <x-staff.field name="reason" id="signoff-reason-{{ $a->id }}" label="Why not" type="textarea" required maxlength="500" />
                  </x-slot:fields>
                </x-staff.confirm>
              </div>
            @endif
          </td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="6"><x-staff.empty title="Nothing waiting for you" /></td></tr>
      @endforelse
    </x-staff.table>

    <x-staff.table title="Recently decided" caption="Decided sign-offs" :headers="['Asked by', 'What', ['Amount', 'num'], 'Decided by', 'Outcome']">
      @forelse ($decided as $a)
        <tr>
          <td data-sort-value="{{ $names[$a->requested_by] ?? '' }}"><x-staff.person :name="$names[$a->requested_by] ?? '—'" :id="$a->requested_by" /></td>
          <td><span class="font-medium text-gray-800 dark:text-white/90">{{ $labels[$a->action]['label'] ?? $a->action }}</span><span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $subject($a) }}</span></td>
          <td class="num">@if ($a->amount !== null)<x-staff.money :amount="$a->amount" class="text-gray-800 dark:text-white/90" />@else<span class="text-gray-400">—</span>@endif</td>
          <td><span class="cursor-pointer hover:text-brand-500" @if ($a->decided_by) data-user-peek="{{ $a->decided_by }}" @endif>{{ $names[$a->decided_by] ?? '—' }}</span></td>
          <td data-filter-value="{{ ucfirst($a->status) }}" class="max-w-[260px] !whitespace-normal"><x-staff.pill :tone="$outTone[$a->status] ?? 'gray'" dot>{{ ucfirst($a->status) }}</x-staff.pill>
            @if ($a->reason)<span class="mt-1 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $a->reason }}</span>@endif</td>
        </tr>
      @empty
        <tr data-table-skip><td colspan="5"><x-staff.empty title="None yet" /></td></tr>
      @endforelse
    </x-staff.table>
  </div>
@endsection
