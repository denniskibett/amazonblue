@extends('home.layouts.app', [
  'title' => 'Emergency Solutions — Aimzone Ventures',
  'description' => 'When payroll is due, a supplier won\'t wait or a client pays late, our bridge facility closes the gap fast, with complete discretion.',
  'active' => 'services',
  'variant' => 'on-photo',
])

@section('content')
  <section class="page-hero page-hero-photo">
    <div class="wrap split">
      <div class="stack stack-lg">
        <span class="tag"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
          >Emergency Solutions</span
        >
        <h1>Emergency funding, decided in under an hour.</h1>
        <p class="lead">
          When payroll is due, a supplier won't wait or a client pays late, our bridge facility closes the gap fast,
          with complete discretion.
        </p>
        <div class="facts">
          <div class="fact"><b>20% flat</b><span>10-day facility</span></div>
          <div class="fact"><b>KES 100K–20M</b><span>Facility size</span></div>
          <div class="fact"><b>30–45 min</b><span>Processing</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Request funding</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Talk to us</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/nairobi-cbd.jpg') }}"
          alt="Upper Hill skyline, Nairobi"
          width="1600"
          height="1200"
          loading="eager"
          fetchpriority="high"
          decoding="async"
        />
      </figure>
    </div>
  </section>
  <section class="section">
    <div class="wrap grid g2 gap-lg">
      <div class="card card-soft">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
        ></span>
        <h2 class="h-card">What it's for</h2>
        <ul class="ticks">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Payroll and
            statutory deductions
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Supplier and
            stock payments
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Tax and licence
            deadlines
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Bridging a late
            client payment
          </li>
        </ul>
      </div>
      <div class="card card-soft">
        <span class="chip sand"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
        ></span>
        <h2 class="h-card">What you'll need</h2>
        <ul class="ticks docs">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >National ID or passport
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Passport-size photo and signature
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>6
            months of M-Pesa or bank statements
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Business registration (for companies)
          </li>
        </ul>
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>How it works</h2></div>
      <div class="grid g4" data-carousel="step" aria-label="How it works">
        <div class="step">
          <span class="n">01</span>
          <h3>Apply online</h3>
          <p class="muted text-sm">Choose an amount and term, and tell us what it's for.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Quick review</h3>
          <p class="muted text-sm">A credit officer reviews your application, usually within 30–45 minutes.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>Sign your offer</h3>
          <p class="muted text-sm">Read the terms in plain language and sign with a one-time code.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Receive funds</h3>
          <p class="muted text-sm">Money is sent to M-Pesa or your bank right away.</p>
        </div>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head center reveal"><h2>Common questions</h2></div>
      <div class="faq">
        <details open>
          <summary>
            How quickly can I get emergency funding?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            Most emergency funding requests are processed within 24 hours. We prioritize urgent cases and can
            expedite based on your specific situation.
          </p>
        </details>
        <details>
          <summary>
            How much can I borrow?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">From KES 100,000 up to KES 20 million, depending on your profile and any collateral.</p>
        </details>
        <details>
          <summary>
            Do I need collateral?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            Not for smaller amounts within your pre-approved limit. Larger facilities may need security.
          </p>
        </details>
        <details>
          <summary>
            Can I repay early?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">Yes, any time. The facility fee is fixed, so there are no surprises.</p>
        </details>
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>Other services</h2></div>
      <div class="grid g3" data-carousel="service" aria-label="Other services">
        <a class="card" href="{{ route('site.page', 'asset-finance') }}"
          ><span class="chip sand"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
              /></svg
          ></span>
          <h3>Asset Finance</h3>
          <span class="more"
            >View
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" /></svg></span></a
        ><a class="card" href="{{ route('site.page', 'crb-solutions') }}"
          ><span class="chip sage"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
          ></span>
          <h3>CRB Solutions</h3>
          <span class="more"
            >View
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" /></svg></span></a
        ><a class="card" href="{{ route('site.page', 'commodities') }}"
          ><span class="chip apricot"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
          ></span>
          <h3>Commodities</h3>
          <span class="more"
            >View
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
      </div>
    </div>
  </section>
  <section class="section pt-0">
    <div class="wrap">
      <div class="cta-band">
        <h2>Ready when you need us.</h2>
        <p>Apply in minutes, or talk to a relationship officer first.</p>
        <div class="actions actions-center">
          <a class="btn btn-light" href="{{ route('register') }}">Get started</a
          ><a class="btn btn-outline-light" href="{{ route('site.page', 'contact') }}">Talk to us</a>
        </div>
        <span class="small-lock"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          >100% confidential consultation</span
        >
      </div>
    </div>
  </section>
@endsection
