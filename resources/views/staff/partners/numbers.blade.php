{{-- Every partner's numbers and loans (owner, 15 Nov 2026): what they put in, earned (interest, penalty shares,
     introducer fees), got back and were paid; what they hold now and have out on loans; the loans they are in. --}}
@extends('layouts.app')

@php
  $kes = fn ($n) => number_format((float) $n, 2);
  $partners = $rows->reject(fn ($r) => $r->partner->is_house_account);
  $house = $rows->first(fn ($r) => $r->partner->is_house_account);
  $total = fn ($k) => $partners->sum($k);
  $cols = ['put_in' => 'Put in', 'interest' => 'Interest', 'penalties' => 'Penalties', 'fees' => 'Introducer fees', 'principal_back' => 'Principal back',
      'paid_out' => 'Paid out', 'held' => 'Held now', 'out_on_loans' => 'Out on loans'];
  $roleTone = ['introduced' => 'purple', 'funded' => 'blue', 'funded part' => 'blue', 'facility share' => 'amber', 'earned on' => 'gray'];
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Partner numbers', 'sub' => 'What each partner put in, earned and was paid, what they hold now, and the loans they are in. From the partner journal.', 'metrics' => false,
    'actions' => '<a href="' . route('partners.index') . '" class="text-theme-sm font-medium text-brand-500 hover:underline">← Partners</a>'])

  <x-ui.metrics :items="[
    ['label' => 'Put in by partners', 'value' => 'KES ' . $kes($total('put_in')), 'icon' => 'arrow-down', 'hint' => 'principal they sent'],
    ['label' => 'Earned by partners', 'value' => 'KES ' . $kes($total('interest') + $total('penalties') + $total('fees')), 'icon' => 'trending-up', 'hint' => 'interest, penalties and fees'],
    ['label' => 'Paid out to partners', 'value' => 'KES ' . $kes($total('paid_out')), 'icon' => 'send', 'hint' => 'capital and earnings sent'],
    ['label' => 'Held for partners now', 'value' => 'KES ' . $kes($total('held')), 'icon' => 'wallet', 'hint' => 'owed to them, not yet paid'],
  ]" />

  <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[1100px] text-theme-sm">
        <thead class="border-b border-gray-100 bg-gray-50 text-left text-theme-xs text-gray-500 dark:border-gray-800 dark:bg-white/[0.02]">
          <tr><th class="px-4 py-3">Partner</th>@foreach ($cols as $c)<th class="px-3 py-3 text-right">{{ $c }}</th>@endforeach<th class="px-4 py-3 text-right">Loans</th></tr>
        </thead>
        @foreach ($partners as $r)
          <tbody x-data="{ open: false }" class="border-b border-gray-100 dark:border-gray-800">
            <tr class="cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.02]" @click="open = !open">
              <td class="px-4 py-3"><a href="{{ route('partners.show', $r->partner) }}" class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" @click.stop>{{ $r->partner->name }}</a>
                <span class="block text-theme-xs text-gray-500">{{ ucfirst($r->partner->type ?? 'partner') }}{{ $r->partner->user_id ? ' · has a login' : ' · no login' }}</span></td>
              @foreach (array_keys($cols) as $k)
                <td class="px-3 py-3 text-right font-outfit {{ $k === 'held' && $r->held > 0 ? 'font-semibold text-gray-800 dark:text-white/90' : 'text-gray-600 dark:text-gray-400' }}">{{ (float) $r->$k ? $kes($r->$k) : '—' }}</td>
              @endforeach
              <td class="px-4 py-3 text-right"><span class="inline-flex items-center gap-1 font-medium text-brand-500">{{ $r->loans }} <x-ui.icon name="chevron-down" class="size-4 transition" ::class="open && 'rotate-180'" /></span></td>
            </tr>
            <tr x-show="open" x-cloak>
              <td colspan="{{ count($cols) + 2 }}" class="bg-gray-50/60 px-4 py-3 dark:bg-white/[0.02]">
                @php $ls = $loans[$r->partner->id] ?? collect(); @endphp
                @if ($ls->isEmpty())
                  <p class="text-theme-xs text-gray-500">Not in any loan yet.</p>
                @else
                  <table class="w-full text-theme-xs">
                    <thead class="text-left text-gray-500"><tr><th class="py-1">Loan</th><th>Borrower</th><th>How they are in it</th><th class="text-right">Amount</th><th>Status</th><th class="text-right">They earned on it</th></tr></thead>
                    <tbody>
                      @foreach ($ls as $x)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                          <td class="py-1.5"><a class="font-medium text-brand-500 hover:underline" href="{{ route('users.loans.show', [$x->loan->user_id, $x->loan->id]) }}">#{{ $x->loan->id }}</a></td>
                          <td>{{ $x->loan->user->name ?? '—' }}</td>
                          <td>@foreach ($x->roles as $role)<x-staff.pill :tone="$roleTone[$role] ?? 'gray'">{{ $role }}</x-staff.pill> @endforeach</td>
                          <td class="text-right font-outfit">{{ $kes($x->loan->original_amount ?: $x->loan->amount) }}</td>
                          <td>{{ ucfirst(str_replace('_', ' ', $x->loan->status)) }}{{ $x->loan->deleted_at ? ' (deleted)' : '' }}</td>
                          <td class="text-right font-outfit">{{ $x->earned ? $kes($x->earned) : '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                @endif
              </td>
            </tr>
          </tbody>
        @endforeach
        <tfoot class="bg-gray-50 font-semibold dark:bg-white/[0.02]">
          <tr><td class="px-4 py-3">All partners</td>@foreach (array_keys($cols) as $k)<td class="px-3 py-3 text-right font-outfit">{{ $kes($total($k)) }}</td>@endforeach<td class="px-4 py-3 text-right">{{ $partners->sum('loans') }}</td></tr>
          @if ($house)
            <tr class="text-gray-500"><td class="px-4 py-3 font-normal">{{ $house->partner->name }} (our share)</td>@foreach (array_keys($cols) as $k)<td class="px-3 py-3 text-right font-outfit font-normal">{{ (float) $house->$k ? $kes($house->$k) : '—' }}</td>@endforeach<td class="px-4 py-3 text-right font-normal">{{ $house->loans }}</td></tr>
          @endif
        </tfoot>
      </table>
    </div>
  </div>
  <p class="mt-3 text-theme-xs text-gray-500">Interest, penalties and introducer fees are their shares of what borrowers paid. Held now is what the journal says is still theirs (earned or put in, not yet paid out). A loan shows once however many ways the partner is in it; click a row for the loans.</p>
@endsection
