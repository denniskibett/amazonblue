{{-- Table that scrolls inside its box on phones.
     <x-az.table :headers="['Date', 'Reference', ['Amount', 'num']]"> <tr>...</tr> </x-az.table>
     A header is 'Label' or ['Label', 'num'] (right aligned, number font). Or pass <x-slot:head><tr>..</tr></x-slot:head>.
     caption: optional, read by screen readers. --}}
@props(['headers' => [], 'caption' => null])
<div class="az-table-wrap" tabindex="0" @if ($caption) aria-label="{{ $caption }}" @endif>
  <table {{ $attributes->class(['az-table']) }}>
    @if ($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
    <thead>
      @isset($head)
        {{ $head }}
      @else
        <tr>
          @foreach ($headers as $h)
            @php [$text, $align] = is_array($h) ? [$h[0], $h[1] ?? null] : [$h, null]; @endphp
            <th scope="col" @class(['num' => $align === 'num'])>{{ $text }}</th>
          @endforeach
        </tr>
      @endisset
    </thead>
    <tbody>{{ $slot }}</tbody>
  </table>
</div>
