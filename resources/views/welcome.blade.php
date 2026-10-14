@extends('home.layouts.app', [
  'title' => 'Aimzone Ventures — Emergency funding and trade finance in Kenya',
  'description' => 'Emergency bridge finance, asset-backed lending, CRB resolution and trade finance for businesses across East Africa. Fast, discreet, from Nairobi.',
  'variant' => 'on-photo',
])

@section('content')
  <section class="hero" aria-label="Our services">
    <div class="wrap" data-slider>
      <div class="slides">
        <div
          class="slide is-active"
          id="slide-bridge"
          role="tabpanel"
          aria-labelledby="tab-bridge"
          aria-hidden="false"
        >
          <div class="slide-copy">
            <span class="tag"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
              >Emergency Bridge Finance</span
            >
            <h1 class="slide-title">Capital in minutes, not weeks.</h1>
            <p class="lead">
              Fast-track bridge financing for payroll, suppliers and urgent gaps, decided in under an hour.
            </p>
            <div class="facts">
              <div class="fact"><b>20% flat</b><span>10-day facility</span></div>
              <div class="fact"><b>KES 100K–20M</b><span>Facility size</span></div>
              <div class="fact"><b>30–45 min</b><span>Processing</span></div>
            </div>
            <div class="actions">
              <a class="btn btn-primary" href="{{ route('register') }}">Request funding</a
              ><a class="btn btn-ghost" href="{{ route('site.page', 'emergency-solutions') }}">Learn more</a>
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
            <div class="float"><small>Approved</small><b>KES 2,500,000</b></div>
          </figure>
        </div>
        <div class="slide" id="slide-asset" role="tabpanel" aria-labelledby="tab-asset" aria-hidden="true">
          <div class="slide-copy">
            <span class="tag sand"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
                /></svg
              >Asset-Backed Lending</span
            >
            <h2 class="slide-title">Unlock the value in what you own.</h2>
            <p class="lead">
              Borrow up to 70% of the value of property, vehicles and machinery, and keep using them while you
              repay.
            </p>
            <div class="facts">
              <div class="fact"><b>Up to 70%</b><span>Loan-to-value</span></div>
              <div class="fact"><b>4× asset</b><span>Max facility</span></div>
              <div class="fact"><b>3 months</b><span>Term</span></div>
            </div>
            <div class="actions">
              <a class="btn btn-primary" href="{{ route('register') }}">Apply now</a
              ><a class="btn btn-ghost" href="{{ route('site.page', 'asset-finance') }}">Learn more</a>
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
            <div class="float"><small>Valuation</small><b>48 hours</b></div>
          </figure>
        </div>
        <div class="slide" id="slide-crb" role="tabpanel" aria-labelledby="tab-crb" aria-hidden="true">
          <div class="slide-copy">
            <span class="tag sage"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
              >CRB Resolution</span
            >
            <h2 class="slide-title">A clean slate, quietly.</h2>
            <p class="lead">
              Credit bureau rehabilitation with full clearance and a bank certificate, handled in complete
              confidence.
            </p>
            <div class="facts">
              <div class="fact"><b>24–48 h</b><span>Turnaround</span></div>
              <div class="fact"><b>All lenders</b><span>Across Kenya</span></div>
              <div class="fact"><b>100%</b><span>Confidential</span></div>
            </div>
            <div class="actions">
              <a class="btn btn-primary" href="{{ route('register') }}">Restore credit</a
              ><a class="btn btn-ghost" href="{{ route('site.page', 'crb-solutions') }}">Learn more</a>
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
            <div class="float"><small>Cleared in</small><b>36 hours</b></div>
          </figure>
        </div>
        <div class="slide" id="slide-trade" role="tabpanel" aria-labelledby="tab-trade" aria-hidden="true">
          <div class="slide-copy">
            <span class="tag apricot"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
              >Trade Finance</span
            >
            <h2 class="slide-title">Move goods across Africa with confidence.</h2>
            <p class="lead">
              Letters of credit, purchase order and commodity finance for importers and exporters across East
              Africa.
            </p>
            <div class="facts">
              <div class="fact"><b>Up to 80%</b><span>Of order value</span></div>
              <div class="fact"><b>90–120 d</b><span>Tenor</span></div>
              <div class="fact"><b>LC, PO, commodity</b><span>Instruments</span></div>
            </div>
            <div class="actions">
              <a class="btn btn-primary" href="{{ route('register') }}">Make an enquiry</a
              ><a class="btn btn-ghost" href="{{ route('site.page', 'commodities') }}">Learn more</a>
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
            <div class="float"><small>LC issued</small><b>USD 334,880</b></div>
          </figure>
        </div>
      </div>
      <div class="slider-ui" aria-label="Slide controls">
        <button class="slider-arrow" type="button" data-step="-1" aria-label="Previous slide">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" /></svg>
        </button>
        <div class="slider-dots">
          <button class="slider-dot" type="button" aria-label="Show Emergency Bridge Finance"><i></i></button
          ><button class="slider-dot" type="button" aria-label="Show Asset-Backed Lending"><i></i></button
          ><button class="slider-dot" type="button" aria-label="Show CRB Resolution"><i></i></button
          ><button class="slider-dot" type="button" aria-label="Show Trade Finance"><i></i></button>
        </div>
        <button class="slider-arrow" type="button" data-step="1" aria-label="Next slide">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" /></svg>
        </button>
      </div>
      <div class="tabs" role="tablist" aria-label="Choose a service">
        <button
          class="tab"
          type="button"
          role="tab"
          id="tab-bridge"
          aria-controls="slide-bridge"
          aria-selected="true"
        >
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg></span
          >Emergency Bridge Finance<span class="progress"><i></i></span></button
        ><button
          class="tab"
          type="button"
          role="tab"
          id="tab-asset"
          aria-controls="slide-asset"
          aria-selected="false"
        >
          <span class="chip sand"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
              /></svg></span
          >Asset-Backed Lending<span class="progress"><i></i></span></button
        ><button class="tab" type="button" role="tab" id="tab-crb" aria-controls="slide-crb" aria-selected="false">
          <span class="chip sage"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg></span
          >CRB Resolution<span class="progress"><i></i></span></button
        ><button
          class="tab"
          type="button"
          role="tab"
          id="tab-trade"
          aria-controls="slide-trade"
          aria-selected="false"
        >
          <span class="chip apricot"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg></span
          >Trade Finance<span class="progress"><i></i></span>
        </button>
      </div>
    </div>
  </section>
  <section class="stats" aria-label="At a glance">
    <div class="wrap grid g4">
      <div class="stat">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
        ></span>
        <div><b class="num" data-count="98" data-suf="%">98%</b><span>Client satisfaction</span></div>
      </div>
      <div class="stat">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2" /></svg
        ></span>
        <div><b class="num" data-count="24" data-suf="h">24h</b><span>Average processing</span></div>
      </div>
      <div class="stat">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
            /></svg
        ></span>
        <div><b class="num" data-count="500" data-suf="+">500+</b><span>Clients served</span></div>
      </div>
      <div class="stat">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path
              d="M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6M5 11.5v6C5 19 8 20 12 20s7-1 7-2.5v-6"
            /></svg
        ></span>
        <div>
          <b class="num" data-count="2" data-pre="KES " data-suf="B+">KES 2B+</b><span>Capital deployed</span>
        </div>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head reveal">
        <h2>Where you find capital fast</h2>
        <p class="lead">Financing built for time-critical business needs.</p>
      </div>
      <div class="grid g3">
        <a class="card" href="{{ route('site.page', 'emergency-solutions') }}"
          ><span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
          ></span>
          <h3>Emergency liquidity</h3>
          <p>Fast-track bridge financing within 24 hours for urgent needs.</p>
          <span class="more"
            >Learn more
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="card" href="{{ route('site.page', 'crb-solutions') }}"
          ><span class="chip sage"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
          ></span>
          <h3>CRB resolution</h3>
          <p>Credit bureau rehabilitation, handled with complete discretion.</p>
          <span class="more"
            >Learn more
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="card" href="{{ route('site.page', 'asset-finance') }}"
          ><span class="chip sand"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path
                d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
              /></svg
          ></span>
          <h3>Asset-based lending</h3>
          <p>Use real estate, vehicles and machinery to unlock capital.</p>
          <span class="more"
            >Learn more
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="card" href="{{ route('site.page', 'commodities') }}"
          ><span class="chip apricot"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
          ></span>
          <h3>Commodity financing</h3>
          <p>Agricultural, mineral and trade commodity solutions.</p>
          <span class="more"
            >Learn more
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="card" href="{{ route('site.page', 'contact') }}"
          ><span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14h3v5H4zM17 14h3v5h-3z" /></svg
          ></span>
          <h3>Support around the clock</h3>
          <p>Real people answer, day and night, including weekends.</p>
          <span class="more"
            >Learn more
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="card" href="{{ route('site.page', 'privacy') }}"
          ><span class="chip cloud"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          ></span>
          <h3>Confidential by default</h3>
          <p>Your information is encrypted and only seen by those who need it.</p>
          <span class="more"
            >Learn more
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap wrap-wide">
      <div class="section-head center reveal">
        <h2>Flexible financial solutions</h2>
        <p class="lead">Plans designed for every financial need.</p>
      </div>
      <div class="grid g4">
        <div class="plan featured">
          <div class="top">
            <span class="chip chip-glass"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg></span
            ><span class="badge">Most popular</span>
          </div>
          <h3>Emergency Bridge Finance</h3>
          <div>
            <div class="price">20% flat</div>
            <div class="per">10-day facility</div>
          </div>
          <ul class="ticks">
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>KES 100K –
              KES 20 million
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Collateral
              optional on amount
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>30–45 minutes
              processing
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>24/7
              emergency support
            </li>
          </ul>
          <a class="btn btn-light btn-block" href="{{ route('register') }}">Request funding</a>
        </div>
        <div class="plan">
          <div class="top">
            <span class="chip cloud"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
                /></svg
            ></span>
          </div>
          <h3>Asset-Backed Lending</h3>
          <div>
            <div class="price">Up to 70% LTV</div>
            <div class="per">Up to 3 months</div>
          </div>
          <ul class="ticks">
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>4× the asset
              value
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Fast
              valuation and disbursement
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Flexible
              repayment terms
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Property,
              vehicles and machinery
            </li>
          </ul>
          <a class="btn btn-primary btn-block" href="{{ route('register') }}">Apply now</a>
        </div>
        <div class="plan">
          <div class="top">
            <span class="chip cloud"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
            ></span>
          </div>
          <h3>CRB Bailout</h3>
          <div>
            <div class="price">24–48 hours</div>
            <div class="per">Credit rehabilitation</div>
          </div>
          <ul class="ticks">
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Full CRB
              clearance
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>100%
              confidential
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Bank
              clearance certificate
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>All Kenyan
              lenders supported
            </li>
          </ul>
          <a class="btn btn-primary btn-block" href="{{ route('register') }}">Restore credit</a>
        </div>
        <div class="plan">
          <div class="top">
            <span class="chip cloud"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
            ></span>
          </div>
          <h3>Trade Finance</h3>
          <div>
            <div class="price">Custom</div>
            <div class="per">Tailor-made credit for your business</div>
          </div>
          <ul class="ticks">
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Letters of
              credit
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Purchase
              order financing
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Agricultural
              commodity finance
            </li>
            <li>
              <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12l4 4 10-10" /></svg>Import and
              export solutions
            </li>
          </ul>
          <a class="btn btn-primary btn-block" href="{{ route('register') }}">Make an enquiry</a>
        </div>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head reveal">
        <h2>Compare at a glance</h2>
        <p class="lead">The key terms of each product, side by side.</p>
      </div>
      <div class="table-wrap" tabindex="0" role="region" aria-label="Product comparison">
        <table>
          <thead>
            <tr>
              <th scope="col">Product</th>
              <th scope="col">Loan term</th>
              <th scope="col">Interest</th>
              <th scope="col">Max facility</th>
              <th scope="col">Collateral</th>
              <th scope="col">Processing</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <span class="with-ic"
                  ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg
                  >Emergency Bridge Finance</span
                >
              </td>
              <td class="num">10 days</td>
              <td class="num">20% flat</td>
              <td class="num">KES 20M</td>
              <td class="num">Optional</td>
              <td class="num">30–45 min</td>
            </tr>
            <tr>
              <td>
                <span class="with-ic"
                  ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                      d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"
                    /></svg
                  >Asset-Backed Lending</span
                >
              </td>
              <td class="num">Up to 3 months</td>
              <td class="num">70% LTV</td>
              <td class="num">4× asset value</td>
              <td class="num">Required</td>
              <td class="num">Fast</td>
            </tr>
            <tr>
              <td>
                <span class="with-ic"
                  ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
                  >CRB Bailout</span
                >
              </td>
              <td class="num">24–48 hours</td>
              <td class="num">Service fee</td>
              <td class="num">Custom</td>
              <td class="num">Not needed</td>
              <td class="num">24–48 hours</td>
            </tr>
            <tr>
              <td>
                <span class="with-ic"
                  ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 16l2 4h14l2-4zM6 16V9h12v7M12 5v4M9 5h6" /></svg
                  >Trade Finance</span
                >
              </td>
              <td class="num">Flexible</td>
              <td class="num">Negotiable</td>
              <td class="num">Custom</td>
              <td class="num">Trade documents</td>
              <td class="num">3–5 days</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="note">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
        ><span
          >Rates shown are indicative and subject to due diligence, facility size, collateral and risk assessment.
          Final pricing is confirmed on approval.</span
        >
      </div>
    </div>
  </section>
  <section class="section section-tint">
    <div class="wrap">
      <div class="section-head reveal">
        <h2>What our clients say</h2>
        <p class="lead">Businesses and individuals across East Africa.</p>
      </div>
      <div class="grid g3 quotes" data-carousel aria-label="Client testimonials">
        <figure class="quote">
          <div class="stars" aria-label="5 out of 5">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" />
            </svg>
          </div>
          <blockquote>
            “Aimzone Ventures delivered emergency funding within 12 hours when we needed it most.”
          </blockquote>
          <figcaption>
            <span class="avatar">JM</span
            ><span><b>James Mwangi</b><br /><span class="muted text-xs">CEO, Nairobi Logistics</span></span>
          </figcaption>
        </figure>
        <figure class="quote">
          <div class="stars" aria-label="5 out of 5">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" />
            </svg>
          </div>
          <blockquote>
            “They cleared my CRB listing in just 48 hours. It completely changed my financial standing.”
          </blockquote>
          <figcaption>
            <span class="avatar">SK</span
            ><span><b>Sarah Kariuki</b><br /><span class="muted text-xs">Entrepreneur</span></span>
          </figcaption>
        </figure>
        <figure class="quote">
          <div class="stars" aria-label="5 out of 5">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" /></svg
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 3l2.8 5.8 6.2.9-4.5 4.4 1 6.2L12 17.4 6.5 20.3l1-6.2L3 9.7l6.2-.9z" />
            </svg>
          </div>
          <blockquote>
            “Asset financing helped us expand our fleet without tying up our working capital.”
          </blockquote>
          <figcaption>
            <span class="avatar">MO</span
            ><span><b>Michael Ochieng</b><br /><span class="muted text-xs">Transport manager</span></span>
          </figcaption>
        </figure>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head center reveal"><h2>Questions, answered</h2></div>
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
        <h2>Ready when you need us.</h2>
        <p>Join 500+ clients who trust Aimzone Ventures with time-critical finance.</p>
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
