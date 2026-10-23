@extends('home.layouts.account', ['title' => 'Borrow', 'tab' => 'borrow'])

{{-- Borrow in five steps (design "Borrow · 1 product … 5 sign"): product → amount → payout & security → offer → sign.
     One form, one POST to account.borrow.store (LoanController@apply, unchanged: amount + consent). The steps are
     only views; without JavaScript every step shows at once and the form still works. --}}
@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $max = $limit['limit'];
  $canApply = empty($blockers) && $product;
  $start = (int) old('amount', $product ? min($max, max($product['min'], 1000 * floor($max / 2000))) : 0);
  $rate = $product ? rtrim(rtrim(number_format($product['rate'], 2), '0'), '.') : 0;
  $penalty = $product ? rtrim(rtrim(number_format($product['penalty_rate'], 2), '0'), '.') : 0;
  // quick picks: quarters of the limit, rounded to the nearest 500
  $picks = $product ? collect([0.25, 0.5, 0.75, 1])->map(fn ($f) => (int) max($product['min'], round($max * $f / 500) * 500))->unique()->values() : collect();
  // payout and security as saved at verification (read-only here; changes go through Help)
  $mpesa = $user->paymentMethods()->whereIn('method_type_id', \Illuminate\Support\Facades\DB::table('payment_method_types')->where('slug', 'mobile_money')->select('id'))->latest('id')->first();
  $guarantor = $user->contacts()->where('relationship_specific', \App\Http\Controllers\HomeController::GUARANTOR)->latest('id')->first();
  $mask = fn ($n) => $n ? substr(preg_replace('/\D/', '', $n), 0, 4) . ' ••• ' . substr(preg_replace('/\D/', '', $n), -3) : null;
  $steps = ['Product', 'Amount', 'Payout & security', 'Offer', 'Sign'];
  // a validation error on amount/consent brings the borrower back to the right step
  $firstStep = $errors->has('amount') ? 2 : ($errors->has('consent') ? 5 : 1);
@endphp

@section('content')
  <div class="acct-head">
    <h1>Borrow</h1>
    <p>{{ $product ? $product['name'] . ' · ' . $rate . '% flat · repay in ' . $product['period'] : 'Emergency cash to your M-Pesa' }}</p>
  </div>

  @if (! $product)
    <x-az.alert type="err" title="Borrowing isn't available right now">Please <a href="{{ route('account.help') }}">contact us</a> and we'll help.</x-az.alert>
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

    <form method="POST" action="{{ route('account.borrow.store') }}" class="bj-borrow"
          x-data="{
            step: {{ $canApply ? $firstStep : 1 }}, last: 5,
            amount: {{ $start }}, min: {{ $product['min'] }}, max: {{ $max }}, rate: {{ $product['rate'] }},
            fmt(v) { return 'KES ' + (Number(v) || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            get valid() { return this.amount >= this.min && this.amount <= this.max; },
            get interest() { return (this.amount || 0) * this.rate / 100; },
            get fill() { return Math.max(0, Math.min(100, ((this.amount - this.min) / ((this.max - this.min) || 1)) * 100)) + '%'; },
            go(n) { if (n > 2 && !this.valid) { this.step = 2; return; } this.step = n; this.$nextTick(() => this.$refs.top.scrollIntoView({ behavior: 'smooth', block: 'start' })); },
          }">
      @csrf
      <ol class="bj-stepper" x-ref="top" aria-label="Steps">
        @foreach ($steps as $i => $label)
          <li :class="{ done: step > {{ $i + 1 }}, on: step === {{ $i + 1 }} }" :aria-current="step === {{ $i + 1 }} ? 'step' : null">
            <span class="n">{{ $i + 1 }}</span><span class="l">{{ $label }}</span>
          </li>
        @endforeach
      </ol>

      <div class="a-grid two">
        <div class="a-grid">
          {{-- 1 product --}}
          <section class="a-card" x-show="step === 1">
            <p class="bj-kicker">Step 1 of 5</p>
            <div class="a-sec-h"><h2>Choose a product</h2></div>
            <label class="bj-product on">
              <input type="radio" checked aria-label="{{ $product['name'] }}" />
              <span>
                <b>{{ $product['name'] }}</b>
                <small>{{ $kes($product['min']) }} to {{ $kes($max) }} · {{ $rate }}% flat · repay in {{ $product['period'] }} · one payment</small>
              </span>
              <span class="badge-s st-approved">Available</span>
            </label>
            <x-az.placeholder dependency="more products (business, asset-backed)" style="margin-top:12px">
              Other products are arranged with our team for now.
            </x-az.placeholder>
            <div class="bj-nav"><span></span><button type="button" class="btn btn-primary" @click="go(2)" @disabled(! $canApply)>Continue</button></div>
          </section>

          {{-- 2 amount --}}
          <section class="a-card" x-show="step === 2">
            <p class="bj-kicker">Step 2 of 5</p>
            <div class="a-sec-h"><h2>How much do you need?</h2></div>
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
            <div class="bj-nav"><button type="button" class="btn btn-ghost" @click="go(1)">Back</button><button type="button" class="btn btn-primary" @click="go(3)" :disabled="!valid">Continue</button></div>
          </section>

          {{-- 3 payout & security --}}
          <section class="a-card" x-show="step === 3">
            <p class="bj-kicker">Step 3 of 5</p>
            <div class="a-sec-h"><h2>Payout and security</h2></div>
            <dl class="a-break">
              <dt>Paid to</dt><dd>{{ $mpesa?->mobile_number ? 'M-Pesa ' . $mask($mpesa->mobile_number) : 'Your verified M-Pesa number' }}</dd>
              <dt>In the name of</dt><dd>{{ $mpesa?->account_name ?: $user->name }}</dd>
              <dt>Guarantor</dt><dd>{{ $guarantor?->name ?? '—' }}</dd>
            </dl>
            <p class="muted" style="font-size:13px;margin-top:12px">These come from your verification. To change them, <a href="{{ route('account.help') }}">send us a message</a> first.</p>
            <div class="bj-nav"><button type="button" class="btn btn-ghost" @click="go(2)">Back</button><button type="button" class="btn btn-primary" @click="go(4)">Continue</button></div>
          </section>

          {{-- 4 offer --}}
          <section class="a-card" x-show="step === 4">
            <p class="bj-kicker">Step 4 of 5</p>
            <div class="a-sec-h"><h2>Your offer</h2></div>
            <div class="bj-offer">
              <div>
                <p class="label">You receive</p>
                <p class="big" x-text="fmt(amount)">{{ $kes($start) }}</p>
                <p class="muted">{{ $product['name'] }} · {{ $product['period'] }}</p>
              </div>
              <dl class="a-break">
                <dt>Rate</dt><dd>{{ $rate }}% flat</dd>
                <dt>Interest</dt><dd x-text="fmt(interest)"></dd>
                <dt class="total">Total to repay</dt><dd class="total" x-text="fmt((amount || 0) + interest)"></dd>
              </dl>
            </div>
            <div class="bj-due">
              <span class="cal"><b>{{ $product['due_date']->format('j') }}</b>{{ strtoupper($product['due_date']->format('M')) }}</span>
              <span><b>One payment of <span x-text="fmt((amount || 0) + interest)"></span></b><small>Due {{ $product['due_date']->format('j M Y') }} if sent today; the date is set when the money is sent. Paying late adds a {{ $penalty }}% penalty.</small></span>
            </div>
            <p class="muted" style="font-size:12.5px;margin-top:10px">This is the offer for your application. Our team reviews it before the money is sent.</p>
            <div class="bj-nav"><button type="button" class="btn btn-ghost" @click="go(3)">Back</button><button type="button" class="btn btn-primary" @click="go(5)">Accept and sign</button></div>
          </section>

          {{-- 5 sign --}}
          <section class="a-card" x-show="step === 5">
            <p class="bj-kicker">Step 5 of 5</p>
            <div class="a-sec-h"><h2>Sign and send</h2></div>
            <p>Your verified signature is placed on the loan agreement for <b x-text="fmt(amount)"></b>. The agreement is fixed when you send this, and you can open it any time from the loan page.</p>
            @if ($canApply)
              <div class="a-cta">
                <label class="a-terms">
                  <input type="checkbox" name="consent" value="1" required />
                  <span>I agree to the loan terms. My verified signature will be used on the loan agreement.</span>
                </label>
                @error('consent')<p class="error">{{ $message }}</p>@enderror
                <button class="btn btn-primary" type="submit" :disabled="!valid">
                  <span x-text="'Apply for ' + fmt(amount)">Apply</span>
                </button>
              </div>
            @endif
            <div class="bj-nav"><button type="button" class="btn btn-ghost" @click="go(4)">Back</button><span></span></div>
          </section>
        </div>

        {{-- summary, always visible --}}
        <aside class="a-grid">
          <section class="a-card">
            <div class="a-sec-h"><h2>Summary</h2></div>
            <dl class="a-break">
              <dt>You receive</dt><dd x-text="fmt(amount)">{{ $kes($start) }}</dd>
              <dt>Interest ({{ $rate }}% flat)</dt><dd x-text="fmt(interest)"></dd>
              <dt>Repay by</dt><dd>{{ $product['due_date']->format('j M Y') }}</dd>
              <dt class="total">Total to repay</dt><dd class="total" x-text="fmt((amount || 0) + interest)"></dd>
            </dl>
          </section>
          <div class="a-note {{ $limit['tier'] === 'good_standing' ? 'ok' : 'info' }}" role="status">
            <div>
              <b>Your limit: {{ $kes($max) }}</b>
              {{ match (true) {
                  ($limit['capped_by'] ?? null) === 'risk' => 'Your recent repayments keep this below ' . $kes($limit['ladder']) . '; paying in full and on time raises it.',
                  ($limit['capped_by'] ?? null) === 'income' => 'Based on your income. Talk to us if it has changed.',
                  ($limit['capped_by'] ?? null) === 'behaviour' => 'Based on the amounts you have repaid on time so far; repaying on time raises it.',
                  $limit['tier'] === 'good_standing' => 'Good standing. Borrow any amount up to your limit.',
                  default => 'Repay on time to unlock up to ' . $kes($product['good_standing_limit']) . '.',
              } }}
            </div>
          </div>
          <a class="a-card bj-helpcard" href="{{ route('account.help') }}">
            <span class="chip cloud"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14h3v5H4zM17 14h3v5h-3z" /></svg></span>
            <span><b>Need help?</b><small>{{ \App\Support\BorrowerLoanState::CONTACT['phone'] }} · 24/7</small></span>
          </a>
        </aside>
      </div>
    </form>
  @endif
@endsection
