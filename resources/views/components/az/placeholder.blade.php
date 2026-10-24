{{-- Marks a feature that is designed but not built yet.
     <x-az.placeholder dependency="M-Pesa STK push">Pay from your phone without typing the paybill.</x-az.placeholder>
     stage: defaults to 4. --}}
@props(['dependency', 'title' => null, 'stage' => 4])
{{-- customers see "Coming soon"; what it waits for stays in the page source for us (owner, 24 Oct 2026) --}}
<div {{ $attributes->class(['az-placeholder']) }} role="note" data-depends-on="{{ $dependency }}">
  <span class="az-placeholder__tag">Coming soon</span>
  @if ($title)<b>{{ $title }}</b>@endif
  @if (! $slot->isEmpty())<p>{{ $slot }}</p>@endif
</div>
