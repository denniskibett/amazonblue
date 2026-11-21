{{-- The signed guarantee and release, for staff to read or print (owner, 21 Nov 2026). App\Services\Loans\Guarantees. --}}
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>Guarantee — Loan #{{ str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
      body { font: 15px/1.55 'Helvetica Neue', Arial, sans-serif; color: #151a2e; background: #fff; margin: 0; }
      main { max-width: 760px; margin: 0 auto; padding: 32px 20px 48px; }
      header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; border-bottom: 2px solid #1b2b6b; padding-bottom: 14px; }
      header img { height: 34px; }
      h1 { font-size: 21px; margin: 18px 0 4px; color: #1b2b6b; }
      .meta { color: #5a6178; font-size: 13.5px; margin: 0 0 16px; }
      ol { padding-left: 20px; display: grid; gap: 8px; }
      dl { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; margin: 18px 0; }
      dl div { display: flex; justify-content: space-between; gap: 10px; border-bottom: 1px solid #dde3ef; padding: 4px 0; }
      dt { color: #5a6178; } dd { margin: 0; font-weight: 600; text-align: right; }
      .sig { margin-top: 24px; display: flex; gap: 32px; align-items: flex-end; flex-wrap: wrap; }
      .sig img { height: 80px; border-bottom: 1px solid #151a2e; display: block; }
      .proof { margin-top: 28px; font-size: 12px; color: #5a6178; word-break: break-all; }
      button { margin-top: 20px; padding: 8px 14px; border-radius: 8px; border: 1px solid #1b2b6b; background: #1b2b6b; color: #fff; font-weight: 600; cursor: pointer; }
      @media (max-width: 560px) { dl { grid-template-columns: 1fr; } }
      @media print { button { display: none; } main { padding: 0; } }
    </style>
  </head>
  <body>
    <main>
      <header>
        <img src="{{ asset('assets/images/logo/aimzone-horizontal-blue.svg') }}" alt="Aimzone Ventures" />
        <div class="meta" style="text-align:right;margin:0">Loan #{{ str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT) }}<br>Borrower: {{ $loan->user?->name }}</div>
      </header>
      <h1>Guarantee and release</h1>
      <p class="meta">Signed online by the guarantor on {{ $row->signed_at->format('j F Y \a\t H:i') }}</p>
      <ol>@foreach (preg_split("/\n\n/", (string) $row->terms) as $line)<li>{{ $line }}</li>@endforeach</ol>
      <dl>
        <div><dt>Guarantor</dt><dd>{{ $row->name }}</dd></div>
        <div><dt>ID or passport</dt><dd>{{ $row->id_number }}</dd></div>
        <div><dt>Phone</dt><dd>{{ $row->phone }}</dd></div>
        <div><dt>Date of birth</dt><dd>{{ $row->dob?->format('j M Y') }}</dd></div>
        <div><dt>Lives in</dt><dd>{{ $row->residence }}</dd></div>
        <div><dt>Does</dt><dd>{{ $row->occupation }}</dd></div>
        @if ($row->relationship)<div><dt>Relationship</dt><dd>{{ $row->relationship }}</dd></div>@endif
        @if ($row->email)<div><dt>Email</dt><dd>{{ $row->email }}</dd></div>@endif
      </dl>
      <div class="sig">
        <div><img src="{{ route('loans.guarantee.file', [$loan, 'signature']) }}" alt="Guarantor's signature" /><small>{{ $row->name }}, guarantor</small></div>
      </div>
      <p class="proof">Signed from {{ $row->signed_ip ?: 'unknown address' }} · {{ $row->signed_agent }}<br>Document fingerprint (SHA-256): {{ $row->terms_sha256 }}</p>
      <button type="button" onclick="window.print()">Print or save as PDF</button>
    </main>
  </body>
</html>
