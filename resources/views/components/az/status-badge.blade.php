{{-- Loan status / payment state pill. <x-az.status-badge :status="$loan->status" /> · <x-az.status-badge status="received" type="payment" />
     Words: config/az.php · colours: .st-* / .pay-* (resources/design/tokens.cjs) --}}
@props(['status', 'type' => 'loan', 'label' => null])
@php
  $key = str_replace([' ', '-'], '_', strtolower(trim((string) $status)));
  $key = $key === 'active' ? 'disbursed' : $key;
  $payment = $type === 'payment';
  $labels = $payment ? config('az.payment_statuses') : config('az.customer_loan_statuses') + config('az.loan_statuses');
  $known = array_key_exists($key, $payment ? config('az.payment_statuses') : config('az.loan_statuses'));
  $class = $known ? ($payment ? 'pay-' : 'st-') . str_replace('_', '-', $key) : 'st-rejected';
@endphp
<span {{ $attributes->class(['badge-s', $class]) }}>{{ $label ?? ($labels[$key] ?? ucfirst(str_replace('_', ' ', $key))) }}</span>
