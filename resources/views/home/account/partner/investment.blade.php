@extends('home.layouts.account', ['title' => $investment->name, 'tab' => 'investments', 'area' => 'partner'])

@php
  $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('j M Y') : null;
  $committed = (float) $fundings->sum('amount_committed');
  $drawn = (float) $fundings->sum('amount_disbursed');
  $returned = (float) $fundings->sum('amount_returned');
  // notes: all but internal ones ('update' notes are already in updates). Owner decision: which notes partners see
  $notes = collect($investment->notes ?? [])->reject(fn ($n) => in_array($n['category'] ?? '', ['internal', 'staff', 'update'], true))->sortByDesc('date')->values();
  $updates = collect($investment->updates ?? [])->sortByDesc('date')->values();
  $milestones = collect($investment->milestones ?? [])->sortBy('date')->values();
@endphp

@section('content')
  <div>
    <a class="acct-back" href="{{ route('account.partner.investments') }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>Investments</a>
    <div class="acct-head">
      <h1>{{ $investment->name }}</h1>
      <p>{{ $investment->formatted_status }}{{ $investment->facility_type ? ' · ' . $investment->facility_type_label : '' }}{{ $investment->maturity_date ? ' · matures ' . $date($investment->maturity_date) : '' }}</p>
    </div>
  </div>

  <div class="a-stats four">
    <div class="a-stat"><span>You committed</span><b><x-az.money :amount="$committed" /></b></div>
    <div class="a-stat"><span>Lent to borrowers</span><b><x-az.money :amount="$drawn" /></b></div>
    <div class="a-stat"><span>Returned to you</span><b>—</b></div>{{-- S-P3: not recorded yet (Phase 3) --}}
    <div class="a-stat"><span>Status</span><b>{{ $fundings->isNotEmpty() ? ucfirst($fundings->first()->status ?: 'active') : $investment->formatted_status }}</b></div>
  </div>

  <div class="a-grid two">
    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Loans from this investment</h2><span class="muted" style="font-size:13px">{{ $loans->count() }}</span></div>
        @forelse ($loans as $loan)
          @php $who = \App\Services\PartnerAccount::borrowerLabel($loan->user); @endphp
          <a class="a-row" href="{{ route('account.partner.loan', $loan) }}" style="text-decoration:none;color:inherit">
            <span class="p-who" aria-hidden="true">{{ $who['initials'] }}</span>
            <span class="t"><b>Loan #{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</b><span>{{ $who['handle'] ?? 'Borrower ' . $who['initials'] }} · {{ $date($loan->created_at) }}</span></span>
            <span class="amt"><x-az.money :amount="$loan->amount" /><small><x-az.status-badge :status="$loan->status" /></small></span>
          </a>
        @empty
          <x-az.empty title="No loans yet" message="No loan has been paid out from this investment yet." />
        @endforelse
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>Your entries</h2><a href="{{ route('account.statement') }}">Statement</a></div>
        @forelse ($entries as $entry)
          <div class="a-row">
            <span class="t"><b>{{ \App\Services\PartnerPortfolio::label($entry) }}</b><span>{{ $entry->reference }}</span></span>
            <span class="amt"><x-az.money :amount="$entry->amount" sign tone="auto" /><small>{{ $date($entry->transaction_date) }}</small></span>
          </div>
        @empty
          <p class="a-empty">No journal entries are linked to this investment.</p>
        @endforelse
      </section>
    </div>

    <div class="a-grid">
      <section class="a-card">
        <div class="a-sec-h"><h2>Milestones</h2></div>
        @if ($milestones->isEmpty())
          <p class="a-empty">No milestones yet.</p>
        @else
          <x-az.timeline :items="$milestones->map(fn ($m) => ['label' => $m['description'] ?? 'Milestone', 'detail' => ucfirst($m['status'] ?? 'pending'), 'date' => $m['date'] ?? null, 'done' => in_array($m['status'] ?? '', ['done', 'completed', 'achieved'], true)])->all()" style="margin-top:10px" />
        @endif
      </section>

      <section class="a-card">
        <div class="a-sec-h"><h2>Updates and notes</h2></div>
        @if ($updates->isEmpty() && $notes->isEmpty())
          <p class="a-empty">No updates yet. Our team posts news about this investment here.</p>
        @else
          <div class="a-list">
            @foreach ($updates as $u)
              <div class="a-row"><span class="t"><b>{{ $u['update'] ?? '' }}</b><span>Update · {{ $date($u['date'] ?? null) }}</span></span></div>
            @endforeach
            @foreach ($notes as $n)
              <div class="a-row"><span class="t"><b style="font-weight:500">{{ $n['content'] ?? '' }}</b><span>{{ ucfirst($n['category'] ?? 'note') }} · {{ $date($n['date'] ?? null) }}</span></span></div>
            @endforeach
          </div>
        @endif
      </section>
    </div>
  </div>
@endsection
