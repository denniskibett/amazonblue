{{-- Inline message. type: info | ok | warn | err (also success / warning / error, the flashed-alert words).
     <x-az.alert type="warn" title="Repayment due in 3 days">KES 5,400.00 is due on 24 Oct.</x-az.alert> --}}
@props(['type' => 'info', 'title' => null])
@php
  $type = ['success' => 'ok', 'warning' => 'warn', 'error' => 'err', 'danger' => 'err'][$type] ?? $type;
  $path = [
    'ok' => 'M5 12l4 4 10-10',
    'err' => 'M12 8v5M12 16h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    'warn' => 'M12 8v5M12 16h.01M12 3l10 18H2z',
  ][$type] ?? 'M12 11v6M12 7h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z';
@endphp
<div {{ $attributes->class(['a-note', $type]) }} role="{{ in_array($type, ['err', 'warn']) ? 'alert' : 'status' }}">
  <svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path }}" /></svg>
  <div>
    @if ($title)<b>{{ $title }}</b>@endif
    {{ $slot }}
  </div>
</div>
