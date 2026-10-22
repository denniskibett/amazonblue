{{-- Staff table in the Loans Due look (owner, 22 Oct 2026): one white card holding the tabs (slot "tabs"), the title
     with "Showing 1 to N of M entries", Show / search / filters / buttons on the right, the table, and the pager.
     Props: title, headers ('Label' or ['Label', 'num']), caption, list (false = small fixed table, no paging),
     bare (true = no card of its own, for a table already inside a card).
     Rows: <tr><td>. The search, paging and sorting come from partials/ui/datatable. --}}
@props(['headers' => [], 'caption' => null, 'list' => true, 'title' => null, 'bare' => false])
<div @class(['overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]' => ! $bare])>
  @isset($tabs)
    <div class="flex gap-1 overflow-x-auto border-b border-gray-200 px-4 pt-3 dark:border-gray-800 sm:px-6" role="tablist">{{ $tabs }}</div>
  @endisset
  <div @class(['px-4 pb-3 pt-4 sm:px-6' => ! $bare])>
    @if ($title)<div class="mb-4"><h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h3></div>@endif
    <div class="max-w-full overflow-x-auto" tabindex="0" @if ($caption) aria-label="{{ $caption }}" @endif>
      <table @if ($list) data-table @endif {{ $attributes->class(['w-full min-w-[640px] text-left text-theme-sm text-gray-700 dark:text-gray-300 [&_td]:whitespace-nowrap [&_td]:py-3 [&_td]:pr-4 [&_td:last-child]:pr-0 [&_td.num]:text-right [&_td.num]:tabular-nums']) }}>
        @if ($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
        <thead class="border-y border-gray-100 dark:border-gray-800">
          @isset($head)
            {{ $head }}
          @else
            <tr>
              @foreach ($headers as $h)
                @php [$text, $align] = is_array($h) ? [$h[0], $h[1] ?? null] : [$h, null]; @endphp
                <th scope="col" @class(['whitespace-nowrap py-3 pr-4 text-theme-xs font-medium text-gray-500 dark:text-gray-400', 'text-right' => $align === 'num'])>{{ $text }}</th>
              @endforeach
            </tr>
          @endisset
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">{{ $slot }}</tbody>
      </table>
    </div>
  </div>
</div>
