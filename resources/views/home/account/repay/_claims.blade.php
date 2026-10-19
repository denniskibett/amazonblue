{{-- "I've paid" claims from this session (no storage until Stage 4). Each turns "Received" once staff record a
     repayment with the same M-Pesa code. Needs $loan; $claims defaults to HomeController::claimsFor($loan). --}}
@php $claims = $claims ?? \App\Http\Controllers\HomeController::claimsFor($loan); @endphp
@if (! empty($claims))
  <section class="a-card">
    <div class="a-sec-h"><h2>Payments you told us about</h2></div>
    <div class="a-list">
      @foreach ($claims as $claim)
        @php $at = \Illuminate\Support\Carbon::parse($claim['at']); @endphp
        @if ($claim['receipt'])
          <a class="a-row" href="{{ route('account.receipt', [$loan, $claim['receipt']]) }}">
        @else
          <div class="a-row">
        @endif
          <span class="chip {{ $claim['receipt'] ? 'sage' : 'sand' }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $claim['receipt'] ? 'M5 12l4 4 10-10' : 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z' }}" /></svg></span>
          <span class="t"><b>KES {{ number_format($claim['amount'], 2) }}</b><span>{{ $claim['code'] }} · told us {{ $at->format('j M, H:i') }}</span></span>
          <x-az.status-badge type="payment" :status="$claim['receipt'] ? 'received' : 'pending'" :label="$claim['receipt'] ? 'Received' : 'Checking'" />
        @if ($claim['receipt'])</a>@else</div>@endif
      @endforeach
    </div>
    <p class="muted rp-small" style="margin-top:8px">Our team checks each code against M-Pesa, then records it. This list is kept until you sign out.</p>
  </section>
@endif
