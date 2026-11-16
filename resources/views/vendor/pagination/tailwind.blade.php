{{-- Server-side pages in the same look as the table pager (owner, 16 Nov 2026): "Showing 1–10 of 50" on the left,
     ‹ 1 2 3 4 5 … 10 › square boxes on the right. --}}
@if ($paginator->hasPages())
  @php
    $sq = 'inline-flex size-11 shrink-0 items-center justify-center rounded-xl border text-theme-sm font-medium transition-colors';
    $off = $sq . ' border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]';
    $on = $sq . ' border-brand-500 bg-brand-500 text-white';
    $dim = $sq . ' cursor-not-allowed border-gray-200 bg-white text-gray-300 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-600';
    $left = '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>';
    $right = '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>';
  @endphp
  <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    <p class="text-sm text-gray-700 dark:text-gray-400">
      @if ($paginator->firstItem())
        Showing <span class="font-medium text-gray-800 dark:text-white/90">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
        @if (method_exists($paginator, 'total')) of <span class="font-medium text-gray-800 dark:text-white/90">{{ $paginator->total() }}</span>@endif
      @endif
    </p>
    <nav class="flex items-center gap-2" role="navigation" aria-label="Pages">
      @if ($paginator->onFirstPage())
        <span class="{{ $dim }}" aria-disabled="true" aria-label="Previous page">{!! $left !!}</span>
      @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $off }}" aria-label="Previous page">{!! $left !!}</a>
      @endif
      <div class="hidden items-center gap-2 sm:flex">
        @foreach ($elements as $element)
          @if (is_string($element))
            <span class="{{ $off }} pointer-events-none" aria-hidden="true">…</span>
          @endif
          @if (is_array($element))
            @foreach ($element as $page => $url)
              @if ($page == $paginator->currentPage())
                <span class="{{ $on }}" aria-current="page">{{ $page }}</span>
              @else
                <a href="{{ $url }}" class="{{ $off }}" aria-label="Page {{ $page }}">{{ $page }}</a>
              @endif
            @endforeach
          @endif
        @endforeach
      </div>
      @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $off }}" aria-label="Next page">{!! $right !!}</a>
      @else
        <span class="{{ $dim }}" aria-disabled="true" aria-label="Next page">{!! $right !!}</span>
      @endif
    </nav>
  </div>
@endif
