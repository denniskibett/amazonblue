@extends('home.layouts.app', [
  'title' => 'Careers — Aimzone Ventures',
  'description' => 'Open roles at Aimzone Ventures in Nairobi.',
  'active' => 'company',
])

@section('content')
  <section class="page-hero">
    <div class="wrap">
      <h1>Build calm capital with us.</h1>
      <p class="lead">
        We're a small Nairobi team helping East African businesses through time-critical moments. If you care about
        doing finance well, and kindly, we'd like to meet you.
      </p>
      <div class="actions"><a class="btn btn-primary" href="#roles">See open roles</a></div>
    </div>
  </section>
  <section class="section">
    <div class="wrap split">
      <figure class="media">
        <img
          src="{{ asset('assets/images/careers-office.jpg') }}"
          alt="Kenyatta International Conference Centre, Nairobi"
          width="1600"
          height="1200"
          loading="lazy"
          decoding="async"
        />
      </figure>
      <div class="stack">
        <h2 class="reveal">Why join us</h2>
        <div class="feature-row">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M4 14v-2a8 8 0 0 1 16 0v2M4 14h3v5H4zM17 14h3v5h-3z" /></svg
          ></span>
          <div>
            <h3>Work that matters</h3>
            <p class="muted">Every facility helps a real business through a hard moment.</p>
          </div>
        </div>
        <div class="feature-row">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></svg
          ></span>
          <div>
            <h3>Learn fast</h3>
            <p class="muted">Credit, trade and technology, side by side in a small team.</p>
          </div>
        </div>
        <div class="feature-row">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg
          ></span>
          <div>
            <h3>Do it right</h3>
            <p class="muted">Discretion and honesty are how we work, not slogans.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="section section-tint" id="roles">
    <div class="wrap">
      <div class="section-head reveal">
        <h2>Open roles</h2>
        <p class="lead">All roles are based at The Atrium, Lenana Road, Nairobi.</p>
      </div>
      <div class="roles">
        <div class="role">
          <div>
            <h3>Credit Analyst</h3>
            <p class="muted">Assess applications, structure facilities and present to credit committee.</p>
            <div class="role-meta">
              <span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
                  /></svg
                >Credit</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" /></svg
                >Nairobi</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2" /></svg
                >Full-time</span
              >
            </div>
          </div>
          <a
            class="btn btn-ghost"
            href="mailto:careers@aimzoneventures.com?subject=Application%3A%20Credit%20Analyst"
            >Apply</a
          >
        </div>
        <div class="role">
          <div>
            <h3>Relationship Officer</h3>
            <p class="muted">Guide borrowers from first call to funds, and look after them afterwards.</p>
            <div class="role-meta">
              <span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
                  /></svg
                >Sales &amp; clients</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" /></svg
                >Nairobi</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2" /></svg
                >Full-time</span
              >
            </div>
          </div>
          <a
            class="btn btn-ghost"
            href="mailto:careers@aimzoneventures.com?subject=Application%3A%20Relationship%20Officer"
            >Apply</a
          >
        </div>
        <div class="role">
          <div>
            <h3>Trade Finance Associate</h3>
            <p class="muted">Structure letters of credit and supplier payments for importers and exporters.</p>
            <div class="role-meta">
              <span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
                  /></svg
                >Trade</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" /></svg
                >Nairobi</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2" /></svg
                >Full-time</span
              >
            </div>
          </div>
          <a
            class="btn btn-ghost"
            href="mailto:careers@aimzoneventures.com?subject=Application%3A%20Trade%20Finance%20Associate"
            >Apply</a
          >
        </div>
        <div class="role">
          <div>
            <h3>Software Engineer</h3>
            <p class="muted">Build the web and mobile app our clients and team use every day.</p>
            <div class="role-meta">
              <span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21c1-4 4-6 7-6s6 2 7 6M16 3a4 4 0 0 1 0 8M18 15c2 1 3.5 3 4 6"
                  /></svg
                >Technology</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21s7-6 7-12a7 7 0 0 0-14 0c0 6 7 12 7 12zM12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" /></svg
                >Nairobi</span
              ><span
                ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2" /></svg
                >Full-time</span
              >
            </div>
          </div>
          <a
            class="btn btn-ghost"
            href="mailto:careers@aimzoneventures.com?subject=Application%3A%20Software%20Engineer"
            >Apply</a
          >
        </div>
      </div>
      <div class="note note-info">
        <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v12H3zM3 7l9 6 9-6" /></svg
        ><span
          >Don't see your role? Send your CV to
          <a href="mailto:careers@aimzoneventures.com?subject=Careers">careers@aimzoneventures.com</a> and tell us
          how you'd help.</span
        >
      </div>
    </div>
  </section>
  <section class="section">
    <div class="wrap">
      <div class="section-head reveal"><h2>How we hire</h2></div>
      <div class="grid g4">
        <div class="step">
          <span class="n">01</span>
          <h3>Apply</h3>
          <p class="muted text-sm">Send your CV and a short note.</p>
        </div>
        <div class="step">
          <span class="n">02</span>
          <h3>Intro call</h3>
          <p class="muted text-sm">30 minutes with the hiring manager.</p>
        </div>
        <div class="step">
          <span class="n">03</span>
          <h3>Practical task</h3>
          <p class="muted text-sm">A short, paid exercise close to the real work.</p>
        </div>
        <div class="step">
          <span class="n">04</span>
          <h3>Meet the team</h3>
          <p class="muted text-sm">Meet the people you'd work with, then an offer.</p>
        </div>
      </div>
    </div>
  </section>
@endsection
