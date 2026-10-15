@extends('home.layouts.app', [
  'title' => 'Terms of service — Aimzone Ventures',
  'description' => 'These terms explain how our services work and what we each agree to.',
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Terms of service</h1>
      <p class="muted">Last updated: 15 October 2026 · Applies in Kenya</p>
      <div class="pills">
        <a class="pill" href="{{ route('site.page', 'privacy') }}"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          >Privacy policy</a
        ><a class="pill on" href="{{ route('site.page', 'terms') }}"
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
        <a href="#s1"><span>01</span>About these terms</a><a href="#s2"><span>02</span>Eligibility</a
        ><a href="#s3"><span>03</span>Your account</a><a href="#s4"><span>04</span>Applications and offers</a
        ><a href="#s5"><span>05</span>Repayments</a><a href="#s6"><span>06</span>Fees</a
        ><a href="#s7"><span>07</span>Acceptable use</a><a href="#s8"><span>08</span>Liability</a
        ><a href="#s9"><span>09</span>Closing your account</a><a href="#s10"><span>10</span>Governing law</a>
      </nav>
      <article class="prose">
        <p class="lead prose-lead">These terms explain how our services work and what we each agree to.</p>
        <div class="note note-flush">
          <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          ><span>Template for review. This page must be approved by your legal counsel before publishing.</span>
        </div>
        <section id="s1">
          <h2><span>01</span>About these terms</h2>
          <p>
            These terms govern your use of the Aimzone Ventures website, app and services. Each facility is also
            governed by its own facility agreement, which takes priority if the two differ.
          </p>
        </section>
        <section id="s2">
          <h2><span>02</span>Eligibility</h2>
          <ul>
            <li>You are at least 18 and legally able to enter a contract.</li>
            <li>You are a Kenyan resident or a business registered in Kenya [or other eligible jurisdictions].</li>
            <li>The information you give us is true, complete and kept up to date.</li>
          </ul>
        </section>
        <section id="s3">
          <h2><span>03</span>Your account</h2>
          <p>
            Keep your password and one-time codes private. Tell us immediately at hello@aimzoneventures.com if you
            suspect unauthorised access.
          </p>
        </section>
        <section id="s4">
          <h2><span>04</span>Applications and offers</h2>
          <p>
            Applying does not guarantee funding. All facilities are subject to verification, credit assessment and,
            where relevant, valuation. Rates on our website are indicative; your offer letter sets the final terms.
          </p>
        </section>
        <section id="s5">
          <h2><span>05</span>Repayments</h2>
          <p>
            You agree to repay on the dates in your facility agreement using the payment channels we provide. Late
            payments may attract fees as set out in your agreement. If you're struggling, contact us early so we can
            find a way forward together.
          </p>
        </section>
        <section id="s6">
          <h2><span>06</span>Fees</h2>
          <p>
            All fees are shown in your offer before you accept. [Describe facility, valuation, late payment and any
            other fees.]
          </p>
        </section>
        <section id="s7">
          <h2><span>07</span>Acceptable use</h2>
          <ul>
            <li>Don't provide false information or documents.</li>
            <li>Don't use our services for any unlawful purpose, including money laundering.</li>
            <li>Don't try to disrupt or access our systems without authorisation.</li>
          </ul>
        </section>
        <section id="s8">
          <h2><span>08</span>Liability</h2>
          <p>
            We provide our services with reasonable care and skill. To the extent the law allows, we are not liable
            for indirect or consequential losses.
          </p>
        </section>
        <section id="s9">
          <h2><span>09</span>Closing your account</h2>
          <p>
            You can close your account once all facilities are repaid. We may suspend accounts that breach these
            terms, with notice where the law requires.
          </p>
        </section>
        <section id="s10">
          <h2><span>10</span>Governing law</h2>
          <p>
            These terms are governed by the laws of Kenya. Disputes will be resolved by [mediation, arbitration in
            Nairobi, or the courts of Kenya].
          </p>
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
