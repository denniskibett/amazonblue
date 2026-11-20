{{-- Funding campaigns: one cold email to every eligible prospect in a segment, sent in batches from sales@sharet.africa. --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); @endphp
  @include('staff.desk._header', ['title' => 'Campaigns', 'sub' => 'Each run sends up to ' . config('funding_crm.batch_limit') . ' emails from ' . config('funding_crm.from.address') . ', only to prospects never emailed, still New, with a business email and not on the do-not-email list.', 'metrics' => false])
  @include('staff.funding._ui')

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="{{ $card }} !p-0 overflow-x-auto xl:col-span-2">
      <table class="min-w-full">
        <thead class="border-b border-gray-200 dark:border-gray-800"><tr><th class="{{ $th }}">Campaign</th><th class="{{ $th }}">Segment</th><th class="{{ $th }}">Sent</th><th class="{{ $th }}">Opened</th><th class="{{ $th }}">Clicked</th><th class="{{ $th }}">Left</th><th class="{{ $th }}"></th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($campaigns as $c)
            <tr>
              <td class="{{ $td }}"><b class="text-gray-800 dark:text-white/90">{{ $c->name }}</b><span class="block text-theme-xs text-gray-500">{{ $c->reference }} · {{ $c->subject }}</span></td>
              <td class="{{ $td }}">{{ $c->segment ? $segments[$c->segment]['label'] : 'Every segment' }}</td>
              <td class="{{ $td }}">{{ $c->sent_count }}</td><td class="{{ $td }}">{{ $c->opened }}</td><td class="{{ $td }}">{{ $c->clicked }}</td><td class="{{ $td }}">{{ $c->left }}</td>
              <td class="{{ $td }}">@if ($c->left && auth()->user()->hasAnyRole(['super-admin', 'admin', 'manager']))<form method="POST" action="{{ route('staff.funding.campaigns.send', $c) }}" onsubmit="return confirm('Send up to {{ min($c->left, config('funding_crm.batch_limit')) }} emails now?')">@csrf<button class="{{ $btn }} !h-9">Send next {{ min($c->left, config('funding_crm.batch_limit')) }}</button></form>@endif</td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No campaigns yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <form method="POST" action="{{ route('staff.funding.campaigns.store') }}" class="{{ $card }} grid gap-3" x-data="{ seg: '' }">
      @csrf
      <h3 class="font-semibold text-gray-800 dark:text-white/90">New campaign</h3>
      <label><span class="{{ $label }}">Name</span><input class="{{ $input }}" name="name" required placeholder="e.g. Distributors, Nairobi, Dec"></label>
      <label><span class="{{ $label }}">Segment</span><select class="{{ $input }}" name="segment" x-model="seg" @change="if (seg && window.funBooks[seg]) { $refs.s.value = window.funBooks[seg].subject; $refs.b.value = window.funBooks[seg].body }">
        <option value="">Every segment</option>@foreach ($segments as $k => $s)<option value="{{ $k }}">{{ $s['label'] }} ({{ $eligible[$k] }} ready)</option>@endforeach</select></label>
      <label><span class="{{ $label }}">Subject</span><input class="{{ $input }}" name="subject" x-ref="s" required maxlength="180"></label>
      <label><span class="{{ $label }}">Message</span><textarea class="{{ $area }}" name="body" x-ref="b" rows="10" required></textarea></label>
      <p class="text-theme-xs text-gray-500">Placeholders: {company} {town} {manager} {manager_phone}. The AIM-E- reference, the tracked link and the opt-out are added for you.</p>
      <button class="{{ $btn }} justify-center">Save draft</button>
    </form>
  </div>
  <script>window.funBooks = @json(collect($segments)->map(fn ($s) => ['subject' => $s['subject'], 'body' => $s['body']]));</script>
@endsection
