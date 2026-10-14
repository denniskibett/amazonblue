@extends('home.layouts.app', [
  'title' => 'Privacy policy — Aimzone Ventures',
  'description' => 'We respect your privacy and handle your information with care. This policy explains what we collect, why, and the choices you have.',
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Privacy policy</h1>
      <p class="muted">Last updated: [date] · Applies in Kenya</p>
      <div class="pills">
        <a class="pill on" href="{{ route('site.page', 'privacy') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          >Privacy policy</a
        ><a class="pill" href="{{ route('site.page', 'terms') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>Terms
          of service</a
        ><a class="pill" href="{{ route('site.page', 'disclaimer') }}"
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
        <a href="#s1"><span>01</span>Who we are</a><a href="#s2"><span>02</span>What we collect</a
        ><a href="#s3"><span>03</span>How we use it</a><a href="#s4"><span>04</span>Legal basis</a
        ><a href="#s5"><span>05</span>Who we share it with</a><a href="#s6"><span>06</span>How long we keep it</a
        ><a href="#s7"><span>07</span>Your rights</a><a href="#s8"><span>08</span>Security</a
        ><a href="#s9"><span>09</span>Changes to this policy</a>
      </nav>
      <article class="prose">
        <p class="lead prose-lead">
          We respect your privacy and handle your information with care. This policy explains what we collect, why,
          and the choices you have.
        </p>
        <div class="note note-flush">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          ><span>Template for review. This page must be approved by your legal counsel before publishing.</span>
        </div>
        <section id="s1">
          <h2><span>01</span>Who we are</h2>
          <p>
            Aimzone Ventures (“we”, “us”) is a digital emergency fund and trade finance private credit firm at The
            Atrium, 1st Floor, Lenana Road, Nairobi. We are the data controller for the personal data described
            here. [Registered name and number.]
          </p>
        </section>
        <section id="s2">
          <h2><span>02</span>What we collect</h2>
          <ul>
            <li>
              Identity details: name, ID or passport number, date of birth, KRA PIN, passport photo and signature.
            </li>
            <li>Contact details: phone number, email and physical address.</li>
            <li>
              Financial information: bank and M-Pesa statements, credit bureau (CRB) reports, income and business
              records.
            </li>
            <li>Facility information: applications, offers, repayments and correspondence.</li>
            <li>Technical data: device, IP address and how you use our website and app.</li>
          </ul>
        </section>
        <section id="s3">
          <h2><span>03</span>How we use it</h2>
          <ul>
            <li>To verify your identity and meet know-your-customer and anti-money-laundering obligations.</li>
            <li>To assess applications, price facilities and manage repayments.</li>
            <li>To provide support and send reminders and service messages.</li>
            <li>To prevent fraud and keep our services secure.</li>
            <li>To improve our products, and for marketing only with your consent.</li>
          </ul>
        </section>
        <section id="s4">
          <h2><span>04</span>Legal basis</h2>
          <p>
            We process personal data in line with the Kenya Data Protection Act, 2019: to perform our contract with
            you, to comply with legal obligations, for our legitimate interests in running a safe lending business,
            and with your consent where required.
          </p>
        </section>
        <section id="s5">
          <h2><span>05</span>Who we share it with</h2>
          <p>
            Only where necessary: credit reference bureaus, partner banks for trade finance, payment providers,
            valuers, our professional advisers, and regulators or law-enforcement bodies where the law requires. We
            never sell your data.
          </p>
        </section>
        <section id="s6">
          <h2><span>06</span>How long we keep it</h2>
          <p>
            For as long as you hold a facility with us and for [7] years afterwards, unless the law requires longer.
          </p>
        </section>
        <section id="s7">
          <h2><span>07</span>Your rights</h2>
          <ul>
            <li>Access the personal data we hold about you.</li>
            <li>Ask us to correct or delete it.</li>
            <li>Object to or restrict certain processing.</li>
            <li>Withdraw consent for marketing at any time.</li>
            <li>Complain to the Office of the Data Protection Commissioner.</li>
          </ul>
        </section>
        <section id="s8">
          <h2><span>08</span>Security</h2>
          <p>
            Your documents are encrypted in transit and at rest, and access is limited to staff who need it.
            [Describe key controls.]
          </p>
        </section>
        <section id="s9">
          <h2><span>09</span>Changes to this policy</h2>
          <p>We will tell you about material changes by email or in the app before they take effect.</p>
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
