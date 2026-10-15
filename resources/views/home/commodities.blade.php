@extends('home.layouts.app', [
  'title' => 'Commodities — Aimzone Ventures',
  'description' => 'Letters of credit, purchase order finance and commodity finance for importers, exporters and traders, so you can pay suppliers now and repay when the',
  'active' => 'services',
  'variant' => 'on-photo',
])

@section('content')
  <section class="page-hero page-hero-photo">
    <div class="wrap split">
      <div class="stack stack-lg">
        <span class="tag apricot"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
          >Commodities</span
        >
        <h1>Trade and commodity finance for East Africa.</h1>
        <p class="lead">
          Letters of credit, purchase order finance and commodity finance for importers, exporters and traders, so
          you can pay suppliers now and repay when the goods sell.
        </p>
        <div class="facts">
          <div class="fact"><b>Up to 80%</b><span>Of order value</span></div>
          <div class="fact"><b>90–120 d</b><span>Tenor</span></div>
          <div class="fact"><b>LC, PO, commodity</b><span>Instruments</span></div>
        </div>
        <div class="actions">
          <a class="btn btn-primary" href="{{ route('register') }}">Make an enquiry</a
          ><a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Talk to us</a>
        </div>
      </div>
      <figure class="media">
        <img
          src="{{ asset('assets/images/port-of-mombasa.jpg') }}"
          alt="Container cranes at the Port of Mombasa"
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
            <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
        ></span>
        <h2 class="h-card">What it's for</h2>
        <ul class="ticks">
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Paying overseas
            suppliers
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Agricultural
            harvest and stock
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Mineral and
            commodity trades
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Fulfilling
            large purchase orders
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
            >Certificate of incorporation and CR12
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Directors' IDs or passports
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Purchase order, contract or proforma invoice
          </li>
          <li>
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
            >Import declaration and insurance (for imports)
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
          <h3>Share the deal</h3>
          <p class="muted text-sm">Supplier, goods, value and shipment dates.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Term sheet</h3>
          <p class="muted text-sm">We offer up to 80% of the order value, with your deposit covering the rest.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>Supplier is paid</h3>
          <p class="muted text-sm">By letter of credit or direct payment through partner banks.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Repay from sales</h3>
          <p class="muted text-sm">Repay within the agreed tenor, typically 90–120 days.</p>
        </div>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head center reveal"><h2>Common questions</h2></div>
      <div class="faq">
        <details>
          <summary>
            What can you finance?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">Imports, exports and local trade in agricultural, mineral and other commodities.</p>
        </details>
        <details>
          <summary>
            How much deposit do I need?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">Usually 20% of the order value, depending on the deal.</p>
        </details>
        <details>
          <summary>
            Which currencies?<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">KES and USD. Exchange rates are locked when you approve a payment.</p>
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
        ><a class="card" href="{{ route('site.page', 'crb-solutions') }}"
          ><span class="chip sage"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
          ></span>
          <h3>CRB Solutions</h3>
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
