@extends('home.layouts.account', ['title' => 'Borrow', 'tab' => 'borrow'])

@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $max = $limit['limit'];
  $canApply = empty($blockers) && $product;
  $start = (int) old('amount', $product ? min($max, max($product['min'], 1000 * floor($max / 2000))) : 0);
  $rate = $product ? rtrim(rtrim(number_format($product['rate'], 2), '0'), '.') : 0;
  // quick picks: quarters of the limit, rounded to the nearest 500
  $picks = $product ? collect([0.25, 0.5, 0.75, 1])->map(fn ($f) => (int) max($product['min'], round($max * $f / 500) * 500))->unique()->values() : collect();
@endphp

@section('content')
  <div class="acct-head">
    <h1>Borrow</h1>
    <p>{{ $product ? $product['name'] . ' · ' . $rate . '% flat · repay in ' . $product['period'] : 'Emergency cash to your M-Pesa' }}</p>
  </div>

  @if (! $product)
    <div class="a-note err"><div><b>Borrowing isn't available right now</b>Please contact us on WhatsApp and we'll help.</div></div>
  @else
    @unless ($canApply)
      <div class="a-note {{ $isVerified ? 'warn' : 'info' }}">
        <div>
          <b>{{ $blockers[0][0] }}</b>{{ $blockers[0][1] }}
          @unless ($isVerified)
            <div><a class="btn btn-primary" href="{{ route('kyc.personal') }}">{{ $user->kyc_status === 'rejected' ? 'Update and resubmit' : ($user->kyc_status === 'submitted' ? 'View my details' : 'Verify now') }}</a></div>
          @endunless
        </div>
      </div>
    @endunless

    <div class="a-note {{ $limit['tier'] === 'good_standing' ? 'ok' : 'info' }}" role="status">
      <div>
        <b>Your limit: {{ $kes($max) }}</b>
        {{ match (true) {
            ($limit['capped_by'] ?? null) === 'risk' => 'Borrow from ' . $kes($product['min']) . ' up to ' . $kes($max) . '. Your recent repayments keep this below ' . $kes($limit['ladder']) . '; paying in full and on time raises it.',
            ($limit['capped_by'] ?? null) === 'income' => 'Borrow from ' . $kes($product['min']) . ' up to ' . $kes($max) . ', based on your income. Talk to us if it has changed.',
            $limit['tier'] === 'good_standing' => 'Good standing. Borrow any amount from ' . $kes($product['min']) . ' up to your limit.',
            default => 'Borrow from ' . $kes($product['min']) . ' up to ' . $kes($max) . '. Repay on time to unlock up to ' . $kes($product['good_standing_limit']) . '.',
        } }}
      </div>
    </div>

    <form method="POST" action="{{ route('account.borrow.store') }}" class="a-grid two"
          x-data="{
            amount: {{ $start }}, min: {{ $product['min'] }}, max: {{ $max }}, rate: {{ $product['rate'] }},
            fmt(v) { return 'KES ' + (Number(v) || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            get valid() { return this.amount >= this.min && this.amount <= this.max; },
            get interest() { return (this.amount || 0) * this.rate / 100; },
            get fill() { return Math.max(0, Math.min(100, ((this.amount - this.min) / ((this.max - this.min) || 1)) * 100)) + '%'; },
          }">
      @csrf
      <section class="a-card">
        <p class="muted" style="text-align:center;font-size:14px">How much do you need?</p>
        <label class="a-amount" style="margin-top:8px">
          <span>KES</span>
          <input name="amount" type="number" inputmode="numeric" step="100" :min="min" :max="max"
                 x-model.number="amount" aria-label="Amount in KES" @disabled(! $canApply) />
        </label>
        <input class="a-range" type="range" :min="min" :max="max" step="100" x-model.number="amount"
               :style="'--fill:' + fill" aria-label="Choose amount" @disabled(! $canApply) />
        <div class="a-scale"><span>{{ $kes($product['min']) }}</span><span>{{ $kes($max) }}</span></div>
        <div class="a-chips">
          @foreach ($picks as $pick)
            <button type="button" @click="amount = {{ $pick }}" :class="amount === {{ $pick }} && 'on'" @disabled(! $canApply)>
              {{ $pick === $max ? 'Max ' . $kes($pick) : $kes($pick) }}
            </button>
          @endforeach
        </div>
        @error('amount')<p class="a-note err" style="margin-top:16px">{{ $message }}</p>@enderror
        <p x-show="!valid" x-cloak class="a-note err" style="margin-top:16px">Choose between {{ $kes($product['min']) }} and {{ $kes($max) }}.</p>

      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>Summary</h2></div>
        <dl class="a-break">
          <dt>You receive</dt><dd x-text="fmt(amount)"></dd>
          <dt>Interest ({{ $rate }}% flat)</dt><dd x-text="fmt(interest)"></dd>
          <dt>Repay by</dt><dd>{{ $product['due_date']->format('j M Y') }}</dd>
          <dt class="total">Total to repay</dt><dd class="total" x-text="fmt((amount || 0) + interest)"></dd>
        </dl>
        <p class="muted" style="font-size:13px;margin-top:14px">
          Approved loans go straight to your M-Pesa. Paying late adds a {{ rtrim(rtrim(number_format($product['penalty_rate'], 2), '0'), '.') }}% penalty.
        </p>

        @if ($canApply)
          <div class="a-cta">
            <label class="a-terms">
              <input type="checkbox" name="consent" value="1" required />
              <span>I agree to the loan terms. My verified signature will be used on the loan agreement.</span>
            </label>
            <button class="btn btn-primary" type="submit" :disabled="!valid">
              <span x-text="'Apply for ' + fmt(amount)"></span>
            </button>
          </div>
        @endif
      </section>
    </form>
  @endif
@endsection
