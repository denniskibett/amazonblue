{{-- One section of <x-staff.tabs>, shown while its tab is open. --}}
@props(['name'])
<div id="panel-{{ $name }}" role="tabpanel" aria-labelledby="tab-{{ $name }}" x-show="tab === @js($name)" {{ $attributes }}>
  {{ $slot }}
</div>
