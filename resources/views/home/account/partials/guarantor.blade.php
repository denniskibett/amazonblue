{{-- The borrower's loan is waiting for their guarantor to sign (owner, 21 Nov 2026). App\Services\Loans\Guarantees.
     @include('home.account.partials.guarantor', ['loan' => $loan]) — renders nothing once signed or for older loans. --}}
@php $g = app(\App\Services\Loans\Guarantees::class)->summary($loan); @endphp
@if ($g && $g['waiting'])
  <div class="a-note warn guar-card" role="status" aria-labelledby="guar-{{ $loan->id }}">
      <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5M9 14l2 2 4-4" /></svg>
      <div style="flex:1">
        <b id="guar-{{ $loan->id }}">Waiting for your guarantor to sign</b>
        We texted {{ $g['name'] }} ({{ $g['phone'] }}) a link{{ $g['emailed'] ? ' and emailed it too' : '' }}. They add their details, a photo of their ID and sign on their phone; we send your money once they do.
        <div class="hold-actions">
          <form method="POST" action="{{ route('account.loan.guarantor.remind', $loan) }}">@csrf<button class="btn btn-ghost" type="submit">Text the link again</button></form>
        </div>
      </div>
  </div>
@elseif ($g && $g['status'] === 'signed' && in_array($loan->status, \App\Services\Loans\Guarantees::OPEN, true))
  <x-az.alert type="success" title="Your guarantor has signed">{{ $g['name'] }} signed on {{ \Illuminate\Support\Carbon::parse($g['signed_at'])->format('j M Y') }}.</x-az.alert>
@endif
