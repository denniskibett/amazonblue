@extends('home.layouts.app', [
  'title' => 'Enquiries — Aimzone Ventures',
  'description' => 'Funding, investor relations and partnership enquiries for Aimzone Ventures, Nairobi.',
  'variant' => 'on-dark',
  'active' => 'contact',
])

@section('content')
  <section class="page-hero page-hero-dark">
    <div class="wrap">
      <span class="kicker">Enquiries</span>
      <h1>Speak with the right desk.</h1>
      <p class="lead">
        Whether you need capital, want to deploy it or want to partner with us, your enquiry goes straight to the
        team that handles it. Every conversation is confidential.
      </p>
      <ul class="assurances">
        <li>
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          >Confidential by default
        </li>
        <li>
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2" /></svg
          >Acknowledged within one business hour
        </li>
        <li>
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>NDA
          available on request
        </li>
      </ul>
    </div>
  </section>
  <section class="section pt-md">
    <div class="wrap">
      <div class="grid g3 desks">
        <a class="desk" href="#message" data-topic="bridge"
          ><span class="desk-top"
            ><span class="chip"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M13 3L5 14h6l-1 7 8-11h-6z" /></svg></span
            ><span class="desk-label">01 · Client desk</span></span
          >
          <h3>Funding and credit</h3>
          <p>
            Emergency bridge finance, asset-backed lending, CRB resolution and trade finance for businesses and
            individuals.
          </p>
          <dl class="desk-meta">
            <div>
              <dt>Response</dt>
              <dd>Within one business hour</dd>
            </div>
            <div>
              <dt>Email</dt>
              <dd>clients@aimzoneventures.com</dd>
            </div>
            <div>
              <dt>Line</dt>
              <dd>24/7, including weekends</dd>
            </div>
          </dl>
          <span class="more"
            >Start an enquiry
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="desk" href="#message" data-topic="investment"
          ><span class="desk-top"
            ><span class="chip apricot"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M12 8c4 0 7-1 7-2.5S16 3 12 3 5 4 5 5.5 8 8 12 8zM5 5.5v6C5 13 8 14 12 14s7-1 7-2.5v-6M5 11.5v6C5 19 8 20 12 20s7-1 7-2.5v-6"
                /></svg></span
            ><span class="desk-label">02 · Investor relations</span></span
          >
          <h3>Investing with Aimzone</h3>
          <p>Private credit notes, trade finance participation and co-investment for eligible investors.</p>
          <dl class="desk-meta">
            <div>
              <dt>Response</dt>
              <dd>Introductory call within two business days</dd>
            </div>
            <div>
              <dt>Email</dt>
              <dd>investors@aimzoneventures.com</dd>
            </div>
            <div>
              <dt>Who</dt>
              <dd>Family offices, funds, qualified individuals</dd>
            </div>
          </dl>
          <span class="more"
            >Start an enquiry
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
        <a class="desk" href="#message" data-topic="partnership"
          ><span class="desk-top"
            ><span class="chip sage"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
                /></svg></span
            ><span class="desk-label">03 · Partnerships</span></span
          >
          <h3>Banks, DFIs and corporates</h3>
          <p>Co-lending, referral and distribution partnerships with banks, funds, brokers and corporates.</p>
          <dl class="desk-meta">
            <div>
              <dt>Response</dt>
              <dd>Within two business days</dd>
            </div>
            <div>
              <dt>Email</dt>
              <dd>partnerships@aimzoneventures.com</dd>
            </div>
            <div>
              <dt>Who</dt>
              <dd>Institutions and intermediaries</dd>
            </div>
          </dl>
          <span class="more"
            >Start an enquiry
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
      </div>
      <div class="enquiry-grid">
        <form
          class="form-card"
          id="message"
          data-validate
          data-success="Thank you. Your enquiry has been received and the right desk will be in touch."
          novalidate
        >
          <input type="hidden" id="c-route" name="route_to" value="clients@aimzoneventures.com" />
          <div class="form-head">
            <span class="kicker">Confidential enquiry</span>
            <h2 class="h-form" data-form-title>Funding enquiry</h2>
          </div>
          <div class="field">
            <label for="c-svc">Enquiry type</label
            ><select class="input" id="c-svc" name="topic">
              <optgroup label="Funding">
                <option value="bridge" data-route="clients@aimzoneventures.com">Emergency Bridge Finance</option>
                <option value="asset" data-route="clients@aimzoneventures.com">Asset-Backed Lending</option>
                <option value="crb" data-route="clients@aimzoneventures.com">CRB Resolution</option>
                <option value="trade" data-route="clients@aimzoneventures.com">Trade Finance</option>
              </optgroup>
              <optgroup label="Investors and partners">
                <option value="investment" data-route="investors@aimzoneventures.com">
                  Investment opportunities
                </option>
                <option value="partnership" data-route="partnerships@aimzoneventures.com">Partnership</option>
              </optgroup>
              <optgroup label="Other">
                <option value="other" data-route="hello@aimzoneventures.com">Media or general enquiry</option>
              </optgroup>
            </select>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="c-name">Full name</label
              ><input class="input" id="c-name" name="name" autocomplete="name" required /><span class="error"
                >Enter your name.</span
              >
            </div>
            <div class="field">
              <label for="c-org">Organisation <span class="muted">(optional)</span></label
              ><input class="input" id="c-org" name="organisation" autocomplete="organization" />
            </div>
          </div>
          <div class="form-row">
            <div class="field">
              <label for="c-email">Email</label
              ><input class="input" id="c-email" name="email" type="email" autocomplete="email" required /><span
                class="error"
                >Enter a valid email address.</span
              >
            </div>
            <div class="field">
              <label for="c-phone">Phone</label
              ><input
                class="input"
                id="c-phone"
                name="phone"
                type="tel"
                autocomplete="tel"
                placeholder="+254 7XX XXX XXX"
                required
              /><span class="error">Enter a phone number we can call.</span>
            </div>
          </div>
          <div class="form-row" data-show-for="investment" hidden>
            <div class="field">
              <label for="c-investor">Investing as</label
              ><select class="input" id="c-investor" name="investor_type">
                <option>Qualified individual</option>
                <option>Family office</option>
                <option>Fund or asset manager</option>
                <option>Bank or DFI</option>
                <option>Corporate</option>
              </select>
            </div>
            <div class="field">
              <label for="c-ticket">Indicative allocation</label
              ><select class="input" id="c-ticket" name="allocation">
                <option>Prefer to discuss</option>
                <option>Under USD 100,000</option>
                <option>USD 100,000 – 500,000</option>
                <option>USD 500,000 – 2 million</option>
                <option>Above USD 2 million</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label for="c-msg">Your enquiry</label
            ><textarea class="input" id="c-msg" name="message" rows="5" required></textarea
            ><span class="error">Tell us a little about what you need.</span>
          </div>
          <div class="field">
            <label class="check"
              ><input type="checkbox" id="c-consent" name="consent" required /><span
                >I agree that Aimzone Ventures may contact me about this enquiry and process my details under its
                <a href="{{ route('site.page', 'privacy') }}">privacy policy</a>.</span
              ></label
            ><span class="error">Please confirm so we can contact you.</span>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Submit enquiry</button>
          <p class="secure">
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
            >Encrypted in transit and seen only by the desk handling your enquiry.
          </p>
        </form>
        <aside class="office-panel" aria-label="Office and direct lines">
          <div>
            <span class="kicker">Nairobi office</span>
            <h3>The Atrium, 1st Floor</h3>
            <p>Lenana Road, Nairobi, Kenya<br />Meetings by appointment</p>
          </div>
          <dl class="office-lines">
            <div>
              <dt>Client line</dt>
              <dd><a href="tel:+254701607959">+254 701 607 959</a><small>24/7</small></dd>
            </div>
            <div>
              <dt>Clients</dt>
              <dd><a href="mailto:clients@aimzoneventures.com">clients@aimzoneventures.com</a></dd>
            </div>
            <div>
              <dt>Investors</dt>
              <dd><a href="mailto:investors@aimzoneventures.com">investors@aimzoneventures.com</a></dd>
            </div>
            <div>
              <dt>Partnerships</dt>
              <dd><a href="mailto:partnerships@aimzoneventures.com">partnerships@aimzoneventures.com</a></dd>
            </div>
            <div>
              <dt>General &amp; media</dt>
              <dd><a href="mailto:hello@aimzoneventures.com">hello@aimzoneventures.com</a></dd>
            </div>
            <div>
              <dt>WhatsApp</dt>
              <dd><a href="https://wa.me/254701607959" target="_blank" rel="noopener">Message an officer</a></dd>
            </div>
          </dl>
          <div>
            <h4>What happens next</h4>
            <ol class="next-steps">
              <li><b>Acknowledgement</b><span>We confirm receipt within one business hour.</span></li>
              <li><b>A named contact</b><span>An officer from the right desk calls or emails you.</span></li>
              <li><b>NDA where needed</b><span>We sign one before any sensitive documents change hands.</span></li>
            </ol>
          </div>
        </aside>
      </div>
    </div>
  </section>
@endsection
