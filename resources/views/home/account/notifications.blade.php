@extends('home.layouts.account', ['title' => 'Notifications', 'tab' => 'home'])

{{-- Notifications centre. There is no notifications table yet: "Recent updates" is built from the borrower's
     own records (Concerns\BorrowerJourneys::accountNotifications); the email list matches app/Notifications. --}}
@php
  $date = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('j M Y');
  $chip = ['ok' => 'sage', 'err' => 'apricot', 'info' => 'cloud'];
  $path = ['ok' => 'M5 12l4 4 10-10', 'err' => 'M12 8v5M12 16h.01', 'info' => 'M12 11v6M12 7h.01'];
  $emails = [
    ['Verification received', 'When you submit your details, with your reference number.'],
    ['Verification result', 'When our team approves your details, or tells you what to fix.'],
    ['Application received', 'When you apply for a loan from your account.'],
  ];
  $coming = [
    ['Loan approved and money sent', 'SMS and email the moment your loan is on its way.'],
    ['Repayment reminder', '3 days and 1 day before your due date.'],
    ['Payment received', 'A receipt for every M-Pesa payment we record.'],
    ['Overdue notice', 'If a payment is late, with what to do next.'],
  ];
@endphp

@section('content')
  <div class="acct-head">
    <h1>Notifications</h1>
    <p>Updates on your account and the messages we send you</p>
  </div>

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>Recent updates</h2></div>
      @if ($updates->isEmpty())
        <x-az.empty title="Nothing yet" message="Updates about your verification, loans and payments will show here." />
      @else
        <div class="a-list">
          @foreach ($updates as $u)
            <a class="a-row" href="{{ $u['href'] }}">
              <span class="chip {{ $chip[$u['tone']] ?? 'cloud' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path[$u['tone']] ?? $path['info'] }}" /></svg></span>
              <span class="t"><b>{{ $u['title'] }}</b><span>{{ $u['detail'] }}</span></span>
              <span class="amt"><small>{{ $date($u['date']) }}</small></span>
            </a>
          @endforeach
        </div>
      @endif
    </section>

    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Emails we send you</h2></div>
        <p class="muted" style="font-size:13.5px">To {{ $user->email }}. <a href="{{ route('account.help') }}">Wrong address?</a></p>
        <ul class="bj-mail">
          @foreach ($emails as [$t, $d])
            <li><span class="badge-s st-repaid">On</span><div><b>{{ $t }}</b><span>{{ $d }}</span></div></li>
          @endforeach
        </ul>
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>SMS and in-app alerts</h2></div>
        <x-az.placeholder dependency="SMS gateway and in-app notifications">
          We'll text you and show alerts here for these moments:
        </x-az.placeholder>
        <ul class="bj-mail">
          @foreach ($coming as [$t, $d])
            <li><span class="badge-s st-rejected">Soon</span><div><b>{{ $t }}</b><span>{{ $d }}</span></div></li>
          @endforeach
        </ul>
      </section>
    </div>
  </div>
@endsection
