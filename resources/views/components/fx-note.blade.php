{{-- The rate line next to converted figures (App\Support\Money::note). Renders nothing when nothing is converted. --}}
@props(['from' => null, 'to' => null])
@php $fxLine = rescue(fn () => \App\Support\Money::note($from, $to), null, false); @endphp
@if ($fxLine)
  <p {{ $attributes->merge(['class' => 'mb-4 -mt-3 text-xs text-gray-500 dark:text-gray-400']) }}>{{ $fxLine }}</p>
@endif
