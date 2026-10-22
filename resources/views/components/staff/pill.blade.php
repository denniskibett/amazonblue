{{-- Small coloured pill for table cells (Loans Due look). tone: green | amber | red | blue | brand | gray | purple
     <x-staff.pill tone="amber">Above limit</x-staff.pill> --}}
@props(['tone' => 'gray', 'dot' => false])
@php
  $c = [
    'green' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500',
    'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    'red' => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-500',
    'blue' => 'bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-500',
    'brand' => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400',
    'purple' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400',
    'gray' => 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-white/80',
  ][$tone] ?? 'bg-gray-100 text-gray-700';
@endphp
<span {{ $attributes->class(["inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-theme-xs font-medium $c"]) }}>@if ($dot)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif{{ $slot }}</span>
