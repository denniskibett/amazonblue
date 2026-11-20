{{-- Do-not-email list: opt-outs, bounces, complaints and manual additions. Checked before every cold email. --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); @endphp
  @include('staff.desk._header', ['title' => 'Do not email', 'sub' => 'Addresses we never email again (Data Protection Act, 2019: the right to object to direct marketing). We keep only the address and why.', 'metrics' => false])
  @include('staff.funding._ui')

  <form method="POST" action="{{ route('staff.funding.suppressions.store') }}" class="mb-6 flex flex-wrap gap-3">
    @csrf
    <input class="{{ $input }} max-w-sm" type="email" name="email" placeholder="name@business.co.ke" required aria-label="Email">
    <select class="{{ $input }} max-w-xs" name="reason" aria-label="Reason">@foreach (\App\Models\Funding\Suppression::REASONS as $k => $l)<option value="{{ $k }}" @selected($k === 'manual')>{{ $l }}</option>@endforeach</select>
    <button class="{{ $btn }}">Add</button>
  </form>
  <div class="{{ $card }} !p-0 overflow-x-auto">
    <table class="min-w-full">
      <thead class="border-b border-gray-200 dark:border-gray-800"><tr><th class="{{ $th }}">Email</th><th class="{{ $th }}">Why</th><th class="{{ $th }}">Source</th><th class="{{ $th }}">Since</th></tr></thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($rows as $r)
          <tr><td class="{{ $td }}">{{ $r->email }}</td><td class="{{ $td }}">{{ \App\Models\Funding\Suppression::REASONS[$r->reason] ?? $r->reason }}</td><td class="{{ $td }}">{{ $r->source }}</td><td class="{{ $td }}">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('j M Y') }}</td></tr>
        @empty
          <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">Nobody yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $rows->links() }}</div>
@endsection
