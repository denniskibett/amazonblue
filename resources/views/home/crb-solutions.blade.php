@extends('home.layouts.app', [
  'title' => 'CRB Solutions — Aimzone Ventures',
  'description' => 'A credit bureau listing can block loans, jobs and contracts. We settle or restructure the debt behind it and get you a clearance certificate, fast and',
  'active' => 'services',
  'variant' => 'on-photo',
])

@section('content')
  <section class="page-hero page-hero-photo">
    <div class="wrap split">
      <div class="stack stack-lg">
        <span class="tag sage"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
          >CRB Solutions</span
        >
        <h1>Clear your CRB listing, quietly.</h1>
        <p class="lead">
          A credit bureau listing can block loans, jobs and contracts. We settle or restructure the debt behind it
          and get you a clearance certificate, fast and in complete confidence.
        </p>
        <div class="facts">
          <div class="fact"><b>24–48 h</b><span>Turnaround</span></div>
          <div class="fact"><b>All lenders</b><span>Across Kenya</span></div>
          <div class="fact"><b>100%</b><span>Confidential</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Restore credit</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Talk to us</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/nairobi-entrepreneur.jpg') }}"
          alt="Kenyan shopkeeper restocking her kiosk"
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
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
        ></span>
        <h2 class="h-card">What it's for</h2>
        <ul class="ticks">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Negative
            listing blocking a bank loan
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Clearance
            needed for a tender or job
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Old debts you
            want resolved properly
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Restoring your
            credit standing
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
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>Your
            latest CRB report (or we can request it)
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Details of the listed debt
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
      <div class="grid g4" data-carousel="step" aria-label="How it works">
        <div class="step">
          <span class="n">01</span>
          <h3>Share your report</h3>
          <p class="muted text-sm">We review your CRB report and the listing behind it.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>We negotiate</h3>
          <p class="muted text-sm">We settle or restructure the debt with the lender.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>Clearance issued</h3>
          <p class="muted text-sm">The lender updates the bureau and you get a clearance certificate.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Stay clear</h3>
          <p class="muted text-sm">We help you plan repayments so it doesn't happen again.</p>
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
            What is the CRB clearance process?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            We work directly with Kenyan credit bureaus to resolve negative listings. The process typically takes
            24-48 hours and is completely confidential.
          </p>
        </details>
        <details>
          <summary>
            How long does clearance take?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">Usually 24–48 hours once the debt is settled.</p>
        </details>
        <details>
          <summary>
            Will anyone know?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">No. Everything is handled confidentially between you, us and the lender.</p>
        </details>
        <details>
          <summary>
            Which lenders do you work with?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">All Kenyan lenders that report to licensed credit reference bureaus.</p>
        </details>
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal"><h2>Other services</h2></div>
      <div class="grid g3" data-carousel="service" aria-label="Other services">
        <a class="card" href="{{ route('site.page', 'emergency-solutions') }}"
          ><span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
          ></span>
          <h3>Emergency Solutions</h3>
          <span class="more"
            >View
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" /></svg></span></a
        ><a class="card" href="{{ route('site.page', 'asset-finance') }}"
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
