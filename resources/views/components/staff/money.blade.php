{{-- KES amount with two decimals. Same props as x-az.money: amount, currency (false hides), sign, tone (in|out|muted|auto) --}}
@props(['amount', 'currency' => 'KES', 'sign' => false, 'tone' => null])
@php
  $n = (float) $amount;
  $mark = $n < 0 ? '− ' : ($sign && $n > 0 ? '+ ' : '');
  $tone = $tone === 'auto' ? ($n > 0 ? 'in' : ($n < 0 ? 'out' : null)) : $tone;
@endphp
<span {{ $attributes->class([
  'whitespace-nowrap font-outfit font-medium tabular-nums',
  'text-az-sage-d dark:text-success-400' => $tone === 'in',
  'text-az-rose-d dark:text-error-400' => $tone === 'out',
  'text-gray-500 dark:text-gray-400' => $tone === 'muted',
]) }}>{{ $mark }}@if ($currency)<span class="mr-0.5 text-[0.8em] opacity-70">{{ $currency }}</span> @endif{{ number_format(abs($n), 2) }}</span>
