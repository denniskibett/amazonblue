{{-- A person's loan totals as the standard cards (owner, 22 Oct 2026). Amounts are as recorded; "Cash with us" above
     shows real money only. --}}
<x-ui.metrics class="!mb-8" :items="[
  ['label' => 'Total loans', 'value' => number_format($user->loans->count()), 'icon' => 'briefcase'],
  ['label' => 'Total loaned', 'value' => 'KES ' . number_format((float) $user->loans->sum('amount'), 2), 'icon' => 'banknote'],
  ['label' => 'Total disbursed', 'value' => 'KES ' . number_format((float) $user->disbursements->sum('amount'), 2), 'icon' => 'send'],
  ['label' => 'Total repaid', 'value' => 'KES ' . number_format((float) $user->repayments->sum('amount'), 2), 'icon' => 'hand-coins'],
]" />
