@extends('home.layouts.app', [
  'title' => 'Asset Finance — Aimzone Ventures',
  'description' => 'Borrow against a vehicle, property or machinery, and keep using it while you repay. We value the asset quickly and lend up to 70% of its value.',
  'active' => 'services',
])

@section('content')
  <section class="page-hero">
    <div class="wrap split">
      <div class="stack stack-lg">
        <span class="tag sand"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
            /></svg
          >Asset Finance</span
        >
        <h1>Unlock the value in what you own.</h1>
        <p class="lead">
          Borrow against a vehicle, property or machinery, and keep using it while you repay. We value the asset
          quickly and lend up to 70% of its value.
        </p>
        <div class="facts">
          <div class="fact"><b>Up to 70%</b><span>Loan-to-value</span></div>
          <div class="fact"><b>4× asset</b><span>Max facility</span></div>
          <div class="fact"><b>3 months</b><span>Term</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Apply now</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Talk to us</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/mombasa-highway-trucks.jpg') }}"
          alt="Container truck at the Port of Mombasa"
          width="1600"
          height="1200"
          loading="lazy"
          decoding="async"
        />
      </figure>
    </div>
  </section>
  <section class="section">
    <div class="wrap grid g2 gap-lg">
      <div class="card card-soft">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
            /></svg
        ></span>
        <h2 class="h-card">What it's for</h2>
        <ul class="ticks">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Working capital
            for growth
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Fleet or
            equipment expansion
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Refinancing
            expensive debt
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Large one-off
            expenses
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
            >Logbook, title deed or proof of ownership
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Comprehensive insurance
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Passport-size photo and signature
          </li>
        </ul>
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>How it works</h2></div>
      <div class="grid g4">
        <div class="step">
          <span class="n">01</span>
          <h3>Tell us about the asset</h3>
          <p class="muted text-sm">Vehicle, property or machinery, with proof of ownership.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Independent valuation</h3>
          <p class="muted text-sm">A valuer inspects the asset, usually within 48 hours.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>See your limit</h3>
          <p class="muted text-sm">Borrow up to 70% of the forced sale value.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Sign and receive</h3>
          <p class="muted text-sm">Security is registered and funds are released.</p>
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
            What types of assets can be used for financing?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            We accept real estate, vehicles, luxury assets, and other valuable collateral. Each case is evaluated
            individually based on asset value and condition.
          </p>
        </details>
        <details>
          <summary>
            What is loan-to-value?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            The loan as a share of the asset's value. We lend up to 70%, and borrowing less gets a better rate.
          </p>
        </details>
        <details>
          <summary>
            Can I keep using my vehicle?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">Yes. You keep using it while you repay; we register our interest as security.</p>
        </details>
        <details>
          <summary>
            How long can I borrow for?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">Up to 3 months, with monthly or end-of-term repayment.</p>
        </details>
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>Other services</h2></div>
      <div class="grid g3">
        <a class="card" href="{{ route('site.page', 'emergency-solutions') }}"
          ><span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
          ></span>
          <h3>Emergency Solutions</h3>
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
