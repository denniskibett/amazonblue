{{-- Funding cold email (App\Mail\FundingOutreachEmail). Plain on purpose: it reads like a person wrote it. --}}
<!doctype html>
<html lang="en">
<body style="margin:0;padding:24px;background:#ffffff;font:15px/1.6 -apple-system,Segoe UI,Arial,sans-serif;color:#1d2939">
  <div style="max-width:560px">
    @foreach ($paragraphs as $p)
      <p style="margin:0 0 14px">{!! nl2br(e($p)) !!}</p>
    @endforeach
    <p style="margin:18px 0"><a href="{{ $applyUrl }}" style="color:#2B4FD8;font-weight:600">See how business finance works at Aimzone Ventures</a></p>
    <p style="margin:24px 0 0;font-size:12px;line-height:1.5;color:#667085">
      Aimzone Ventures, Nairobi. Ref {{ $email->reference }}. We found {{ $email->prospect->name }}'s business contact on {{ strtolower(config('funding_crm.sources.' . $email->prospect->source, 'a public listing')) }}.
      Any finance is subject to our assessment and terms; nothing here is an offer.
      How we use business contact details: <a href="{{ url('/privacy') }}" style="color:#667085">privacy notice</a> (Data Protection Act, 2019).
      <a href="{{ $optOutUrl }}" style="color:#667085">Don't email us again</a>.
    </p>
  </div>
  <img src="{{ $pixelUrl }}" width="1" height="1" alt="" style="display:block;border:0">
</body>
</html>
