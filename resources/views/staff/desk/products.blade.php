{{-- Admin · products & pricing: loan_types. Editable on engine v2 (cycles keep their own terms); every edit keeps the old values (loan_type_versions). --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Loan products', 'sub' => 'The loan types borrowers and staff can choose, as they are set today.'])

  @if ($products->isEmpty())
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"><x-staff.empty title="No loan products" /></div>
  @else
    <x-staff.table caption="Loan products" :headers="['Product', ['Interest', 'num'], ['Penalty', 'num'], 'Period', ['Grace (days)', 'num'], ['Default after (days)', 'num'], ['Open loans', 'num'], 'Description']">
      @foreach ($products as $p)
        <tr>
          <td class="font-medium text-gray-800 dark:text-white/90">{{ $p->name }}</td>
          <td class="num">{{ rtrim(rtrim(number_format($p->interest_rate, 2), '0'), '.') }}%</td>
          <td class="num">{{ rtrim(rtrim(number_format($p->penalty_rate, 2), '0'), '.') }}%</td>
          <td>{{ $p->period }} {{ $p->unit }}</td>
          <td class="num">{{ $p->grace_period_days }}</td>
          <td class="num">{{ $p->default_threshold_days }}</td>
          <td class="num">{{ $openLoans[$p->id] ?? 0 }}</td>
          <td class="max-w-[320px] !whitespace-normal text-theme-xs text-gray-500">{{ $p->description ?: '—' }}
            @if (($versions[$p->id] ?? 0) > 0)<span class="block">{{ $versions[$p->id] }} earlier version(s) kept</span>@endif
            @if ($editable)
              <details class="mt-2"><summary class="cursor-pointer font-medium text-brand-500">Edit</summary>
                <form method="POST" action="{{ route('staff.products.update', $p) }}" class="mt-2 grid grid-cols-2 gap-2">
                  @csrf
                  @foreach (['name' => 'Name', 'interest_rate' => 'Interest %', 'penalty_rate' => 'Penalty % a day', 'period' => 'Period', 'grace_period_days' => 'Grace days', 'default_threshold_days' => 'Default after (days)'] as $f => $l)
                    <label class="text-theme-xs">{{ $l }}<input name="{{ $f }}" value="{{ $p->$f }}" required class="mt-0.5 h-8 w-full rounded border border-gray-300 px-2 dark:border-gray-700 dark:bg-gray-900"></label>
                  @endforeach
                  <label class="text-theme-xs">Unit<select name="unit" class="mt-0.5 h-8 w-full rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    @foreach (['days', 'weeks', 'months', 'years'] as $u)<option @selected(rtrim($p->unit, 's') . 's' === $u)>{{ $u }}</option>@endforeach</select></label>
                  <label class="col-span-2 text-theme-xs">Why (kept with the old values)<input name="reason" required minlength="5" class="mt-0.5 h-8 w-full rounded border border-gray-300 px-2 dark:border-gray-700 dark:bg-gray-900"></label>
                  <button class="col-span-2 rounded-lg bg-brand-500 px-3 py-1.5 text-white">Save</button>
                </form>
              </details>
            @endif
          </td>
        </tr>
      @endforeach
    </x-staff.table>
  @endif

  <x-staff.placeholder class="mt-6" dependency="products with audit and snapshotting" title="Editing products">
    Open loans read the penalty rate from their product every day, so changing a product today would change loans already running.
    Editing comes with Stage 4: each loan keeps a copy of its terms when it is approved, and every change is audited.
  </x-staff.placeholder>
@endsection
