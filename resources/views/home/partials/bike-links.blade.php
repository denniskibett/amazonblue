{{-- The bike loan pages link to each other, then the closing call to action. $current: the page's own slug. --}}
@php
  $bikePages = [
    'boda-boda-loans' => ['Boda boda loans', 'Logbook loans and emergency cash for riders and owners.'],
    'electric-bike-loans' => ['Electric bike loans', 'Loans for e-bike and e-motorcycle riders and owners.'],
    'motorbike-loans-kenya' => ['Motorbike loans in Kenya', 'Borrow against a motorbike, or ask about buying one.'],
    'bike-loan-requirements' => ['Requirements and how to apply', 'What to have ready, step by step.'],
  ];
  unset($bikePages[$current ?? '']);
@endphp
<section class="section section-tint">
  <div class="wrap">
    <div class="section-head reveal"><h2>More on bike loans</h2></div>
    <div class="grid g3" aria-label="More on bike loans">
      @foreach ($bikePages as $slug => [$label, $blurb])
        <a class="card" href="{{ route('site.page', $slug) }}"
          ><span class="chip sand">@include('home.partials.bike-icon')</span>
          <h3>{{ $label }}</h3>
          <p class="muted text-sm">{{ $blurb }}</p>
          <span class="more"
            >View
            <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></span
        ></a>
      @endforeach
    </div>
  </div>
</section>
<section class="section pt-0">
  <div class="wrap">
    <div class="cta-band">
      <h2>Ready to ride on?</h2>
      <p>Apply online in minutes, or tell us about your bike and a relationship officer will call you.</p>
      <div class="actions actions-center">
        <a class="btn btn-light" href="{{ route('register') }}" data-sa-click="bike_apply_footer">Get started</a
        ><a class="btn btn-outline-light" href="{{ route('site.page', 'contact') }}?topic=bike#message" data-sa-click="bike_enquiry_footer">Ask about bike finance</a>
      </div>
    </div>
  </div>
</section>
