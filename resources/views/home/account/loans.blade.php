@extends('home.layouts.account', ['title' => 'My loans', 'tab' => 'loans'])

@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $badges = [
    'pending' => ['Under review', 'amber'], 'approved' => ['Approved', 'blue'], 'disbursed' => ['Active', 'green'],
    'active' => ['Active', 'green'], 'overdue' => ['Overdue', 'red'], 'forbearance' => ['Payment plan', 'blue'],
    'defaulted' => ['Defaulted', 'red'], 'recovery' => ['In recovery', 'red'], 'repaid' => ['Repaid', 'grey'],
    'rejected' => ['Not approved', 'grey'], 'written_off' => ['Written off', 'grey'],
  ];
@endphp

@section('content')
  <div class="acct-head">
    <h1>My loans</h1>
    <p>Every loan you've taken with us, newest first.</p>
  </div>

  <section class="a-card">
    @if ($loans->isEmpty())
      <div class="a-empty">
        <p>You haven't borrowed yet.</p>
        <a class="btn btn-primary" href="{{ route('account.borrow') }}" style="margin-top:14px">Borrow now</a>
      </div>
    @else
      <div class="a-list">
        @foreach ($loans as $loan)
          @php
            [$text, $tone] = $badges[$loan->status] ?? [ucfirst($loan->status), 'grey'];
            $cycle = $loan->cycles->sortByDesc('cycle_number')->first();
            $late = in_array($loan->status, ['disbursed', 'active']) && $cycle?->due_date && now()->startOfDay()->gt($cycle->due_date);
            if ($late) { [$text, $tone] = ['Overdue', 'red']; }
          @endphp
          <a class="a-row" href="{{ route('account.loan', $loan) }}">
            <span class="chip {{ $tone === 'red' ? 'rose' : ($loan->status === 'repaid' ? 'sage' : 'cloud') }}">
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $loan->status === 'repaid' ? 'M5 12l4 4 10-10' : 'M4 6h16M4 12h16M4 18h10' }}" /></svg>
            </span>
            <span class="t">
              <b>{{ $kes($loan->amount) }}</b>
              <span>#{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }} · {{ \Illuminate\Support\Carbon::parse($loan->borrow_date)->format('j M Y') }}{{ $cycle?->due_date ? ' · due ' . $cycle->due_date->format('j M') : '' }}</span>
            </span>
            <span class="badge-s {{ $tone }}">{{ $text }}</span>
          </a>
        @endforeach
      </div>
      @if ($loans->hasPages())
        <div class="a-pager">
          @if ($loans->previousPageUrl())<a class="btn btn-ghost" href="{{ $loans->previousPageUrl() }}">Newer</a>@else<span></span>@endif
          @if ($loans->nextPageUrl())<a class="btn btn-ghost" href="{{ $loans->nextPageUrl() }}">Older</a>@endif
        </div>
      @endif
    @endif
  </section>
@endsection
