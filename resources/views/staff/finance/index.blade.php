{{-- Finance applications review queue (20 Nov 2026): business asset and trade finance, oldest submitted first. --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); $S = config('finance_journey.statuses'); @endphp
  @include('staff.desk._header', ['title' => 'Finance applications', 'sub' => 'Business asset and trade finance applications. Each one is a request: approving here is in principle only, subject to credit checks, security and a signed agreement.', 'metrics' => false])

  <div class="mb-6 flex flex-wrap gap-2">
    @foreach (['waiting' => 'Waiting for us (' . (($counts['submitted'] ?? 0) + ($counts['in_review'] ?? 0)) . ')'] + collect($S)->map(fn ($l, $k) => $l . ' (' . ($counts[$k] ?? 0) . ')')->all() + ['all' => 'All'] as $k => $l)
      <a href="{{ route('staff.finance.index', array_filter(['status' => $k, 'product' => request('product')])) }}" class="rounded-lg border px-3 py-2 text-sm {{ $status === $k ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-200 text-gray-700 dark:border-gray-800 dark:text-gray-300' }}">{{ $l }}</a>
    @endforeach
  </div>
  <form method="GET" class="mb-4 flex flex-wrap gap-3">
    <input type="hidden" name="status" value="{{ $status }}">
    <input class="{{ $input }} max-w-xs" type="search" name="q" value="{{ request('q') }}" placeholder="Reference, business or email" aria-label="Search">
    <select class="{{ $input }} max-w-xs" name="product" aria-label="Product" onchange="this.form.submit()"><option value="">Asset and trade</option>@foreach (config('finance_journey.products') as $k => $p)<option value="{{ $k }}" @selected(request('product') === $k)>{{ $p['label'] }}</option>@endforeach</select>
    <button class="{{ $ghost }}">Search</button>
  </form>

  <div class="{{ $card }} !p-0 overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-b border-gray-200 dark:border-gray-800"><tr><th class="{{ $th }}">Application</th><th class="{{ $th }}">Business</th><th class="{{ $th }}">Asks for</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Submitted</th><th class="{{ $th }}">Files</th></tr></thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($apps as $a)
          <tr>
            <td class="{{ $td }}"><a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('staff.finance.show', $a) }}">{{ $a->reference }}</a><span class="block text-theme-xs text-gray-500">{{ $a->productConfig('label') }}</span></td>
            <td class="{{ $td }}">{{ $a->businessName() }}<span class="block text-theme-xs text-gray-500">{{ $a->contact_name }} · {{ $a->phone }}</span></td>
            <td class="{{ $td }} whitespace-nowrap">{{ $a->amount ? $a->currency() . ' ' . number_format($a->amount) : '—' }}{{ $a->term_months ? ' · ' . $a->term_months . ' mo' : '' }}</td>
            <td class="{{ $td }}">{{ $a->statusLabel() }}@if ($a->reviewer)<span class="block text-theme-xs text-gray-500">{{ $a->reviewer->name }}</span>@endif</td>
            <td class="{{ $td }} whitespace-nowrap">{{ $a->submitted_at?->format('j M, g:ia') ?? 'Not yet' }}</td>
            <td class="{{ $td }}">{{ $a->documents_count }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">Nothing here. Businesses apply at <a class="underline" href="{{ route('finance.choose') }}" target="_blank" rel="noopener">{{ route('finance.choose') }}</a>.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $apps->links() }}</div>
@endsection
