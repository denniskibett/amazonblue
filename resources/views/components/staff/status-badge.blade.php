{{-- Loan status / payment state pill (TailAdmin). Same props as x-az.status-badge.
     <x-staff.status-badge :status="$loan->status" /> · <x-staff.status-badge status="failed" type="payment" /> --}}
@props(['status', 'type' => 'loan', 'label' => null])
@php
  $key = str_replace([' ', '-'], '_', strtolower(trim((string) $status)));
  $key = $key === 'active' ? 'disbursed' : $key;
  $labels = $type === 'payment' ? config('az.payment_statuses') : config('az.loan_statuses');
  // colour classes are safelisted in tailwind.config.js (bg-status-*, bg-pay-*)
  $tone = array_key_exists($key, $labels) ? ($type === 'payment' ? 'pay-' : 'status-') . str_replace('_', '-', $key) : 'status-rejected';
@endphp
<span {{ $attributes->class(["inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-theme-xs font-medium bg-{$tone}-bg text-{$tone}-fg"]) }}><span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $label ?? ($labels[$key] ?? ucfirst(str_replace('_', ' ', $key))) }}</span>
