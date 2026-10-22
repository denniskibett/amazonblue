{{-- Table in a bordered box that scrolls sideways on small screens. Same props as x-az.table:
     headers ('Label' or ['Label', 'num']), caption, or <x-slot:head>. Rows: <tr><td class="...">.
     The first column stays in place while the rest scrolls, and cells are tighter on phones (Stage 4 Phase 0.13).
     Every staff table gets search, sorting, rows per page and pagination (partials/ui/datatable); :list="false" for a
     small fixed table that should stay plain. data-table-page, data-table-search pass through as attributes. --}}
@props(['headers' => [], 'caption' => null, 'list' => true])
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
<div class="max-w-full overflow-x-auto" tabindex="0" @if ($caption) aria-label="{{ $caption }}" @endif>
  <table @if ($list) data-table @endif {{ $attributes->class(['w-full min-w-[640px] text-left text-theme-sm text-gray-700 dark:text-gray-300 [&_td]:whitespace-nowrap [&_td]:border-t [&_td]:border-gray-100 [&_td]:px-3 sm:[&_td]:px-5 [&_td]:py-3 [&_td:first-child]:sticky [&_td:first-child]:left-0 [&_td:first-child]:z-[1] [&_td:first-child]:bg-white dark:[&_td:first-child]:bg-gray-900 [&_th:first-child]:sticky [&_th:first-child]:left-0 [&_th:first-child]:z-[1] [&_th:first-child]:bg-gray-50 dark:[&_th:first-child]:bg-gray-900 dark:[&_td]:border-gray-800 [&_td.num]:text-right [&_td.num]:font-outfit [&_td.num]:tabular-nums']) }}>
    @if ($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
    <thead class="bg-gray-50 text-theme-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
      @isset($head)
        {{ $head }}
      @else
        <tr>
          @foreach ($headers as $h)
            @php [$text, $align] = is_array($h) ? [$h[0], $h[1] ?? null] : [$h, null]; @endphp
            <th scope="col" @class(['whitespace-nowrap px-3 py-3 font-medium sm:px-5', 'text-right' => $align === 'num'])>{{ $text }}</th>
          @endforeach
        </tr>
      @endisset
    </thead>
    <tbody>{{ $slot }}</tbody>
  </table>
</div>
</div>
