{{-- Nothing-here-yet block. <x-az.empty title="No loans yet" message="..."> <a class="btn btn-primary">Borrow</a> </x-az.empty> --}}
@props(['title' => null, 'message' => null])
<div {{ $attributes->class(['az-empty']) }}>
  <span class="az-empty__ic" aria-hidden="true"><svg class="ic" viewBox="0 0 24 24"><path d="M4 7h16v12H4zM4 7l2-3h12l2 3M9 11h6" /></svg></span>
  @if ($title)<b>{{ $title }}</b>@endif
  @if ($message)<p>{{ $message }}</p>@endif
  @if (! $slot->isEmpty())<div class="az-empty__actions">{{ $slot }}</div>@endif
</div>
