{{-- What this loan's state means for the borrower (Stage 2 borrower journeys).
     @include('home.account.partials.loan-state', ['loan' => $loan, 'progress' => $progress, 'onHome' => true])
     Copy and figures: App\Support\BorrowerLoanState (read-only). Renders nothing for active-on-time / repaid loans. --}}
@php
  $state = \App\Support\BorrowerLoanState::for($loan, $progress, $onHome ?? false);
  // on the account home the hero above already shows an open loan's status and amount: keep this card short
  $compact = ($onHome ?? false) && $state && ! in_array($state['status'], ['rejected', 'written_off'], true);
@endphp
@if ($state)
  <section class="a-card bj-state bj-{{ $state['tone'] }}" aria-labelledby="bj-state-{{ $loan->id }}">
    @unless ($compact)
    <div class="bj-state__head">
      <x-az.status-badge :status="$state['status']" :label="$state['status'] === 'forbearance' ? 'Payment plan' : null" />
      @if ($onHome ?? false)<span class="muted">Loan #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }} · KES {{ number_format((float) $loan->amount, 2) }}</span>@endif
    </div>
    @endunless
    <h2 id="bj-state-{{ $loan->id }}">{{ $state['title'] }}</h2>
    @unless ($compact && in_array($state['status'], ['pending', 'approved'], true))<p>{{ $state['message'] }}</p>@endunless

    @if (! empty($state['case']))
      <dl class="a-dl bj-case">
        @foreach ($state['case'] as $label => $value)
          <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
      </dl>
    @elseif (in_array($state['status'], ['defaulted', 'recovery']))
      <p class="muted" style="font-size:13.5px">A recovery officer will be assigned to you shortly.</p>
    @endif

    @if (! empty($state['next']))
      <h3 class="bj-state__sub">What happens next</h3>
      <ul class="bj-next">
        @foreach ($state['next'] as $line)<li>{{ $line }}</li>@endforeach
      </ul>
    @endif

    <div class="bj-actions">
      @foreach ($state['actions'] as [$label, $href, $kind])
        <a class="btn {{ $kind === 'primary' ? 'btn-primary' : 'btn-ghost' }}" href="{{ $href }}">{{ $label }}</a>
      @endforeach
    </div>
  </section>
@elseif (! ($onHome ?? false) && in_array($loan->status, \App\Support\BorrowerLoanState::EXTENDABLE, true))
  <x-az.alert type="info" title="Need more time to pay?">
    You can ask for an extension before the due date; it adds interest for the new period. <a href="{{ route('account.extension', $loan) }}">See the cost and ask</a>
  </x-az.alert>
@endif
