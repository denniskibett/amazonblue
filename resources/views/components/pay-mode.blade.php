{{-- How a payment was made. Sharet Pay shows with Sharet's icon (10 Nov 2026); any other mode shows the slot (the
     page's own wording), or the PaymentModes label. <x-pay-mode :mode="$r->mode">{{ $label }}</x-pay-mode> --}}
@props(['mode' => null])
@if (\App\Support\PaymentModes::normalise($mode) === 'sharet')<span {{ $attributes->merge(['style' => 'display:inline-flex;align-items:center;gap:.35em;white-space:nowrap']) }}><x-sharet-icon />Sharet Pay</span>@elseif ($slot->isNotEmpty()){{ $slot }}@else{{ \App\Support\PaymentModes::label($mode) }}@endif
