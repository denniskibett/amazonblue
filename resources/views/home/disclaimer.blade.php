@extends('home.layouts.app', [
  'title' => 'Disclaimer — Aimzone Ventures',
  'description' => 'Important information about the content on this website and the limits of what it offers.',
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Disclaimer</h1>
      <p class="muted">Last updated: [date] · Applies in Kenya</p>
      <div class="pills">
        <a class="pill" href="{{ route('site.page', 'privacy') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          >Privacy policy</a
        ><a class="pill" href="{{ route('site.page', 'terms') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>Terms
          of service</a
        ><a class="pill on" href="{{ route('site.page', 'disclaimer') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" /></svg
          >Disclaimer</a
        >
      </div>
    </div>
  </section>
  <section class="section pt-md">
    <div class="wrap legal">
      <nav class="toc" aria-label="On this page">
        <p>On this page</p>
        <a href="#s1"><span>01</span>General information only</a
        ><a href="#s2"><span>02</span>Indicative rates and figures</a
        ><a href="#s3"><span>03</span>No guarantee of approval</a
        ><a href="#s4"><span>04</span>Client testimonials</a><a href="#s5"><span>05</span>Third-party links</a
        ><a href="#s6"><span>06</span>Regulatory status</a><a href="#s7"><span>07</span>Photo credits</a>
      </nav>
      <article class="prose">
        <p class="lead prose-lead">
          Important information about the content on this website and the limits of what it offers.
        </p>
        <div class="note note-flush">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          ><span>Template for review. This page must be approved by your legal counsel before publishing.</span>
        </div>
        <section id="s1">
          <h2><span>01</span>General information only</h2>
          <p>
            Content on this website is general information, not financial, legal or tax advice. Please seek
            independent advice before making financial decisions.
          </p>
        </section>
        <section id="s2">
          <h2><span>02</span>Indicative rates and figures</h2>
          <p>
            Rates, processing times, facility sizes and loan-to-value limits are indicative and may change. They
            depend on due diligence, facility size, collateral and risk assessment. Final pricing is confirmed only
            in your offer letter.
          </p>
        </section>
        <section id="s3">
          <h2><span>03</span>No guarantee of approval</h2>
          <p>
            All applications are subject to verification and credit approval. Meeting the stated criteria does not
            guarantee a facility.
          </p>
        </section>
        <section id="s4">
          <h2><span>04</span>Client testimonials</h2>
          <p>Testimonials reflect individual experiences and are not typical of all outcomes.</p>
        </section>
        <section id="s5">
          <h2><span>05</span>Third-party links</h2>
          <p>Our website may link to other sites. We aren't responsible for their content or privacy practices.</p>
        </section>
        <section id="s6">
          <h2><span>06</span>Regulatory status</h2>
          <p>[State Aimzone Ventures’ licensing or regulatory status accurately, as advised by counsel.]</p>
        </section>
        <section id="s7">
          <h2><span>07</span>Photo credits</h2>
          <p>Photographs on this site come from Wikimedia Commons and are used under the licences shown.</p>
          <ul>
            <li>
              Upper Hill skyline, Nairobi: Antony Trivet,
              <a
                href="https://commons.wikimedia.org/wiki/File:Upper_Hill_Building_Nairobi_Kenya.jpg"
                target="_blank"
                rel="noopener"
                >CC BY-SA 4.0</a
              >
            </li>
            <li>
              Shopkeeper: Isaiah Maghanga,
              <a href="https://commons.wikimedia.org/wiki/File:Shop_Keeper.jpg" target="_blank" rel="noopener"
                >CC BY-SA 4.0</a
              >
            </li>
            <li>
              Britam Tower, Nairobi: Daniel Case,
              <a
                href="https://commons.wikimedia.org/wiki/File:Britam_Tower_and_surrounding_buildings,_Nairobi,_KE.jpg"
                target="_blank"
                rel="noopener"
                >CC BY-SA 4.0</a
              >
            </li>
            <li>
              Kenyatta International Conference Centre: Jorge Láscar,
              <a
                href="https://commons.wikimedia.org/wiki/File:Lascar_Kenyatta_International_Conference_Center_(4522598390).jpg"
                target="_blank"
                rel="noopener"
                >CC BY 2.0</a
              >
            </li>
            <li>
              Port of Mombasa (three photos): Ministry of East African Affairs, Commerce &amp; Tourism, Kenya,
              <a
                href="https://commons.wikimedia.org/wiki/File:A_general_view_of_Mombasa_Port_on_Kenya%27s_Indian_Ocean_coast.jpg"
                target="_blank"
                rel="noopener"
                >Public domain</a
              >
            </li>
          </ul>
        </section>
        <div class="card card-inline">
          <div class="feature-row center">
            <span class="chip"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14h3v5H4zM17 14h3v5h-3z" /></svg
            ></span>
            <div>
              <h3 class="h-sm">Questions about this page?</h3>
              <p class="text-xs">hello@aimzoneventures.com · +254 701 607 959</p>
            </div>
          </div>
          <a class="btn btn-ghost" href="{{ route('site.page', 'contact') }}">Contact us</a>
        </div>
      </article>
    </div>
  </section>
@endsection
