@extends('home.layouts.account', ['title' => 'Share your experience', 'tab' => 'home'])

{{-- Testimonial (owner, 1 Nov 2026): one per borrower, editable; every save goes back to staff for approval
     before it shows on the website (TestimonialController, Staff\TestimonialsController). --}}
@php
  $t = $testimonial;
  $ratings = [5 => '5 – Excellent', 4 => '4 – Good', 3 => '3 – Okay', 2 => '2 – Poor', 1 => '1 – Very poor'];
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.home') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Home</a>
    <div class="acct-head">
      <h1>Share your experience</h1>
      <p>Tell others what borrowing from Aimzone was like</p>
    </div>
  </div>

  @if ($t)
    @if ($t->status === 'approved')
      <x-az.alert type="ok" title="On our website">Thank you. Your words are on the Aimzone website. If you change them, our team reads them again first.</x-az.alert>
    @elseif ($t->status === 'pending')
      <x-az.alert type="info" title="Waiting for our team">We read every message before it appears on the website.</x-az.alert>
    @else
      <x-az.alert type="warn" title="Not on our website">Our team chose not to show this one. You can change it and send it again.</x-az.alert>
    @endif
  @endif

  <section class="a-card" style="max-width:640px">
    <form method="POST" action="{{ route('account.testimonial.save') }}">
      @csrf
      <x-az.field name="rating" label="How was it?" type="select" :options="$ratings" :value="$t?->rating ?? 5" required />
      <x-az.field name="body" label="Your words" type="textarea" required rows="4" minlength="10" maxlength="400" :value="$t?->body"
        placeholder="For example: the money came the same day and the team was kind when I needed more time" hint="Up to 400 characters." />
      <x-az.field name="display_name" label="Show my name as" required maxlength="60" :value="$t?->display_name ?? $defaultName"
        hint="Your first name and initial by default." />
      <x-az.field name="display_role" label="What you do, or your town (optional)" maxlength="80" :value="$t?->display_role"
        placeholder="For example: Entrepreneur, Nakuru" />
      <label class="a-terms" style="margin-top:12px">
        <input type="checkbox" name="consent" value="1" required @checked(old('consent', $t ? 1 : null)) />
        <span>Aimzone may show this on its website.</span>
      </label>
      @error('consent')<p class="error">{{ $message }}</p>@enderror
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">{{ $t ? 'Save changes' : 'Send' }}</button>
      <p class="muted" style="font-size:12.5px;margin-top:10px">We never show your phone, email or loan amounts.</p>
    </form>
  </section>
@endsection
