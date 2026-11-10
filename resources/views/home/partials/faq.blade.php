{{--
  FAQ block + its FAQPage JSON-LD (pushed to the <head>). $faqs: [[question, answer], ...] in plain text, so the
  page and the structured data always say the same thing. $heading defaults to "Common questions".
--}}
<section class="section" id="faq">
  <div class="wrap">
    <div class="section-head center reveal"><h2>{{ $heading ?? 'Common questions' }}</h2></div>
    <div class="faq">
      @foreach ($faqs as $i => [$question, $answer])
        <details @if ($i === 0) open @endif>
          <summary>
            {{ $question }}<span class="plus"
              ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg
            ></span>
          </summary>
          <p class="answer">{{ $answer }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>
@push('jsonld')
  {!! \App\Support\Seo::script(\App\Support\Seo::faqPage($faqs)) !!}
@endpush
