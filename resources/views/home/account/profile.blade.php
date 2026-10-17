@extends('home.layouts.account', ['title' => 'Profile', 'tab' => 'profile'])

@php
  $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
  $avatar = $user->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) : null;
  $verification = match (true) {
    ! $isBorrower => null,
    $isVerified => ['Verified', 'green'],
    $user->kyc_status === 'submitted' => ['In review', 'amber'],
    $user->kyc_status === 'rejected' => ['Needs attention', 'red'],
    default => ['Not verified', 'grey'],
  };
  $pwErrors = $errors->getBag('updatePassword');
@endphp

@section('content')
  <section class="a-card a-profile">
    <span class="acct-avatar">@if ($avatar)<img src="{{ $avatar }}" alt="" />@else{{ $initials }}@endif
      @if ($isVerified && $user->hasRole('borrower'))@include('home.partials.verified-tick', ['class' => 'on-avatar'])@endif</span>
    <div style="min-width:0">
      <h1>{{ $user->name }}@if ($isVerified && $user->hasRole('borrower'))@include('home.partials.verified-tick')@endif</h1>
      <p>{{ $user->email }} · {{ $user->phone }}</p>
    </div>
  </section>

  <div class="a-grid two">
    <div class="a-grid">
      @if ($isBorrower)
        <section class="a-card">
          <div class="a-sec-h"><h2>Verification</h2><span class="badge-s {{ $verification[1] }}">{{ $verification[0] }}</span></div>
          <dl class="a-dl">
            @foreach ($kycSteps as $step)
              <div><dt>{{ $step['title'] }}</dt><dd>{{ $step['complete'] ? '✓' : 'To do' }}</dd></div>
            @endforeach
          </dl>
          @if (in_array($user->kyc_status, [null, 'rejected'], true))
            <a class="btn btn-primary btn-block" href="{{ route('kyc.personal') }}" style="margin-top:14px">{{ $user->kyc_status === 'rejected' ? 'Update and resubmit' : 'Complete verification' }}</a>
          @endif
        </section>

        <section class="a-card">
          <div class="a-sec-h"><h2>Your details</h2></div>
          <dl class="a-dl">
            <div><dt>{{ $user->id_type === 'passport' ? 'Passport' : 'National ID' }}</dt><dd>{{ $user->id_number ?? '—' }}</dd></div>
            <div><dt>KRA PIN</dt><dd>{{ $user->kra_pin ?? '—' }}</dd></div>
            <div><dt>Address</dt><dd>{{ $address ? trim($address->address_line_1 . ', ' . $address->state, ', ') : '—' }}</dd></div>
            <div><dt>M-Pesa (payouts)</dt><dd>{{ $mpesa?->mobile_number ?? '—' }}</dd></div>
            <div><dt>Next of kin</dt><dd>{{ $user->kin_name ? $user->kin_name . ' · ' . $user->kin_phone : '—' }}</dd></div>
            <div><dt>Guarantor</dt><dd>{{ $guarantor ? $guarantor->name . ' · ' . $guarantor->phone : '—' }}</dd></div>
          </dl>
          <p class="muted" style="font-size:13px;margin-top:12px">
            @if ($isVerified)Your details are verified. To change anything, message us on WhatsApp.@else You can change these in your verification.@endif
          </p>
        </section>
      @endif

      @if ($partner)
        <section class="a-card">
          <div class="a-sec-h"><h2>Partnership</h2><span class="badge-s {{ $partner->status === 'active' ? 'green' : 'grey' }}">{{ ucfirst($partner->status ?? 'active') }}</span></div>
          <dl class="a-dl">
            <div><dt>Partner</dt><dd>{{ $partner->name }}</dd></div>
            <div><dt>Type</dt><dd>{{ ucfirst($partner->type) }}</dd></div>
            <div><dt>Profit share</dt><dd>{{ rtrim(rtrim(number_format((float) $partner->profit_share_rate, 2), '0'), '.') }}%</dd></div>
            @if ((float) $partner->broker_rate > 0)<div><dt>Broker rate</dt><dd>{{ rtrim(rtrim(number_format((float) $partner->broker_rate, 2), '0'), '.') }}%</dd></div>@endif
            @if ($partner->bank_name)<div><dt>Payout bank</dt><dd>{{ $partner->bank_name }} · {{ $partner->bank_account_number }}</dd></div>@endif
          </dl>
        </section>
      @endif
    </div>

    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Change password</h2></div>
        @if (session('status') === 'password-updated')
          <div class="a-note ok" style="margin-bottom:14px"><div><b>Password changed</b>Use your new password next time you sign in.</div></div>
        @endif
        <form method="POST" action="{{ route('password.update') }}" class="stack" style="gap:14px">
          @csrf
          @method('PUT')
          <div class="field @if ($pwErrors->has('current_password')) invalid @endif">
            <label for="current_password">Current password</label>
            <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required />
            <span class="error">{{ $pwErrors->first('current_password') }}</span>
          </div>
          <div class="field @if ($pwErrors->has('password')) invalid @endif">
            <label for="password">New password</label>
            <input class="input" id="password" name="password" type="password" autocomplete="new-password" required />
            <span class="error">{{ $pwErrors->first('password') }}</span>
          </div>
          <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
          </div>
          <button class="btn btn-primary btn-block" type="submit">Update password</button>
        </form>
      </section>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-ghost btn-block" type="submit">Sign out</button>
      </form>
    </div>
  </div>
@endsection
