{{-- Admin · products & pricing: loan_types, read-only. Editing is Stage 4 (open loans read the penalty rate live). --}}
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
          <td class="max-w-[320px] !whitespace-normal text-theme-xs text-gray-500">{{ $p->description ?: '—' }}</td>
        </tr>
      @endforeach
    </x-staff.table>
  @endif

  <x-staff.placeholder class="mt-6" dependency="products with audit and snapshotting" title="Editing products">
    Open loans read the penalty rate from their product every day, so changing a product today would change loans already running.
    Editing comes with Stage 4: each loan keeps a copy of its terms when it is approved, and every change is audited.
  </x-staff.placeholder>
@endsection
