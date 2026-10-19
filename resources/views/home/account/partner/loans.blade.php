@extends('home.layouts.account', ['title' => 'Funded loans', 'tab' => 'funded', 'area' => 'partner'])

@section('content')
  <div class="acct-head">
    <h1>Funded loans</h1>
    <p>Loans paid out from investments you fund{{ \App\Models\Loan::where('partner_id', $partner->id)->exists() ? ', and loans you brought in' : '' }}</p>
  </div>

  @if ($total > 0)
    <nav class="a-filter" aria-label="Filter by status">
      <a href="{{ route('account.partner.loans') }}" @if (! $status) aria-current="page" @endif>All · {{ $total }}</a>
      <a href="{{ route('account.partner.loans', ['status' => 'open']) }}" @if ($status === 'open') aria-current="page" @endif>Open</a>
      <a href="{{ route('account.partner.loans', ['status' => 'repaid']) }}" @if ($status === 'repaid') aria-current="page" @endif>Repaid</a>
      <a href="{{ route('account.partner.loans', ['status' => 'closed']) }}" @if ($status === 'closed') aria-current="page" @endif>Written off</a>
    </nav>
  @endif

  <section class="a-card">
    @if ($loans->isEmpty())
      <x-az.empty :title="$total ? 'No loans in this group' : 'No funded loans yet'"
        :message="$total ? 'Try another filter.' : 'When a loan is paid out from an investment you fund, it shows here with its status and repayments. Borrowers are shown by initials only.'">
        @unless ($total)<a class="btn btn-ghost" href="{{ route('account.partner.investments') }}">Your investments</a>@endunless
      </x-az.empty>
    @else
      <div class="a-list">
        @foreach ($loans as $loan)
          @php $who = \App\Services\PartnerAccount::borrowerLabel($loan->user); @endphp
          <a class="a-row" href="{{ route('account.partner.loan', $loan) }}" style="text-decoration:none;color:inherit">
            <span class="p-who" aria-hidden="true">{{ $who['initials'] }}</span>
            <span class="t"><b>Loan #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</b><span>{{ $who['handle'] ?? 'Borrower ' . $who['initials'] }} · {{ $loan->created_at?->format('j M Y') }}</span></span>
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
