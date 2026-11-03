{{-- Collector portal: one placed loan (owner, 3 Nov 2026). Only what the placement shares; contact details only after
     the borrower was told which agency will call. --}}
@extends('home.layouts.account', ['title' => 'Loan #' . $p->loan_id, 'tab' => 'home'])

@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $lines = fn (array $pairs) => collect($pairs)->filter(fn ($v) => filled($v));
@endphp

@section('content')
  <div class="acct-head">
    <h1>{{ $profile['identity']['Name'] }}</h1>
    <p>Loan #{{ $p->loan_id }} · {{ $p->loan->loanType?->name }} · {{ $profile['days_late'] }} days late · placed {{ $p->placed_at->format('j M') }}, ends {{ $p->ends_on->format('j M Y') }}</p>
  </div>

  @if ($profile['status'] === 'forbearance')
    <x-az.alert type="info" title="In forbearance{{ $profile['forbearance_until'] ? ' until ' . $profile['forbearance_until']->format('j M Y') : '' }}">The late penalty is paused while the borrower keeps to what was agreed. Remind, don't press.</x-az.alert>
  @endif
  @unless ($profile['contact_allowed'])
    <x-az.alert type="warn" title="Don't contact this borrower yet">{{ $profile['contact_from'] ? 'You may contact them from ' . $profile['contact_from']->format('j M Y') . ', once the notice period has run.' : 'We have not yet told them which agency will call. Contact details show once we have.' }}</x-az.alert>
  @endunless

  <section class="a-card">
    <div class="a-sec-h"><h2>What is owed today</h2></div>
    <dl class="a-dl">@foreach ($profile['owed'] as $k => $v)<div><dt>{{ $k }}</dt><dd>{{ $kes($v) }}</dd></div>@endforeach</dl>
    <p class="muted" style="font-size:13px;margin-top:10px">Pay to our paybill only, account {{ $p->loan_id }}. Never take cash or a payment to the agency.</p>
    <p style="margin-top:12px"><a class="btn btn-ghost" href="{{ route('collect.profile', $p->id) }}" target="_blank" rel="noopener">Download profile (PDF)</a></p>
  </section>

  <section class="a-card">
    <div class="a-sec-h"><h2>How to reach them</h2></div>
    @php $reach = $lines($profile['reach'] + $profile['work']); @endphp
    @if ($reach->isEmpty())
      <p class="muted">Contact details show once the notice period has run.</p>
    @else
      <dl class="a-dl">@foreach ($reach as $k => $v)<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl>
    @endif
    <p class="muted" style="font-size:13px;margin-top:10px">Next of kin are only asked how to reach the borrower. Never tell them about the debt.</p>
  </section>

  @if ($profile['contact_allowed'])
    <section class="a-card">
      <div class="a-sec-h"><h2>Log a contact or promise</h2></div>
      <form method="POST" action="{{ route('collect.contact', $p->id) }}">@csrf
        <x-az.field name="channel" label="Channel" type="select" :options="$channels" required />
        <x-az.field name="outcome" label="Outcome" required maxlength="120" placeholder="e.g. Reached · promised to pay" />
        <x-az.field name="promise_amount" label="Promise amount (KES)" type="number" min="1" step="1" />
        <x-az.field name="promise_date" label="Pay by" type="date" :min="now()->toDateString()" />
        <x-az.field name="note" label="Note" type="textarea" rows="3" maxlength="1000" placeholder="What was agreed, and the next step" />
        <button class="btn btn-primary" style="margin-top:12px">Save contact</button>
      </form>
    </section>
  @endif

  <section class="a-card">
    <div class="a-sec-h"><h2>Promises and contacts</h2></div>
    @if ($profile['contacts']->isEmpty() && $profile['our_promises']->isEmpty())
      <p class="muted">No contacts logged yet.</p>
    @else
      <dl class="a-dl">
        @foreach ($profile['contacts'] as $c)
          <div><dt>{{ $c->created_at->format('j M, H:i') }} · {{ $channels[$c->channel] ?? $c->channel }} · {{ $c->user?->name }}{{ $c->note ? ' — ' . $c->note : '' }}</dt>
            <dd>{{ $c->outcome }}{{ $c->promise_amount ? ' · ' . $kes($c->promise_amount) . ' by ' . $c->promise_date?->format('j M') : '' }}</dd></div>
        @endforeach
        @foreach ($profile['our_promises'] as $a)
          <div><dt>{{ \Illuminate\Support\Carbon::parse($a->action_date)->format('j M') }} · our team</dt><dd>{{ $a->outcome }} · promised {{ \Illuminate\Support\Carbon::parse($a->promised_date)->format('j M') }}</dd></div>
        @endforeach
      </dl>
    @endif
  </section>

  <section class="a-card">
    <div class="a-sec-h"><h2>Payments</h2></div>
    @if ($profile['payments']->isEmpty())
      <p class="muted">No payments yet.</p>
    @else
      <dl class="a-dl">@foreach ($profile['payments'] as $r)<div><dt>{{ \Illuminate\Support\Carbon::parse($r->repayment_date)->format('j M Y') }}{{ $r->mpesa_ref ? ' · ' . $r->mpesa_ref : '' }}</dt><dd>{{ $kes($r->amount) }}</dd></div>@endforeach</dl>
    @endif
  </section>
@endsection
