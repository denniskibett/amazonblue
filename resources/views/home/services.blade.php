@extends('home.layouts.app', [
  'title' => 'Services — Aimzone Ventures',
  'description' => 'Emergency bridge finance, asset-backed lending, CRB resolution and trade finance.',
  'active' => 'services',
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Four ways to find capital fast.</h1>
      <p class="lead">
        Emergency, asset-backed, credit rehabilitation and trade finance. All decided quickly, all handled with
        discretion.
      </p>
      <div class="pills">
        <a class="pill" href="{{ route('site.page', 'emergency-solutions') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
          >Emergency Bridge Finance</a
        ><a class="pill" href="{{ route('site.page', 'asset-finance') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
            /></svg
          >Asset-Backed Lending</a
        ><a class="pill" href="{{ route('site.page', 'crb-solutions') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
          >CRB Resolution</a
        ><a class="pill" href="{{ route('site.page', 'commodities') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
          >Trade Finance</a
        >
      </div>
    </div>
  </section>
  <section class="section" id="bridge">
    <div class="wrap split">
      <div class="stack">
        <span class="chip chip-lg"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
        ></span>
        <h2 class="reveal">Emergency Bridge Finance</h2>
        <p class="lead">
          Fast-track bridge financing for payroll, suppliers and urgent gaps, decided in under an hour.
        </p>
        <ul class="ticks ticks-grid">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Payroll,
            supplier and tax deadlines
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Collateral
            optional below set limits
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Funds to M-Pesa
            or your bank
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Extend or top
            up in the app
          </li>
        </ul>
        <div class="facts-row">
          <div><b>20% flat</b><span>10-day facility</span></div>
          <div><b>KES 100K–20M</b><span>Facility size</span></div>
          <div><b>30–45 min</b><span>Processing</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Request funding</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'emergency-solutions') }}">Full details</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/nairobi-cbd.jpg') }}"
          alt="Upper Hill skyline, Nairobi"
          width="1600"
          height="1200"
          loading="lazy"
          decoding="async"
        />
      </figure>
    </div>
  </section>
  <section class="section section-tint" id="asset">
    <div class="wrap split flip">
      <div class="stack">
        <span class="chip sand chip-lg"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
            /></svg
        ></span>
        <h2 class="reveal">Asset-Backed Lending</h2>
        <p class="lead">
          Borrow up to 70% of the value of property, vehicles and machinery, and keep using them while you repay.
        </p>
        <ul class="ticks ticks-grid">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Vehicles,
            property and machinery
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Independent
            valuation in 48 hours
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Keep using your
            asset
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Repay monthly
            or at maturity
          </li>
        </ul>
        <div class="facts-row">
          <div><b>Up to 70%</b><span>Loan-to-value</span></div>
          <div><b>4× asset</b><span>Max facility</span></div>
          <div><b>3 months</b><span>Term</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Apply now</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'asset-finance') }}">Full details</a>
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
  <section class="section" id="crb">
    <div class="wrap split">
      <div class="stack">
        <span class="chip sage chip-lg"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
        ></span>
        <h2 class="reveal">CRB Resolution</h2>
        <p class="lead">
          Credit bureau rehabilitation with full clearance and a bank certificate, handled in complete confidence.
        </p>
        <ul class="ticks ticks-grid">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Review of your
            full CRB report
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Settlement and
            negotiation with lenders
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Bank clearance
            certificate
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Advice to stay
            clear
          </li>
        </ul>
        <div class="facts-row">
          <div><b>24–48 h</b><span>Turnaround</span></div>
          <div><b>All lenders</b><span>Across Kenya</span></div>
          <div><b>100%</b><span>Confidential</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Restore credit</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'crb-solutions') }}">Full details</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/nairobi-entrepreneur.jpg') }}"
          alt="Kenyan shopkeeper restocking her kiosk"
          width="1600"
          height="1200"
          loading="lazy"
          decoding="async"
        />
      </figure>
    </div>
  </section>
  <section class="section section-tint" id="trade">
    <div class="wrap split flip">
      <div class="stack">
        <span class="chip apricot chip-lg"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
        ></span>
        <h2 class="reveal">Trade Finance</h2>
        <p class="lead">
          Letters of credit, purchase order and commodity finance for importers and exporters across East Africa.
        </p>
        <ul class="ticks ticks-grid">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Letters of
            credit through partner banks
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Purchase order
            financing
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Agricultural
            and mineral commodities
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Invoice
            discounting
          </li>
        </ul>
        <div class="facts-row">
          <div><b>Up to 80%</b><span>Of order value</span></div>
          <div><b>90–120 d</b><span>Tenor</span></div>
          <div><b>LC, PO, commodity</b><span>Instruments</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Make an enquiry</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'commodities') }}">Full details</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/port-of-mombasa.jpg') }}"
          alt="Container cranes at the Port of Mombasa"
          width="1600"
          height="1200"
          loading="lazy"
          decoding="async"
        />
      </figure>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head reveal">
        <h2>Four calm steps</h2>
        <p class="lead">From sign-up to funds, usually the same day.</p>
      </div>
      <div class="grid g4" data-carousel="step" aria-label="How it works">
        <div class="step">
          <div class="step-top">
            <span class="chip white"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6" /></svg></span
            ><span class="n">01</span>
          </div>
          <h3>Create an account</h3>
          <p class="muted text-sm">Register with your phone and confirm with a one-time code.</p>
        </div>
        <div class="step">
          <div class="step-top">
            <span class="chip white"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 16V4M6 10l6-6 6 6M4 20h16" /></svg></span
            ><span class="n">02</span>
          </div>
          <h3>Share your documents</h3>
          <p class="muted text-sm">ID or passport, a passport photo and your signature, securely, in minutes.</p>
        </div>
        <div class="step">
          <div class="step-top">
            <span class="chip white"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg></span
            ><span class="n">03</span>
          </div>
          <h3>Review your offer</h3>
          <p class="muted text-sm">Clear terms in plain language, with every fee shown upfront.</p>
        </div>
        <div class="step">
          <div class="step-top">
            <span class="chip white"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6M5 11.5v6C5 19 8 20 12 20s7-1 7-2.5v-6"
                /></svg></span
            ><span class="n">04</span>
          </div>
          <h3>Receive your funds</h3>
          <p class="muted text-sm">Straight to M-Pesa or your bank account.</p>
        </div>
      </div>
    </div>
  </section>
  <section class="section" id="faq">
    <div class="wrap">
      <div class="section-head center reveal">
        <h2>Questions, answered</h2>
        <p class="lead">Quick answers to what clients ask us most.</p>
      </div>
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
            Is my information kept confidential?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            Absolutely. We maintain strict confidentiality for all client information. Our services are designed
            with discretion and privacy as core principles.
          </p>
        </details>
        <details>
          <summary>
            How do I get started?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">
            Simply reach out through our contact form or call our emergency line. Our team will guide you through
            the process and find the right solution for your needs.
          </p>
        </details>
      </div>
    </div>
  </section>
  <section class="section pt-0">
    <div class="wrap">
      <div class="cta-band">
        <h2>Not sure which product fits?</h2>
        <p>Tell us what you need. A relationship officer will call you back within the hour.</p>
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
