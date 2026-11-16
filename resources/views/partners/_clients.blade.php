{{-- Clients a partner brought in, and their own loans (owner, 16 Nov 2026; was one line of counts above the lines table).
     An own loan pays the full rate; the introducer share comes back to them when the loan is marked as theirs. --}}
@php
  $clientLoans = \App\Models\Loan::with('user:id,name,phone')->where('broker_status', 1)->where('partner_id', $partner->id)
    ->when($partner->user_id, fn ($q) => $q->where('user_id', '!=', $partner->user_id))->orderByDesc('id')->get();
  $ownLoans = $partner->user_id ? \App\Models\Loan::with('user:id,name,phone')->where('user_id', $partner->user_id)->orderByDesc('id')->get() : collect();
  $all = $clientLoans->map(fn ($l) => [$l, 'client'])->concat($ownLoans->map(fn ($l) => [$l, 'own']));
@endphp
<x-staff.table title="Clients they brought in and their own loans" caption="Partner's clients and own loans"
  :headers="['Loan', 'Borrower', 'Kind', ['Amount', 'num'], 'Borrowed on', 'Status']">
  @forelse ($all as [$loan, $kind])
    <tr>
      <td data-sort-value="{{ $loan->id }}"><a class="font-medium text-brand-500 hover:underline" href="{{ route('loans.show', $loan) }}">#{{ $loan->id }}</a></td>
      <td><x-staff.person :user="$loan->user" :sub="$loan->user?->phone" /></td>
      <td data-filter-value="{{ $kind === 'own' ? 'Own loan' : 'Client they brought' }}">
        @if ($kind === 'own')
          <x-staff.pill tone="purple">Own loan</x-staff.pill>
          @if ($loan->broker_status && (int) $loan->partner_id === $partner->id)<span class="block pt-1 text-theme-xs text-success-600">introducer share back</span>
          @else<span class="block pt-1 text-theme-xs text-amber-700 dark:text-amber-400">no introducer share back yet</span>@endif
        @else<x-staff.pill tone="brand">Client they brought</x-staff.pill>@endif
      </td>
      <td class="num" data-sort-value="{{ $loan->amount }}"><x-staff.money :amount="$loan->amount" /></td>
      <td data-sort-value="{{ $loan->borrow_date }}">{{ $loan->borrow_date ? \Illuminate\Support\Carbon::parse($loan->borrow_date)->format('j M Y') : '—' }}</td>
      <td><x-staff.status-badge :status="$loan->status" /></td>
    </tr>
  @empty
    <tr data-table-skip><td colspan="6"><x-staff.empty title="No clients or own loans" message="Loans they introduce, and loans they take themselves, show here." /></td></tr>
  @endforelse
</x-staff.table>
