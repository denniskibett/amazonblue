@extends('home.layouts.app', [
  'title' => 'Company — Aimzone Ventures',
  'description' => 'Who we are and how we work.',
  'active' => 'company',
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Calm capital for urgent moments.</h1>
      <p class="lead">
        We exist for the moments when a good business needs money fast, and deserves to be treated well while it
        gets it.
      </p>
      <div class="actions">
        <a class="btn btn-primary" href="#how">How we work</a
        ><a class="btn btn-ghost" href="#invest">Invest with us</a>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap split">
      <figure class="media">
        <img
          src="{{ asset('assets/images/team-nairobi.jpg') }}"
          alt="Britam Tower and the Upper Hill business district, Nairobi"
          width="1600"
          height="1200"
          loading="lazy"
          decoding="async"
        />
      </figure>
      <div class="stack">
        <h2 class="reveal">A digital emergency fund, built in Nairobi.</h2>
        <p class="lead">
          Aimzone Ventures is a digital emergency fund and trade finance private credit firm. We help businesses
          across East Africa bridge urgent gaps, unlock the value of their assets and trade with confidence.
        </p>
        <div class="grid mini-stats">
          <div class="mini-stat"><b class="num">2025</b><span class="muted text-xs">Founded</span></div>
          <div class="mini-stat"><b class="num">500+</b><span class="muted text-xs">Clients served</span></div>
          <div class="mini-stat"><b class="num">KES 2B+</b><span class="muted text-xs">Capital deployed</span></div>
          <div class="mini-stat"><b class="num">24/7</b><span class="muted text-xs">Human support</span></div>
        </div>
      </div>
    </div>
  </section>
  <section class="section section-tint" id="how">
    <div class="wrap">
      <div class="section-head reveal">
        <h2>Soft, clear and discreet</h2>
        <p class="lead">Money is stressful. We try to be the calmest part of your day.</p>
      </div>
      <div class="grid g3">
        <div class="card card-soft">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14h3v5H4zM17 14h3v5h-3z" /></svg
          ></span>
          <h3>Soft</h3>
          <p>Human support around the clock. Gentle reminders, never threats. We work things out together.</p>
        </div>
        <div class="card card-soft">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          ></span>
          <h3>Clear</h3>
          <p>Plain-language offers, honest pricing and no surprises in the small print.</p>
        </div>
        <div class="card card-soft">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          ></span>
          <h3>Discreet</h3>
          <p>Confidential by default. Your information is encrypted and only seen by those who need it.</p>
        </div>
      </div>
    </div>
  </section>
  <section class="section section-tint invest" id="invest">
    <div class="wrap">
      <div class="split invest-intro">
        <div class="stack">
          <span class="kicker">For investors</span>
          <h2 class="reveal">Invest in the capital that keeps East Africa trading.</h2>
          <p class="lead">
            We partner with family offices, institutions and qualified individuals who want exposure to short-tenor,
            secured private credit across Kenya and the region.
          </p>
          <ul class="ticks">
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Short-tenor
              facilities backed by assets, receivables or goods
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Credit
              committee review and ongoing portfolio monitoring
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Regular
              reporting on deployment, repayments and performance
            </li>
          </ul>
          <div class="actions">
            <a class="btn btn-primary" href="{{ route('site.page', 'contact') }}?topic=investment#message">Talk to us about investing</a
            ><a class="btn btn-ghost" href="mailto:investors@aimzoneventures.com?subject=Investment%20enquiry"
              >Email the team</a
            >
          </div>
        </div>
        <figure class="media">
          <img
            src="{{ asset('assets/images/invest-mombasa-port.jpg') }}"
            alt="Ships and containers at the Port of Mombasa"
            width="1600"
            height="1200"
            loading="lazy"
            decoding="async"
          />
          <div class="float"><small>Focus</small><b>Trade &amp; working capital</b></div>
        </figure>
      </div>
      <div class="grid g3 invest-options">
        <a class="card card-soft" href="{{ route('site.page', 'contact') }}?topic=investment#message"
          ><span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6M5 11.5v6C5 19 8 20 12 20s7-1 7-2.5v-6"
              /></svg
          ></span>
          <h3>Private credit notes</h3>
          <p>Fixed-term participation in a diversified book of bridge and asset-backed facilities.</p>
          <span class="more"
            >Enquire
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" /></svg></span></a
        ><a class="card card-soft" href="{{ route('site.page', 'contact') }}?topic=investment#message"
          ><span class="chip apricot"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
          ></span>
          <h3>Trade finance participation</h3>
          <p>Co-fund letters of credit and purchase orders secured by the goods being traded.</p>
          <span class="more"
            >Enquire
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" /></svg></span></a
        ><a class="card card-soft" href="{{ route('site.page', 'contact') }}?topic=investment#message"
          ><span class="chip sage"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
              /></svg
          ></span>
          <h3>Strategic partnerships</h3>
          <p>Co-lending, equity and distribution partnerships with funds, banks and DFIs.</p>
          <span class="more"
            >Enquire
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
      </div>
      <div class="note">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
        ><span
          >Investment opportunities are available only to eligible investors and are subject to due diligence and
          documentation. Capital is at risk and past performance is not a guide to future returns. Nothing on this
          page is an offer or solicitation.</span
        >
      </div>
    </div>
  </section>
  <section class="section" id="careers">
    <div class="wrap">
      <div class="split band-mist">
        <div class="stack stack-sm">
          <span class="chip white"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
              /></svg
          ></span>
          <h2>Work with us</h2>
          <p class="lead text-ink">We're hiring credit analysts, relationship officers and engineers in Nairobi.</p>
        </div>
        <div class="actions actions-end"><a class="btn btn-primary" href="{{ route('site.page', 'careers') }}">See open roles</a></div>
      </div>
    </div>
  </section>
@endsection
