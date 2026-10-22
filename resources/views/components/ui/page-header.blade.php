{{-- TailAdmin page title with breadcrumb. <x-ui.page-header title="Users" sub="..."> actions </x-ui.page-header> --}}
@props(['title', 'sub' => null, 'crumbs' => []])
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <div class="min-w-0">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
    @if ($sub)<p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $sub }}</p>@endif
  </div>
  <div class="flex flex-wrap items-center gap-3">
    {{ $slot }}
    <nav aria-label="Breadcrumb" class="hidden sm:block">
      <ol class="flex items-center gap-1.5">
        <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400" href="{{ route('dashboard') }}">Home <x-ui.icon name="chevron-right" class="size-3.5" /></a></li>
        @foreach ($crumbs as $label => $url)
          <li><a class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400" href="{{ $url }}">{{ $label }} <x-ui.icon name="chevron-right" class="size-3.5" /></a></li>
        @endforeach
        <li class="text-sm text-gray-800 dark:text-white/90">{{ $title }}</li>
      </ol>
    </nav>
  </div>
</div>
