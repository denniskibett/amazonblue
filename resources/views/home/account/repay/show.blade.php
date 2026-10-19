{{-- Repay one loan (Concerns\BorrowerRepayment@accountRepay). mode: pay | recovery | not_yet | closed_other.
     Amounts come from LoanProgress only. M-Pesa: config('lending.mpesa'); STK push and stored claims are Stage 4. --}}
@extends('home.layouts.account', ['title' => 'Repay ' . $progress['reference'], 'tab' => 'loans'])

@php
  $loan = $progress['loan'];
  $bal = $progress['balance'];
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : null;
  $canClaim = in_array($mode, ['pay', 'recovery'], true) && $due > 0;
  $penaltyRate = (float) ($loan->loanType->penalty_rate ?? 0);
  $phone = (string) $user->phone;
  $maskedPhone = strlen($phone) >= 7 ? substr($phone, 0, 4) . ' ••• ' . substr($phone, -3) : 'your phone';
  $whatsapp = 'https://wa.me/254701607959';
  $startAmount = (float) old('amount', $due);
  $isFull = abs($startAmount - $due) < 0.005;
  $stkOn = $mpesa['stk'] || $demo;
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.loan', $loan) }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>{{ $progress['reference'] }}</a>
    <div class="acct-head">
      <h1>Repay</h1>
      <p>{{ $progress['reference'] }} · {{ $loan->loanType->name ?? 'Loan' }}</p>
    </div>
  </div>

  @if ($checking)
    <x-az.alert type="ok" title="We're checking your payment">
      Thanks. We have your M-Pesa code <b style="display:inline">{{ $checking }}</b>. Our team matches it with M-Pesa and records it;
      your balance and receipt appear here once it's recorded. We'll call you if anything doesn't match.
    </x-az.alert>
  @endif

  @if ($mode === 'not_yet')
    <x-az.empty title="Nothing to repay yet"
      message="{{ $loan->status === 'approved' ? 'Your loan is approved and on its way to your M-Pesa. You can repay here once it arrives.' : 'We\'re still reviewing your application. Once the money reaches your M-Pesa, you can repay here.' }}">
      <a class="btn btn-primary" href="{{ route('account.loan', $loan) }}">View loan</a>
    </x-az.empty>
  @elseif ($mode === 'closed_other')
    <x-az.empty title="{{ $loan->status === 'rejected' ? 'Nothing to repay' : 'Talk to us about this loan' }}"
      message="{{ $loan->status === 'rejected' ? 'This application was not approved, so nothing was sent and there is nothing to repay.' : 'This loan is closed in our books. If you would like to settle it, our team will help.' }}">
      @if ($loan->status === 'rejected')
        <a class="btn btn-primary" href="{{ route('account.home') }}">Back to home</a>
      @else
        <a class="btn btn-primary" href="{{ $whatsapp }}" target="_blank" rel="noopener">Message us on WhatsApp</a>
      @endif
    </x-az.empty>
  @else
    {{-- recovery / overdue notices (existing data only) --}}
    @if ($mode === 'recovery')
      <x-az.alert type="err" title="Your loan is with our recovery team">
        Please contact the recovery team to agree how you will pay. You can still pay by Paybill below and tell us the M-Pesa code.
        <div><a class="btn btn-primary" href="{{ $whatsapp }}" target="_blank" rel="noopener">Contact the recovery team</a></div>
      </x-az.alert>
    @elseif ($progress['is_late'])
      <x-az.alert type="warn" title="This payment is overdue">
        It was due on {{ $date($progress['due_date']) }}.
        @if (($bal['penalty'] ?? 0) > 0) A late penalty of {{ $kes($bal['penalty']) }} is included in the amount due.@endif
        @if ($penaltyRate > 0) Your loan terms add a {{ rtrim(rtrim(number_format($penaltyRate, 2), '0'), '.') }}% penalty while it stays unpaid.@endif
        Pay what you can today.
      </x-az.alert>
    @endif

    <section class="a-hero {{ $progress['is_late'] || $mode === 'recovery' ? 'red' : '' }}">
      <div class="hero-grid">
        <div>
          <p class="label">Amount due now</p>
          <p class="big">{{ $kes($due) }}</p>
          <p class="sub">
            @if ($progress['due_date'])
              {{ $progress['is_late'] ? 'Overdue since' : 'Due by' }} {{ $date($progress['due_date']) }}
            @endif
            @if ($bal) · {{ $kes($bal['paid']) }} paid so far @endif
          </p>
        </div>
        <div class="ring" style="--p: {{ $progress['progress'] }}"><span>{{ $progress['progress'] }}%<small>repaid</small></span></div>
      </div>
    </section>

    @if ($due <= 0)
      <x-az.alert type="info" title="Nothing due right now">
        Your balance shows nothing left to pay. Our team will close the loan once the last payment is checked.
      </x-az.alert>
    @else
      <div class="a-grid two" x-data="{
            due: {{ round($due, 2) }},
            mode: '{{ $isFull ? 'full' : 'other' }}',
            amount: {{ round($startAmount, 2) }},
            fmt(v) { return 'KES ' + (Number(v) || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            get valid() { return this.amount >= 1 && this.amount <= this.due; },
            pick(m) { this.mode = m; if (m === 'full') this.amount = this.due; },
          }">
        <div class="a-grid">
          {{-- 1. choose the amount --}}
          <section class="a-card">
            <div class="a-sec-h"><h2>How much are you paying?</h2></div>
            <div class="rp-seg" role="group" aria-label="Amount">
              <button type="button" :class="mode === 'full' && 'on'" :aria-pressed="mode === 'full'" @click="pick('full')" class="{{ $isFull ? 'on' : '' }}">Full amount due</button>
              <button type="button" :class="mode === 'other' && 'on'" :aria-pressed="mode === 'other'" @click="pick('other')" class="{{ $isFull ? '' : 'on' }}">Other amount</button>
            </div>
            <div class="rp-amount">
              <span class="rp-cap">Amount</span>
              <p class="rp-big" x-show="mode === 'full'" @if (! $isFull) x-cloak @endif x-text="fmt(amount)">{{ $kes($startAmount) }}</p>
              <label class="a-amount" x-show="mode === 'other'" @if ($isFull) x-cloak @endif>
                <span>KES</span>
                <input type="number" inputmode="decimal" step="1" min="1" :max="due" x-model.number="amount" aria-label="Amount in KES" />
              </label>
            </div>
            <p class="muted rp-small">Between KES 1.00 and {{ $kes($due) }}. Part payments are welcome; the rest stays due.</p>
            <p x-show="!valid" x-cloak class="a-note err" style="margin-top:12px">Choose between KES 1.00 and {{ $kes($due) }}.</p>
          </section>

          {{-- 2a. STK push (Stage 4; design stub with ?demo=1 locally) --}}
          @if ($mode === 'pay')
            <section class="a-card" @if ($demo) x-data="{
                state: '{{ $demoState ?? 'idle' }}', left: {{ $demoState === 'waiting' ? 38 : 60 }}, timer: null,
                start() { this.state = 'sending'; clearInterval(this.timer); setTimeout(() => { this.state = 'waiting'; this.left = 60; this.tick(); }, 1500); },
                tick() { this.timer = setInterval(() => { if (--this.left <= 0) { clearInterval(this.timer); this.state = 'timeout'; } }, 1000); },
                finish(s) { clearInterval(this.timer); this.state = s; },
              }" x-init="state === 'waiting' && tick()" @endif>
              <div class="a-sec-h"><h2>Pay with M-Pesa</h2>@if ($demo)<span class="badge-s st-pending">Demo</span>@endif</div>

              @if ($demo)
                <div x-show="state === 'idle'" @if ($demoState) x-cloak @endif>
                  <p class="muted rp-small">We send a payment prompt to {{ $maskedPhone }}. Enter your M-Pesa PIN to pay <b x-text="fmt(amount)"></b>.</p>
                  <button class="btn btn-primary btn-block" type="button" :disabled="!valid" @click="start()" style="margin-top:14px">Send prompt to my phone</button>
                </div>
                <div class="rp-stk" x-show="state === 'sending'" @if ($demoState !== 'sending') x-cloak @endif>
                  <span class="rp-stk__ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v18H7zM11 18h2" /></svg></span>
                  <b>Sending the prompt…</b>
                  <p>Connecting to M-Pesa. Keep your phone close.</p>
                  <div class="rp-bar"><i class="rp-bar--run"></i></div>
                </div>
                <div class="rp-stk" x-show="state === 'waiting'" @if ($demoState !== 'waiting') x-cloak @endif>
                  <span class="rp-stk__ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" /></svg></span>
                  <b>Check your phone</b>
                  <p>We sent an M-Pesa prompt to {{ $maskedPhone }}. Enter your PIN to pay <span x-text="fmt(amount)"></span>.</p>
                  <div class="rp-bar"><i :style="'width:' + Math.round((60 - left) / 60 * 100) + '%'" style="width:37%"></i></div>
                  <p class="rp-timer">Waiting… <span x-text="'0:' + String(left).padStart(2, '0')">0:38</span></p>
                  <button class="btn btn-ghost" type="button" @click="start()">Resend prompt</button>
                </div>
                <div class="rp-stk ok" x-show="state === 'received'" @if ($demoState !== 'received') x-cloak @endif>
                  <span class="rp-stk__ic"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg></span>
                  <b>Payment received</b>
                  <p><span x-text="fmt(amount)"></span> from {{ $maskedPhone }}. Your receipt will appear under your loan's payments.</p>
                  <a class="btn btn-primary" href="{{ route('account.loan', $loan) }}">Back to my loan</a>
                </div>
                <x-az.alert type="err" title="Payment didn't go through" x-show="state === 'failed'" :x-cloak="$demoState !== 'failed'">
                  M-Pesa declined the payment (for example, not enough balance or the PIN was cancelled). Nothing was charged.
                  <div><button class="btn btn-primary" type="button" @click="start()">Try again</button> <button class="btn btn-ghost" type="button" @click="pick('other'); state = 'idle'">Pay less</button></div>
                </x-az.alert>
                <x-az.alert type="warn" title="Payment request expired" x-show="state === 'timeout'" :x-cloak="$demoState !== 'timeout'">
                  The M-Pesa prompt timed out after 60 seconds. Nothing was charged.
                  <div><button class="btn btn-primary" type="button" @click="start()">Send again</button></div>
                </x-az.alert>
                <p class="rp-demo">Local design stub, nothing is sent.
                  Simulate: <button type="button" @click="finish('received')">received</button> ·
                  <button type="button" @click="finish('failed')">failed</button> ·
                  <button type="button" @click="finish('timeout')">timed out</button></p>
              @else
                <button class="btn btn-primary btn-block rp-off" type="button" disabled>Pay with M-Pesa (STK push)</button>
                <x-az.placeholder dependency="M-Pesa STK push" title="Pay from your phone with your PIN" style="margin-top:14px">
                  We'll send a payment prompt to {{ $maskedPhone }}; you just enter your M-Pesa PIN. Until then, pay by Paybill.
                </x-az.placeholder>
              @endif
            </section>
          @endif

          {{-- 2b. Paybill --}}
          <section class="a-card">
            <div class="a-sec-h"><h2>Pay by M-Pesa Paybill</h2></div>
            @if ($mpesa['paybill'])
              <ol class="a-timeline" style="margin-top:8px">
                <li><span class="dot">1</span><div><b>Lipa na M-Pesa › Pay Bill</b><span>On your phone, open M-Pesa and choose Pay Bill.</span></div></li>
                <li><span class="dot">2</span><div><b>Enter the details below</b><span>Account no. is {{ $mpesa['account_label'] }}.</span></div></li>
                <li><span class="dot">3</span><div><b>Tell us the M-Pesa code</b><span>Type the code from the M-Pesa message under "I've paid".</span></div></li>
              </ol>
              <dl class="a-dl rp-pay">
                <div><dt>Business no.</dt><dd class="num">{{ $mpesa['paybill'] }}</dd></div>
                <div><dt>Account no.</dt><dd class="num">{{ $mpesa['account'] ?? '—' }}</dd></div>
                <div><dt>Amount</dt><dd class="num" x-text="fmt(amount)">{{ $kes($startAmount) }}</dd></div>
              </dl>
            @else
              <x-az.placeholder dependency="Paybill number (owner decision)" :stage="2" title="Paybill number to be confirmed by Aimzone (owner decision)">
                Until it's confirmed, message us on WhatsApp for the payment details, then tell us the M-Pesa code below.
              </x-az.placeholder>
              <dl class="a-dl rp-pay">
                <div><dt>Account no. ({{ $mpesa['account_label'] }})</dt><dd class="num">{{ $mpesa['account'] ?? '—' }}</dd></div>
                <div><dt>Amount</dt><dd class="num" x-text="fmt(amount)">{{ $kes($startAmount) }}</dd></div>
              </dl>
              <a class="btn btn-ghost btn-block" href="{{ $whatsapp }}" target="_blank" rel="noopener" style="margin-top:12px">Ask on WhatsApp</a>
            @endif
          </section>
        </div>

        <div class="a-grid">
          {{-- 3. I've paid --}}
          @if ($canClaim)
            <section class="a-card" id="paid">
              <div class="a-sec-h"><h2>I've paid</h2></div>
              <p class="muted rp-small">Already paid by M-Pesa? Tell us the code from the confirmation message so we can record it faster.</p>
              <form method="POST" action="{{ route('account.repay.confirm', $loan) }}" class="rp-form">
                @csrf
                <x-az.field name="code" label="M-Pesa code" required placeholder="e.g. QJK8X2LM4P" maxlength="12" autocomplete="off" autocapitalize="characters" />
                <x-az.field name="amount" label="Amount paid (KES)" type="number" required step="0.01" min="1" :max="round($due, 2)" x-model.number="amount" :value="round($startAmount, 2)" />
                <x-az.field name="paid_from" label="Phone you paid from" type="tel" :value="$user->phone" hint="Only if it's not your registered number." />
                <button class="btn btn-primary btn-block" type="submit">Send for checking</button>
              </form>
            </section>
          @endif

          @include('home.account.repay._claims', ['claims' => $claims, 'loan' => $loan])

          <section class="a-card">
            <div class="a-sec-h"><h2>Payments received</h2><a href="{{ route('account.loan', $loan) }}">All</a></div>
            @include('home.account.repay._payments', ['repayments' => $progress['repayments']->take(4), 'loan' => $loan])
          </section>
        </div>
      </div>
    @endif
  @endif
@endsection
