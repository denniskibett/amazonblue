{{-- Business loans, owner side (owner, 23 Oct 2026): what the business can borrow, the products, and its loans. --}}
@extends('home.layouts.account', ['title' => 'Borrow for ' . $business->legal_name, 'tab' => 'profile'])

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.business.show', $business) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $business->legal_name }}</a>
    <div class="acct-head">
      <h1>Borrow for {{ $business->trading_name ?: $business->legal_name }}</h1>
      <p>You apply and sign as the owner; the loan is recorded against the business.</p>
    </div>
  </div>


  <div class="a-stats p-three">
    <div class="a-stat"><p class="label">Business limit</p><p class="big"><x-az.money :amount="$business->credit_limit ?? 0" /></p></div>
    <div class="a-stat"><p class="label">Owed now</p><p class="big"><x-az.money :amount="$owed" /></p></div>
    <div class="a-stat"><p class="label">Can borrow</p><p class="big"><x-az.money :amount="$available" /></p></div>
  </div>

  <div class="a-grid two">
    <section class="a-card">
      <div class="a-sec-h"><h2>Apply</h2></div>
      @if ($blockers)
        <x-az.alert type="info" :title="$blockers[0][0]">{{ $blockers[0][1] }}</x-az.alert>
        @if (count($blockers) > 1)
          <ul style="margin:10px 0 0 18px;font-size:14px">@foreach (array_slice($blockers, 1) as [$t, $m])<li>{{ $m }}</li>@endforeach</ul>
        @endif
      @else
        <form method="POST" action="{{ route('account.business.borrow.store', $business) }}" class="a-grid" style="gap:12px">
          @csrf
          <x-az.field name="loan_type_id" label="Product" type="select" required placeholder="Choose a product"
            :options="$products->mapWithKeys(fn ($t) => [$t->id => $t->name . ' · ' . rtrim(rtrim(number_format((float) $t->interest_rate, 2), '0'), '.') . '% for ' . $t->period . ' ' . $t->unit])->all()" />
          <x-az.field name="amount" label="Amount (KES)" type="number" required step="1" :max="$available" inputmode="decimal" hint="Up to KES {{ number_format($available, 2) }}" />
          <x-az.field name="purpose" label="What the money is for" type="textarea" required maxlength="500" rows="3" hint="e.g. stock for the December season" />
          <label style="display:flex;gap:10px;font-size:14px"><input type="checkbox" name="consent" value="1" required>
            <span>I accept the loan terms and sign the agreement for {{ $business->legal_name }} with the signature on my account.</span></label>
          @error('amount')<x-az.alert type="err" title="Check your application">{{ $message }}</x-az.alert>@enderror
          <button class="btn btn-primary btn-block" type="submit">Send application</button>
        </form>
      @endif
    </section>

    <section class="a-card">
      <div class="a-sec-h"><h2>Business loans</h2></div>
      <div class="a-list">
        @forelse ($loans as $loan)
          <a class="a-row" href="{{ route('account.loan', $loan) }}">
            <span class="t"><b>#{{ str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) }} · {{ $loan->loanType?->name }}</b><span>{{ ucfirst($loan->status) }} · {{ \Illuminate\Support\Carbon::parse($loan->borrow_date)->format('j M Y') }}</span></span>
            <span class="amt num">KES {{ number_format((float) $loan->amount, 2) }}</span>
          </a>
        @empty
          <p class="a-empty">No business loans yet.</p>
        @endforelse
      </div>
    </section>
  </div>
@endsection
