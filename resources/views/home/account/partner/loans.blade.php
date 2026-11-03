@extends('home.layouts.account', ['title' => 'Funded loans', 'tab' => 'funded', 'area' => 'partner'])

@section('content')
  @php
    $heads = [
      'funded' => ['Funded loans', 'Loans paid out from investments you fund'],
      'brokered' => ['Clients you introduced', 'Clients you introduced (short-term partner). You earn introducer fees on these; your capital is not lent on them unless they also show under Funded.'],
      'pending' => ['Pending loans', 'Loans you brought in that are waiting for approval or to be sent. Nothing is earned on them until they are sent.'],
    ];
  @endphp
  <div class="acct-head">
    <h1>{{ $heads[$list][0] }}</h1>
    <p>{{ $heads[$list][1] }}</p>
  </div>

  {{-- G6: funded (investment link) and brokered (loans.partner_id) are separate lists; pending = not sent yet (owner, 1 Nov 2026) --}}
  <nav class="a-filter" aria-label="Which loans">
    <a href="{{ route('account.partner.loans') }}" @if ($list === 'funded') aria-current="page" @endif>Funded · {{ $counts['funded'] }}</a>
    <a href="{{ route('account.partner.loans', ['list' => 'brokered']) }}" @if ($list === 'brokered') aria-current="page" @endif>Introduced · {{ $counts['brokered'] }}</a>
    <a href="{{ route('account.partner.loans', ['list' => 'pending']) }}" @if ($list === 'pending') aria-current="page" @endif>Pending · {{ $counts['pending'] }}</a>
  </nav>

  @if ($total > 0 && $list !== 'pending')
    @php $listQuery = $list === 'funded' ? [] : ['list' => $list]; @endphp
    <nav class="a-filter" aria-label="Filter by status">
      <a href="{{ route('account.partner.loans', $listQuery) }}" @if (! $status) aria-current="page" @endif>All · {{ $total }}</a>
      <a href="{{ route('account.partner.loans', $listQuery + ['status' => 'open']) }}" @if ($status === 'open') aria-current="page" @endif>Open</a>
      <a href="{{ route('account.partner.loans', $listQuery + ['status' => 'repaid']) }}" @if ($status === 'repaid') aria-current="page" @endif>Repaid</a>
      <a href="{{ route('account.partner.loans', $listQuery + ['status' => 'closed']) }}" @if ($status === 'closed') aria-current="page" @endif>Written off</a>
    </nav>
  @endif

  <section class="a-card">
    @if ($loans->isEmpty())
      <x-az.empty :title="$total ? 'No loans in this group' : ['funded' => 'No funded loans yet', 'brokered' => 'No introduced clients yet', 'pending' => 'No pending loans'][$list]"
        :message="$total ? 'Try another filter.' : ['funded' => 'When a loan is paid out from an investment you fund, it shows here with its status and repayments. Borrowers are shown by initials only.', 'brokered' => 'Loans for clients you introduce show here once they are sent.', 'pending' => 'Applications you bring in show here while they wait for approval or to be sent.'][$list]">
        @unless ($total)<a class="btn btn-ghost" href="{{ route('account.partner.investments') }}">Your investments</a>@endunless
      </x-az.empty>
    @else
      <div class="a-list">
        @foreach ($loans as $loan)
          @php $who = \App\Services\PartnerAccount::borrowerLabel($loan->user); @endphp
          <a class="a-row" href="{{ route('account.partner.loan', $loan) }}" style="text-decoration:none;color:inherit">
            <span class="p-who" aria-hidden="true">{{ $who['initials'] }}</span>
            <span class="t"><b>Loan #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</b><span>{{ $who['handle'] ?? 'Borrower ' . $who['initials'] }} · {{ $list === 'pending' ? 'applied ' : '' }}{{ $loan->created_at?->format('j M Y') }}</span></span>
            <span class="amt"><x-az.money :amount="$loan->amount" /><small><x-az.status-badge :status="$loan->status" /></small></span>
          </a>
        @endforeach
      </div>
      @if ($loans->hasPages())
        <div class="a-pager">
          @if ($loans->previousPageUrl())<a class="btn btn-ghost" href="{{ $loans->previousPageUrl() }}">Newer</a>@else<span></span>@endif
          <span class="muted" style="align-self:center;font-size:13px">Page {{ $loans->currentPage() }} of {{ $loans->lastPage() }}</span>
          @if ($loans->nextPageUrl())<a class="btn btn-ghost" href="{{ $loans->nextPageUrl() }}">Older</a>@else<span></span>@endif
        </div>
      @endif
    @endif
  </section>
@endsection
