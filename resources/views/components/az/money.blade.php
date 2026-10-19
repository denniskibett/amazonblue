{{-- KES amount with two decimals. <x-az.money :amount="$loan->amount" />
     sign: show + / − · tone: in | out | muted | auto (in when > 0, out when < 0) · currency: false to hide --}}
@props(['amount', 'currency' => 'KES', 'sign' => false, 'tone' => null])
@php
  $n = (float) $amount;
  $mark = $n < 0 ? '− ' : ($sign && $n > 0 ? '+ ' : '');
  $tone = $tone === 'auto' ? ($n > 0 ? 'in' : ($n < 0 ? 'out' : null)) : $tone;
@endphp
<span {{ $attributes->class(['az-money', 'az-money--' . $tone => $tone]) }}>{{ $mark }}@if ($currency)<small>{{ $currency }}</small> @endif{{ number_format(abs($n), 2) }}</span>
