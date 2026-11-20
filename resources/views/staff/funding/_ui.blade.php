{{-- Shared styles and tabs for the funding CRM pages (20 Nov 2026). --}}
<nav class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-800" aria-label="Funding CRM">
  @foreach ([['staff.funding.index', 'Prospects'], ['staff.funding.follow-ups', 'Follow-ups'], ['staff.funding.campaigns', 'Campaigns'], ['staff.funding.playbook', 'Playbook'], ['staff.funding.suppressions', 'Do not email'], ['staff.finance.index', 'Applications']] as [$r, $l])
    @if (\Illuminate\Support\Facades\Route::has($r) && ($r !== 'staff.finance.index' || auth()->user()->can('review finance applications')))
      <a href="{{ route($r) }}" class="rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($r) ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a>
    @endif
  @endforeach
</nav>
@if ($errors->any())
  <div class="mb-4 rounded-lg border border-error-200 bg-error-50 p-3 text-sm text-error-700" role="alert">{{ $errors->first() }}</div>
@endif
