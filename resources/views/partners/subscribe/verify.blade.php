{{-- Partner verification (owner, 23 Oct 2026): identity (the same KYC steps and staff review as borrowers), the business
     for a business partner, and where the money comes from. App\Services\Partners\Subscription::verification. --}}
@extends('home.layouts.account', ['title' => 'Verify your account', 'tab' => 'home', 'area' => 'partner'])

@section('content')
  <div>
    <a class="acct-back" href="{{ route('partner.subscribe') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Subscribe</a>
    <div class="acct-head">
      <h1>Verify your account</h1>
      <p>We verify every partner before taking their money. It takes a few minutes; our team checks it within a working day.</p>
    </div>
  </div>

  @if ($sent = session('partner_sent'))<x-az.alert type="ok" :title="$sent['title']">{{ $sent['body'] }}</x-az.alert>@endif

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>Steps</h2></div>
      <ol class="a-grid" style="gap:10px;list-style:none;padding:0;margin:0">
        @foreach ($check['steps'] as $key => $step)
          <li style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--line, #e4e7ec);border-radius:12px">
            <div>
              <p style="font-weight:600;margin:0">{{ $step['title'] }}</p>
              <p class="muted" style="margin:2px 0 0;font-size:13px">{{ $step['status'] }}</p>
            </div>
            @if ($step['done'])
              <span class="chip sage">Done</span>
            @elseif ($key !== 'source')
              <a class="btn btn-ghost" href="{{ $step['href'] }}">{{ $key === 'identity' ? 'Open' : ($partner->business_id ? 'Open' : 'Add business') }}</a>
            @endif
          </li>
        @endforeach
        <li style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--line, #e4e7ec);border-radius:12px">
          <div>
            <p style="font-weight:600;margin:0">Your signature</p>
            <p class="muted" style="margin:2px 0 0;font-size:13px">{{ $check['signature'] ? 'On file, used to sign your subscription' : 'Added in the identity step' }}</p>
          </div>
          @if ($check['signature'])<span class="chip sage">Done</span>@endif
        </li>
      </ol>
      @if ($check['ready'])
        <a class="btn btn-primary btn-block" style="margin-top:14px" href="{{ route('partner.subscribe') }}">Continue to subscribe</a>
      @endif
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>About your money</h2></div>
      <form method="POST" action="{{ route('partner.verify.save') }}" class="a-grid" style="gap:12px">
        @csrf
        <x-az.field name="source_of_funds" label="Where the money comes from" type="select" required :options="$sources" placeholder="Choose one" :value="$partner->source_of_funds" />
        @if ($isBusiness)
          @if ($businesses->isNotEmpty())
            <x-az.field name="business_id" label="Your business" type="select" :options="$businesses->mapWithKeys(fn ($b) => [$b->id => $b->displayName() . ' · ' . (\App\Models\Business::STATUSES[$b->status] ?? $b->status)])->all()" placeholder="Choose your business" :value="$partner->business_id" hint="The business that sends the money. It must be verified." />
          @else
            <x-az.alert type="info" title="Add your business">You are a business partner, so we verify the business too. <a href="{{ route('account.business.start') }}">Add your business</a>, then come back and choose it here.</x-az.alert>
          @endif
        @endif
        <button class="btn btn-primary btn-block" type="submit">Save</button>
      </form>
    </section>
  </div>
@endsection
