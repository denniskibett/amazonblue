{{-- Online subscription (owner, 23 Oct 2026): choose a line and an amount, read the subscription, sign with the signature on
     file. App\Services\Partners\Subscription. --}}
@extends('home.layouts.account', ['title' => 'Subscribe', 'tab' => 'home', 'area' => 'partner'])

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.partner.home') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Home</a>
    <div class="acct-head">
      <h1>Fund loans with us</h1>
      <p>Choose a line, sign the subscription, send the money. You earn {{ rtrim(rtrim(number_format($terms['share'], 2), '0'), '.') }}% of the interest on every loan your money funds.</p>
    </div>
  </div>

  @if ($sent = session('partner_sent'))<x-az.alert type="ok" :title="$sent['title']">{{ $sent['body'] }}</x-az.alert>@endif
  @error('cancel')<x-az.alert type="err" title="Could not cancel">{{ $message }}</x-az.alert>@enderror

  @if (! $enabled)
    <x-az.alert type="info" title="Opening soon">Online subscription isn't open yet. Use <a href="{{ route('account.partner.funds') }}">Add funds</a> in the meantime.</x-az.alert>
  @elseif (! $check['ready'])
    <x-az.alert type="warn" title="Verify your account first">We need to verify you before you can subscribe. <a href="{{ route('partner.verify') }}">See what is left</a>.</x-az.alert>
  @endif

  <div class="a-grid two">
    <section class="a-card" x-data="{ term: @js(old('term', 'short_term')), months: @js((int) old('months', 12)), amount: @js(old('amount', '')) }">
      <div class="a-sec-h"><h2>Your subscription</h2></div>
      <form method="POST" action="{{ route('partner.subscribe.store') }}" class="a-grid" style="gap:14px">
        @csrf
        <fieldset class="a-grid" style="gap:10px;border:0;padding:0;margin:0">
          <legend style="font-weight:600;margin-bottom:6px">Line</legend>
          @foreach (['short_term', 'long_term'] as $t)
            <label style="display:flex;gap:10px;padding:12px 14px;border:1px solid var(--line, #e4e7ec);border-radius:12px;cursor:pointer" :style="term === '{{ $t }}' ? 'border-color: var(--brand, #465fff)' : ''">
              <input type="radio" name="term" value="{{ $t }}" x-model="term" style="margin-top:3px" @checked(old('term', 'short_term') === $t)>
              <span><b>{{ $terms[$t]['title'] }}</b><span class="muted" style="display:block;font-size:13px">{{ $terms[$t]['body'] }}</span></span>
            </label>
          @endforeach
        </fieldset>
        <div x-show="term === 'long_term'" x-cloak>
          <x-az.field name="months" label="How long the money stays in" type="select" :options="collect($terms['long_term']['months'])->mapWithKeys(fn ($m) => [$m => $m . ' months'])->all()" x-model="months" />
        </div>
        <x-az.field name="amount" label="Amount (KES)" type="number" required step="0.01" :min="$min" inputmode="decimal" x-model="amount" hint="At least KES {{ number_format($min) }}" />
        <label style="display:flex;gap:10px;font-size:14px">
          <input type="checkbox" name="agree" value="1" required>
          <span>I have read the subscription and sign it with the signature on my account.</span>
        </label>
        @error('amount')<x-az.alert type="err" title="Check your subscription">{{ $message }}</x-az.alert>@enderror
        @error('agree')<p style="font-size:13px;color:#d92d20">{{ $message }}</p>@enderror
        <button class="btn btn-primary btn-block" type="submit" @disabled(! $enabled || ! $check['ready'])>Sign subscription</button>
      </form>
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>The subscription</h2></div>
      <p class="muted" style="font-size:13px;margin:0 0 10px">Your figures go in when you sign. You get the signed copy to keep.</p>
      <div style="max-height:420px;overflow:auto;font-size:14px;line-height:1.55;white-space:pre-line;border:1px solid var(--line, #e4e7ec);border-radius:12px;padding:14px">{{ $preview }}</div>
    </section>
  </div>

  @if ($subscriptions->isNotEmpty())
    <section class="a-card">
      <div class="a-sec-h"><h2>Your subscriptions</h2></div>
      <div class="a-grid" style="gap:8px">
        @foreach ($subscriptions as $s)
          <a href="{{ route('partner.subscriptions.show', $s->id) }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--line, #e4e7ec);border-radius:12px;text-decoration:none;color:inherit">
            <span><b>KES {{ number_format((float) $s->amount, 2) }}</b> · {{ $s->payload['term'] === 'long_term' ? $s->payload['months'] . ' months' : 'short term' }}
              <span class="muted" style="display:block;font-size:13px">Signed {{ \Illuminate\Support\Carbon::parse($s->created_at)->format('j M Y') }}</span></span>
            <span class="chip {{ $s->status === 'done' ? 'sage' : ($s->status === 'declined' ? 'apricot' : 'sand') }}">{{ ['done' => 'Line open', 'declined' => 'Closed', 'pending' => $s->reference ? 'Checking the money' : 'Waiting for the money'][$s->status] ?? $s->status }}</span>
          </a>
        @endforeach
      </div>
    </section>
  @endif
@endsection
