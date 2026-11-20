{{-- One finance application for review: the business, the asset or deal, the estimate, documents, history and the decision. --}}
@extends('layouts.app')

@section('content')
  @php
    extract(\App\Support\CrmUi::CLASSES);
    $b = $fa->business ?? []; $d = $fa->details ?? []; $p = $fa->productConfig();
    $m = fn ($v, $c = null) => ($c ?? $fa->currency()) . ' ' . number_format((float) $v);
    $row = fn ($l, $v) => '<div class="flex justify-between gap-3 border-b border-gray-100 py-2 text-sm dark:border-gray-800"><span class="text-gray-500">' . e($l) . '</span><span class="text-right text-gray-800 dark:text-white/90">' . e($v ?? '—') . '</span></div>';
  @endphp
  @include('staff.desk._header', ['title' => $fa->reference . ' · ' . $fa->businessName(), 'sub' => $p['label'] . ' · ' . $fa->statusLabel() . ($fa->submitted_at ? ' · submitted ' . $fa->submitted_at->format('j M Y, g:ia') : ' · not submitted yet'), 'metrics' => false,
    'actions' => '<a class="inline-flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700" href="' . route('staff.finance.index') . '">Back to the queue</a>'])
  @if ($errors->any())<div class="mb-4 rounded-lg border border-error-200 bg-error-50 p-3 text-sm text-error-700" role="alert">{{ $errors->first() }}</div>@endif

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
      <div class="{{ $card }}">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Business</h3>
        {!! $row('Registered name', $b['legal_name'] ?? null) !!}{!! $row('Registration no.', $b['registration_number'] ?? null) !!}{!! $row('KRA PIN', $b['kra_pin'] ?? null) !!}
        {!! $row('Sector', isset($b['sector']) ? (config('funding_crm.segments.' . $b['sector'] . '.label') ?? 'Other') : null) !!}{!! $row('County', $b['county'] ?? null) !!}
        {!! $row('Years trading', $b['years_trading'] ?? null) !!}{!! $row('Directors', $b['directors'] ?? null) !!}{!! $row('Turnover, 12 months', isset($b['annual_turnover']) ? $m($b['annual_turnover'], 'KES') : null) !!}
        {!! $row('Contact', $fa->contact_name . ' · ' . $fa->phone . ' · ' . $fa->email) !!}
      </div>
      <div class="{{ $card }}">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">{{ $p['need_label'] }}</h3>
        @if ($fa->product === 'asset')
          {!! $row('Asset', ($p['categories'][$d['category'] ?? ''] ?? '—') . ' · ' . ($d['description'] ?? '') . ' (' . ($d['condition'] ?? '') . ')') !!}
          {!! $row('Supplier', ($d['supplier'] ?? '—') . (! empty($d['supplier_phone']) ? ' · ' . $d['supplier_phone'] : '')) !!}
          {!! $row('Price', isset($d['price']) ? $m($d['price']) : null) !!}{!! $row('Deposit offered', isset($d['deposit']) ? $m($d['deposit']) . (! empty($d['price']) ? ' (' . round($d['deposit'] / $d['price'] * 100) . '%)' : '') : null) !!}
        @else
          {!! $row('Type', $p['kinds'][$d['kind'] ?? ''] ?? null) !!}{!! $row('Supplier', ($d['supplier'] ?? '—') . ', ' . ($d['supplier_country'] ?? '')) !!}
          {!! $row('Goods', $d['goods'] ?? null) !!}{!! $row('Buyer', $d['buyer'] ?? null) !!}{!! $row('Order value', isset($d['order_value']) ? $m($d['order_value']) : null) !!}
          {!! $row('Buyer pays on', $d['buyer_pays_on'] ?? null) !!}
        @endif
        {!! $row('Asks for', $fa->amount ? $m($fa->amount) . ' · ' . $fa->term_months . ' months' : null) !!}
        @if ($fa->estimate)
          {!! $row('Estimate shown to them', $m($fa->estimate['instalment']) . ($fa->estimate['basis'] === 'monthly' ? ' a month' : ' at the end') . ' at ' . $fa->estimate['rate'] . '%/month (indicative)') !!}
          @if (! empty($fa->estimate['purpose'])){!! $row('Their note', $fa->estimate['purpose']) !!}@endif
        @endif
      </div>
      <div class="{{ $card }}">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Documents</h3>
        @if ($missing)<p class="mb-2 text-sm text-error-600">Missing: {{ collect($missing)->map(fn ($t) => config('finance_journey.documents.' . $t . '.0'))->implode(', ') }}</p>@endif
        <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
          @forelse ($fa->documents as $doc)
            <li class="flex justify-between gap-3 py-2"><span>{{ $doc->label() }}</span><a class="text-brand-500 underline" href="{{ route('staff.finance.document', [$fa, $doc->id]) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($doc->original_name, 40) }}</a></li>
          @empty
            <li class="py-2 text-gray-500">None yet.</li>
          @endforelse
        </ul>
      </div>
      <div class="{{ $card }}">
        <h3 class="mb-2 font-semibold text-gray-800 dark:text-white/90">History</h3>
        <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
          @foreach (array_reverse($fa->history ?? []) as $h)
            <li class="py-2"><b>{{ config('finance_journey.statuses.' . $h['status'], $h['status']) }}</b> · {{ \Illuminate\Support\Carbon::parse($h['at'])->format('j M, g:ia') }}{{ ! empty($h['by']) ? ' · ' . $h['by'] : '' }}@if (! empty($h['note']))<span class="block text-gray-600 dark:text-gray-400">{{ $h['note'] }}</span>@endif</li>
          @endforeach
        </ul>
      </div>
    </div>

    <div class="space-y-6">
      <form method="POST" action="{{ route('staff.finance.decide', $fa) }}" class="{{ $card }} grid gap-3" x-data="{ d: '{{ $fa->status === 'submitted' ? 'in_review' : 'approved' }}' }">
        @csrf
        <h3 class="font-semibold text-gray-800 dark:text-white/90">Decision</h3>
        @if (in_array($fa->status, ['submitted', 'in_review', 'more_info'], true))
          <label><span class="{{ $label }}">What to do</span><select class="{{ $input }}" name="decision" x-model="d">
            @foreach (\App\Services\FinanceJourney\FinanceReview::DECISIONS as $k => $l)@if (! ($k === 'in_review' && $fa->status !== 'submitted') && ! ($fa->status === 'more_info' && $k !== 'declined'))<option value="{{ $k }}">{{ $l }}</option>@endif @endforeach</select></label>
          <label x-show="d === 'more_info'"><span class="{{ $label }}">Send them back to</span><select class="{{ $input }}" name="back_to"><option value="">Just the consent step</option>@foreach (config('finance_journey.steps') as $k => $l)@if ($k !== 'review')<option value="{{ $k }}">{{ $l }}</option>@endif @endforeach</select></label>
          <label x-show="d !== 'in_review'"><span class="{{ $label }}">Message to the applicant</span><textarea class="{{ $area }}" name="note" rows="4" placeholder="Required when you ask for more or decline. They see it."></textarea></label>
          <p class="text-theme-xs text-gray-500" x-show="d === 'approved'">Approval in principle tells them that final terms, security and an agreement still follow. It is not an offer.</p>
          <button class="{{ $btn }} justify-center">Save and tell them</button>
        @else
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ $fa->statusLabel() }}{{ $fa->reviewer ? ' by ' . $fa->reviewer->name : '' }}{{ $fa->reviewed_at ? ' on ' . $fa->reviewed_at->format('j M Y') : '' }}.</p>
          @if ($fa->staff_note)<p class="text-sm text-gray-600 dark:text-gray-400">"{{ $fa->staff_note }}"</p>@endif
        @endif
      </form>

      @if ($fa->status === 'approved' && auth()->user()->can('edit loans'))
        <form method="POST" action="{{ route('staff.finance.loan', $fa) }}" class="{{ $card }} grid gap-3">
          @csrf
          <h3 class="font-semibold text-gray-800 dark:text-white/90">Repayments</h3>
          <p class="text-sm text-gray-500">Once the facility is booked as a loan, link it here: the applicant then repays through Sharet Pay from their application page{{ \App\Services\Sharet\SharetCollections::available() ? '' : ' (Sharet Pay is switched off on this server)' }}.</p>
          <label><span class="{{ $label }}">Loan number</span><input class="{{ $input }}" type="number" name="loan_id" value="{{ $fa->loan_id }}" min="1"></label>
          <button class="{{ $btn }} justify-center">{{ $fa->loan_id ? 'Update' : 'Link loan' }}</button>
        </form>
      @endif

      @if ($fa->prospect)
        <div class="{{ $card }}"><h3 class="mb-1 font-semibold text-gray-800 dark:text-white/90">From the CRM</h3>
          <p class="text-sm">@can('manage funding prospects')<a class="text-brand-500 underline" href="{{ route('staff.funding.show', $fa->prospect) }}">{{ $fa->prospect->reference }} · {{ $fa->prospect->name }}</a>@else{{ $fa->prospect->reference }}@endcan</p></div>
      @endif
    </div>
  </div>
@endsection
