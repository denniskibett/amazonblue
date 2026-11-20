{{-- Funding CRM follow-ups: calls due, and prospects who opened or clicked lately (call them now). --}}
@extends('layouts.app')

@section('content')
  @php extract(\App\Support\CrmUi::CLASSES); @endphp
  @include('staff.desk._header', ['title' => 'Follow-ups', 'sub' => $all ? 'Everyone\'s open follow-ups.' : 'Your calls due, and who has just read your email.', 'metrics' => false,
    'actions' => auth()->user()->hasAnyRole(['super-admin', 'admin']) ? '<a class="inline-flex h-11 items-center rounded-lg border border-gray-300 px-4 text-sm dark:border-gray-700" href="' . route('staff.funding.follow-ups', $all ? [] : ['all' => 1]) . '">' . ($all ? 'Only mine' : 'Everyone') . '</a>' : null])
  @include('staff.funding._ui')

  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="{{ $card }} !p-0 overflow-x-auto xl:col-span-2">
      <table class="min-w-full">
        <thead class="border-b border-gray-200 dark:border-gray-800"><tr><th class="{{ $th }}">Due</th><th class="{{ $th }}">Prospect</th><th class="{{ $th }}">What</th><th class="{{ $th }}"></th></tr></thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($items as $a)
            <tr>
              <td class="{{ $td }} whitespace-nowrap {{ $a->due_at && $a->due_at->isPast() ? 'text-error-600 font-medium' : '' }}">{{ $a->due_at?->format('D j M, g:ia') }}</td>
              <td class="{{ $td }}"><a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('staff.funding.show', $a->prospect_id) }}">{{ $a->prospect?->name }}</a><span class="block text-theme-xs text-gray-500">{{ $a->prospect?->reference }} · {{ $a->prospect?->phone ?: $a->prospect?->email }}</span></td>
              <td class="{{ $td }}">{{ $a->title }}@if ($all)<span class="block text-theme-xs text-gray-500">{{ $a->user?->name }}</span>@endif</td>
              <td class="{{ $td }}"><form method="POST" action="{{ route('staff.funding.activities.done', $a) }}">@csrf<button class="text-sm text-brand-500 underline">Done</button></form></td>
            </tr>
          @empty
            <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">No follow-ups open.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="{{ $card }}">
      <h3 class="mb-1 font-semibold text-gray-800 dark:text-white/90">Reading now</h3>
      <p class="mb-3 text-sm text-gray-500">Opened in the last 3 days or clicked in the last 7. Call while it is fresh.</p>
      <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
        @forelse ($hot as $e)
          <li class="py-2"><a class="font-medium text-gray-800 hover:text-brand-500 dark:text-white/90" href="{{ route('staff.funding.show', $e->prospect_id) }}">{{ $e->prospect?->name }}</a>
            <span class="block text-theme-xs text-gray-500">{{ $e->clicked_at ? 'Clicked ' . $e->clicked_at->tz('Africa/Nairobi')->format('D g:ia') : 'Opened ' . $e->opened_at?->tz('Africa/Nairobi')->format('D g:ia') }} · {{ $e->reference }}</span></li>
        @empty
          <li class="py-2 text-gray-500">Nobody yet.</li>
        @endforelse
      </ul>
    </div>
  </div>
@endsection
