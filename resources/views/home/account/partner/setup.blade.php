{{-- Partner page for a partner-role user with no partners row yet (staff create and link it). --}}
@extends('home.layouts.account', ['title' => $title, 'tab' => $tab, 'area' => 'partner'])

@section('content')
  <div class="acct-head">
    <h1>{{ $title }}</h1>
    <p>Partner account</p>
  </div>
  <section class="a-card">
    <x-az.empty title="Your partner account is being set up"
      message="Our team links your login to your partner record. Once that is done, your balances, investments, funded loans and statement show here.">
      <a class="btn btn-primary" href="https://wa.me/254701607959" target="_blank" rel="noopener">Message us on WhatsApp</a>
    </x-az.empty>
  </section>
@endsection
