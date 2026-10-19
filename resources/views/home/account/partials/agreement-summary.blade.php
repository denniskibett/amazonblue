{{-- The signed terms for one loan: when they were accepted, template version and the file's fingerprint
     (loans.agreement_* columns, frozen when the borrower consented). @include('home.account.partials.agreement-summary', ['loan' => $loan]) --}}
@if ($loan->consent || $loan->agreement_sha256)
  <section class="a-card bj-terms" aria-labelledby="bj-terms-{{ $loan->id }}">
    <div class="a-sec-h"><h2 id="bj-terms-{{ $loan->id }}">Your loan terms</h2>
      @if ($loan->agreement_sha256)<span class="badge-s st-repaid">Signed</span>@else<span class="badge-s st-pending">Accepted</span>@endif
    </div>
    <dl class="a-dl">
      <div><dt>Accepted on</dt><dd>{{ ($signed = $loan->agreement_signed_at ?? $loan->consent_date) ? \Illuminate\Support\Carbon::parse($signed)->format('j M Y, H:i') : '—' }}</dd></div>
      <div><dt>Agreement version</dt><dd>{{ $loan->agreement_version ?: 'Frozen when you first open it' }}</dd></div>
      @if ($loan->agreement_sha256)
        <div><dt>Fingerprint</dt><dd><code title="SHA-256 {{ $loan->agreement_sha256 }}">{{ substr($loan->agreement_sha256, 0, 12) }}…{{ substr($loan->agreement_sha256, -6) }}</code></dd></div>
      @endif
    </dl>
    <p class="muted" style="font-size:13px;margin-top:8px">The agreement is fixed when you accept it; the copy you open is the one you signed.</p>
    <a class="btn btn-ghost btn-block" href="{{ route('account.agreement', $loan) }}" style="margin-top:12px">View signed agreement</a>
  </section>
@endif
