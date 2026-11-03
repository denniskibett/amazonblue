{{-- Debtor profile for an agency (owner, 3 Nov 2026): print or "Save as PDF". Watermarked to the agency; each open is
     logged. Only what the placement shares: no ID photos, selfies or meters. --}}
@php
  $kes = fn ($v) => 'KES ' . number_format((float) $v, 2);
  $rows = fn (array $pairs) => collect($pairs)->filter(fn ($v) => filled($v));
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
  <title>Debtor profile · loan #{{ $p->loan_id }}</title>
  <style>
    @page { size: A4; margin: 18mm 16mm; }
    body { font: 12px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; color: #151A2E; margin: 0 auto; max-width: 760px; padding: 24px; position: relative; }
    h1 { font-size: 22px; margin: 10px 0 0; } h2 { font-size: 13px; color: #1B2B6B; border-bottom: 1.5px solid #1B2B6B; padding-bottom: 3px; margin: 18px 0 4px; }
    table { width: 100%; border-collapse: collapse; } td { padding: 5px 0; vertical-align: top; } td:first-child { width: 42%; color: #5A6178; }
    .meta { position: absolute; top: 24px; right: 24px; text-align: right; font-size: 11px; color: #5A6178; }
    .wm { position: fixed; top: 45%; left: 0; right: 0; text-align: center; transform: rotate(-28deg); font-size: 48px; font-weight: 700; color: rgba(27,43,107,.06); pointer-events: none; }
    .foot { margin-top: 28px; border-top: 1px solid #DDE3EF; padding-top: 8px; font-size: 10px; color: #5A6178; }
    .print { margin-top: 16px; } @media print { .print { display: none; } }
  </style>
</head>
<body>
  <div class="wm" aria-hidden="true">{{ mb_strtoupper($p->agency->name) }} · CONFIDENTIAL</div>
  <div><b>aimzone</b> ventures</div>
  <div class="meta">For {{ $p->agency->name }}<br>Agreement {{ $p->agency->agreement_ref }}<br>Made {{ now()->format('j M Y, H:i') }} by {{ $by->name }}<br>Valid until {{ $p->ends_on->format('j M Y') }}</div>
  <h1>Debtor profile</h1>
  <div style="color:#5A6178">{{ $profile['identity']['Name'] }} · loan #{{ $p->loan_id }}</div>

  <h2>1. Identity</h2>
  <table>@foreach ($rows($profile['identity']) as $k => $v)<tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach</table>
  <h2>2. How to reach them</h2>
  <table>
    @forelse ($rows($profile['reach']) as $k => $v)<tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>@empty<tr><td colspan="2">Contact details are released once the notice period has run.</td></tr>@endforelse
    <tr><td>Rule</td><td>Next of kin are only asked how to reach the borrower. Never tell them about the debt.</td></tr>
  </table>
  <h2>3. Work</h2>
  <table>@foreach ($rows($profile['work']) as $k => $v)<tr><td>{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach</table>
  <h2>4. What is owed ({{ now()->format('j M Y') }})</h2>
  <table>
    @foreach ($profile['owed'] as $k => $v)<tr><td>{{ $k }}</td><td>{{ $kes($v) }}</td></tr>@endforeach
    <tr><td>Status</td><td>{{ ucfirst($profile['status']) }}{{ $profile['forbearance_until'] ? ' until ' . $profile['forbearance_until']->format('j M Y') : '' }} · {{ $profile['days_late'] }} days late</td></tr>
    <tr><td>Pay to</td><td>Our paybill only, account {{ $p->loan_id }}</td></tr>
  </table>
  <h2>5. Promises and contacts</h2>
  <table>
    @forelse ($profile['contacts']->take(10) as $c)<tr><td>{{ $c->created_at->format('j M, H:i') }} · {{ $c->channel }}</td><td>{{ $c->outcome }}{{ $c->promise_amount ? ' · ' . $kes($c->promise_amount) . ' by ' . $c->promise_date?->format('j M') : '' }}</td></tr>
    @empty<tr><td colspan="2">None yet.</td></tr>@endforelse
  </table>
  <div class="foot">Shared under the Data Protection Act 2019 for collecting this debt only. Do not copy or share it. Delete it when the placement ends or the loan is paid. Each download is logged.</div>
  <p class="print"><button onclick="window.print()">Print or save as PDF</button></p>
</body>
</html>
