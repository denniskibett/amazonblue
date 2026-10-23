@extends('home.layouts.account', ['title' => 'My account', 'tab' => 'home'])

@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $first = strtok($user->name, ' ');
  $hour = (int) now()->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
  $loan = $progress['loan'] ?? null;
  $stepsDone = collect($kycSteps)->where('complete', true)->count();
  $limitAmount = $limit['limit'];
  $ladderFill = $product ? max(8, min(100, round($limitAmount / $product['good_standing_limit'] * 100))) : 0;
  $statusBadge = [
    'pending' => ['Under review', 'amber'], 'approved' => ['Approved', 'blue'], 'disbursed' => ['Active', 'green'],
    'active' => ['Active', 'green'], 'overdue' => ['Overdue', 'red'], 'forbearance' => ['Payment plan', 'blue'],
    'defaulted' => ['Defaulted', 'red'], 'recovery' => ['In recovery', 'red'],
  ];
  $tick = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>';
@endphp

@section('content')
  <div class="acct-head">
    <h1>{{ $greeting }}, {{ $first }}@if ($isVerified)@include('home.partials.verified-tick')@endif</h1>
    <p>{{ now()->format('l, j F') }}</p>
  </div>

  {{-- verification --}}
  @unless ($isVerified)
    @if ($user->kyc_status === 'submitted')
      <div class="a-note warn">
        <div><b>We're verifying your details</b>Our team will call your next of kin and guarantor to confirm. You can borrow as soon as you're approved.</div>
      </div>
    @elseif ($user->kyc_status === 'rejected')
      <div class="a-note err">
        <div>
          <b>Your verification needs attention</b>
          {{ $user->kyc_review_notes ?: 'Please check your details and submit again.' }}
          <div><a class="btn btn-primary" href="{{ route('kyc.personal') }}">Update and resubmit</a></div>
        </div>
      </div>
    @else
      <div class="a-note info">
        <div style="flex:1">
          <b>{{ $isExistingClient ? 'Confirm your details once' : 'Verify your account to borrow' }}</b>
          {{ $isExistingClient
              ? 'We have a new verification for every client. Most of your details are already filled in; your current loan isn\'t affected.'
              : 'About 5 minutes: personal details, ID, income and two people we can call.' }}
          <div class="a-steps" aria-label="{{ $stepsDone }} of {{ count($kycSteps) }} steps done">
            @foreach ($kycSteps as $step)<i class="{{ $step['complete'] ? 'on' : '' }}"></i>@endforeach
          </div>
          <div><a class="btn btn-primary" href="{{ route('kyc.personal') }}">{{ $stepsDone ? 'Continue' : ($isExistingClient ? 'Confirm my details' : 'Start verification') }}</a></div>
        </div>
      </div>
    @endif
  @endunless

  {{-- complete your profile (owner, 23 Oct 2026): only for verified borrowers with something missing --}}
  @if ($isVerified && ($missingDetails = ($user->borrower?->missing() ?? ['income', 'employer', 'pay schedule'])))
    <div class="a-note info">
      <div style="flex:1"><b>Complete your profile</b>Tell us your {{ implode(', ', $missingDetails) }} so your limit and reminders fit you.
        <div><a class="btn btn-primary" href="{{ route('account.details') }}">Complete it now</a></div></div>
    </div>
  @endif

  {{-- hero: current loan, or what they can borrow --}}
  @if ($loan)
    @php
      $bal = $progress['balance'];
      [$badgeText, $badgeTone] = $statusBadge[$loan->status] ?? [ucfirst($loan->status), 'grey'];
      if ($progress['is_late']) { [$badgeText, $badgeTone] = ['Overdue', 'red']; }
      $sent = in_array($loan->status, ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery']);
      $daysLeft = $progress['due_date'] ? (int) now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($progress['due_date'])->startOfDay(), false) : null;
    @endphp
    <section class="a-hero {{ $progress['is_late'] ? 'red' : '' }}">
      <div class="hero-grid">
        <div>
          <span class="badge-s">{{ $badgeText }}</span>
          <p class="label" style="margin-top:14px">{{ $sent ? 'Amount due' : 'You asked for' }}</p>
          <p class="big">{{ $kes($sent ? ($bal['due'] ?? $loan->amount) : $loan->amount) }}</p>
          <p class="sub">
            @if (! $sent)
              {{ $loan->status === 'approved' ? 'Approved. Sending to your M-Pesa shortly.' : 'We\'re reviewing it and will send it to your M-Pesa once approved.' }}
            @elseif (! is_null($daysLeft))
              {{ $daysLeft > 0 ? 'Due ' . \Illuminate\Support\Carbon::parse($progress['due_date'])->format('j M') . ' · ' . $daysLeft . ' ' . \Illuminate\Support\Str::plural('day', $daysLeft) . ' left' : ($daysLeft === 0 ? 'Due today' : abs($daysLeft) . ' days late · a penalty applies') }}
            @endif
          </p>
        </div>
        @if ($sent)
          <div class="ring" style="--p: {{ $progress['progress'] }}"><span>{{ $progress['progress'] }}%<small>repaid</small></span></div>
        @endif
      </div>
      <div class="row">
        <a class="btn btn-light" href="{{ route('account.loan', $loan) }}">View loan</a>
        @if ($sent)<a class="btn btn-glass" href="{{ route('account.repay', $loan) }}">Repay</a>@endif
      </div>
    </section>
  @elseif ($product)
    <section class="a-hero">
      <p class="label">You can borrow up to</p>
      <p class="big">{{ $kes($limitAmount) }}</p>
      <p class="sub">{{ $product['name'] }} · {{ rtrim(rtrim(number_format($product['rate'], 2), '0'), '.') }}% flat · repay in {{ $product['period'] }}</p>
      <div class="row">
        @if (empty($blockers))
          <a class="btn btn-light" href="{{ route('account.borrow') }}">Borrow now</a>
        @else
          <a class="btn btn-light" href="{{ $isVerified ? route('account.borrow') : route('kyc.personal') }}">{{ $isVerified ? 'See why' : 'Verify to borrow' }}</a>
        @endif
        <a class="btn btn-glass" href="{{ route('account.loans') }}">My loans</a>
      </div>
    </section>
  @endif

  {{-- what the current (or just-ended) loan's state means: under review, approved, rejected, overdue, recovery... (App\Support\BorrowerLoanState) --}}
  @if ($stateLoan = \App\Support\BorrowerLoanState::homeLoan($user, $loan))
    @include('home.account.partials.loan-state', ['loan' => $stateLoan, 'progress' => $loan && $stateLoan->is($loan) ? $progress : app(\App\Services\LoanProgress::class)->for($stateLoan), 'onHome' => true])
  @endif

  {{-- borrowing limit --}}
  @if ($product)
    <div class="a-note {{ $limit['tier'] === 'good_standing' ? 'ok' : 'info' }}" role="status">
      <div style="flex:1">
        <b>Your limit: {{ $kes($limitAmount) }}</b>
        {{ match (true) {
            ($limit['capped_by'] ?? null) === 'risk' => 'Your recent repayments (rollovers, late or discounted loans) keep this below ' . $kes($limit['ladder']) . '. Pay your next loans in full and on time to raise it.',
            ($limit['capped_by'] ?? null) === 'income' => 'Based on your income: what you repay has to fit within a month\'s income. Talk to us if your income has changed.',
            ($limit['capped_by'] ?? null) === 'behaviour' => 'Based on the amounts you have repaid on time so far. Repay this loan on time to raise it.',
            $limit['tier'] === 'good_standing' => 'Good standing. Thanks for repaying on time; keep it up to keep this limit.',
            default => 'Repay on or before the due date to unlock up to ' . $kes($product['good_standing_limit']) . '.',
        } }}
        <div class="a-progress" style="margin-top:10px;background:rgba(27,43,107,.14)"><i style="width: {{ $ladderFill }}%"></i></div>
        <div class="a-scale" style="margin-top:6px"><span>{{ $kes(config('lending.first_time_limit')) }}</span><span>{{ $kes($product['good_standing_limit']) }}</span></div>
      </div>
    </div>
  @endif

  {{-- quick actions --}}
  <div class="a-actions">
    <a class="a-action" href="{{ route('account.borrow') }}"><span class="chip"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg></span>Borrow</a>
    <a class="a-action" href="{{ route('account.loans') }}"><span class="chip sage"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" /></svg></span>My loans</a>
    <a class="a-action" href="{{ $isVerified ? route('account.profile') : route('kyc.personal') }}"><span class="chip sand"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 12l2 2 4-4M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z" /></svg></span>{{ $isVerified ? 'Verified' : 'Verify' }}</a>
    <a class="a-action" href="{{ route('account.help') }}"><span class="chip apricot"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.8 7L4 20l1.1-4.9A8 8 0 1 1 21 12z" /></svg></span>Help</a>
  </div>

  <div class="a-grid two">
    {{-- recent money movements --}}
    <section class="a-card">
      <div class="a-sec-h"><h2>Recent activity</h2>@if ($activity->isNotEmpty())<a href="{{ route('account.loans') }}">All loans</a>@endif</div>
      @if ($activity->isEmpty())
        <p class="a-empty">Nothing here yet. Money you receive and payments you make will show up here.</p>
      @else
        <div class="a-list">
          @foreach ($activity as $item)
            <a class="a-row" href="{{ route('account.loan', $item['loan_id']) }}">
              <span class="chip {{ $item['kind'] === 'paid' ? 'sage' : 'cloud' }}">
                <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $item['kind'] === 'paid' ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M5 12l7 7 7-7' }}" /></svg>
              </span>
              <span class="t"><b>{{ $item['label'] }}</b><span>Loan #{{ str_pad($item['loan_id'], 5, '0', STR_PAD_LEFT) }}{{ $item['ref'] ? ' · ' . $item['ref'] : '' }}</span></span>
              <span class="amt {{ $item['kind'] === 'sent' ? 'in' : '' }}">{{ $item['kind'] === 'sent' ? '+' : '' }}{{ $kes($item['amount']) }}<small>{{ \Illuminate\Support\Carbon::parse($item['date'])->format('j M Y') }}</small></span>
            </a>
          @endforeach
        </div>
      @endif
    </section>

    <div class="a-grid">
      {{-- repayment journey: how each loan ended, oldest to newest --}}
      @php
        $steps = collect($journey['history'])->reject(fn ($h) => $h['status'] === 'running')->take(-12)->values();
        $closedCount = $journey['factors']['closed'];
        $onTimeCount = $journey['factors']['on_time'];
        [$recordLabel, $recordTone] = match (true) {
          $closedCount === 0 => ['Just starting', 'info'],
          $journey['overall'] >= 80 => ['Excellent', 'ok'],
          $journey['overall'] >= 65 => ['Good', 'ok'],
          $journey['overall'] >= 50 => ['Building', 'info'],
          default => ['Needs attention', 'warn'],
        };
        $tone = ['on_time' => 'ok', 'late' => 'warn', 'discounted' => 'warn', 'rolled' => 'info', 'written_off' => 'err', 'overdue' => 'err'];
      @endphp
      @if ($steps->isNotEmpty())
        <section class="a-card">
          <div class="a-sec-h"><h2>Your repayment journey</h2><span class="journey-badge {{ $recordTone }}">{{ $recordLabel }}</span></div>
          <p class="muted" style="font-size:13.5px">{{ $onTimeCount }} of {{ $closedCount }} {{ \Illuminate\Support\Str::plural('loan', $closedCount) }} paid on time</p>
          <ol class="journey" aria-label="Your last {{ $steps->count() }} loans, oldest first">
            @foreach ($steps as $step)
              <li class="{{ $tone[$step['status']] ?? '' }}" title="{{ \Illuminate\Support\Carbon::parse($step['borrow_date'])->format('j M Y') }} · {{ $kes($step['amount']) }} · {{ $step['label'] }}"><span class="sr-only">{{ $kes($step['amount']) }}: {{ $step['label'] }}</span></li>
            @endforeach
          </ol>
          <div class="a-list" style="margin-top:6px">
            @foreach ($steps->reverse()->take(3) as $step)
              <a class="a-row" href="{{ route('account.loan', $step['loan_id']) }}">
                <span class="journey-dot {{ $tone[$step['status']] ?? '' }}" aria-hidden="true"></span>
                <span class="t"><b>{{ $step['label'] }}</b><span>Loan #{{ str_pad($step['loan_id'], 5, '0', STR_PAD_LEFT) }} · {{ \Illuminate\Support\Carbon::parse($step['borrow_date'])->format('j M Y') }}</span></span>
                <span class="amt">{{ $kes($step['amount']) }}</span>
              </a>
            @endforeach
          </div>
          <p class="muted" style="font-size:12.5px;margin-top:10px">Paying in full, by M-Pesa, on or before the due date raises your limit. Rollovers and discounts don't count as paying on time.</p>
        </section>
      @endif

      {{-- repay: call to action to the repay screen + "I've paid" claims from this session (Concerns\BorrowerRepayment) --}}
      @if ($loan && in_array($loan->status, ['disbursed', 'active', 'overdue', 'forbearance', 'defaulted', 'recovery']))
        <section class="a-card" id="repay">
          <div class="rp-cta">
            <span class="chip {{ $progress['is_late'] || in_array($loan->status, ['defaulted', 'recovery']) ? 'apricot' : 'sage' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zM3 10h18M7 15h4" /></svg></span>
            <span class="t">
              <b>Repay by M-Pesa</b>
              <span>{{ in_array($loan->status, ['defaulted', 'recovery']) ? 'Your loan is with our recovery team. See how to pay.' : 'Pay all or part of ' . $kes($progress['balance']['due'] ?? 0) . ', then tell us the M-Pesa code.' }}</span>
            </span>
          </div>
          <a class="btn btn-primary btn-block" style="margin-top:14px" href="{{ route('account.repay', $loan) }}">Repay</a>
        </section>
        @include('home.account.repay._claims', ['loan' => $loan])
      @endif
    </div>
  </div>

  @include('home.business.partials.account-card')
@endsection
