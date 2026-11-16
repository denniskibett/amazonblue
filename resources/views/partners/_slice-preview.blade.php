{{-- The journal rows a slice would post (PartnerSlicesController, mode=preview): nothing is saved. --}}
@if (session()->has('slice_preview'))
  <div class="rounded-xl border border-gray-200 dark:border-gray-800">
    <p class="border-b border-gray-200 bg-gray-50 px-4 py-2.5 text-theme-xs font-semibold text-gray-700 dark:border-gray-800 dark:bg-white/[0.02] dark:text-gray-300">Preview: the journal rows this would post (nothing saved)</p>
    <div class="overflow-x-auto"><table class="w-full text-theme-xs"><tbody class="divide-y divide-gray-100 dark:divide-gray-800">
      @forelse ((array) session('slice_preview') as [$n, $w, $t, $amt, $loanId, $day, $ref])
        <tr><td class="px-4 py-1.5">{{ $n }}</td><td class="pr-3">{{ $w }}</td><td class="pr-3">{{ $t }}</td><td class="pr-3 text-right font-outfit tabular-nums">{{ number_format((float) $amt, 2) }}</td><td class="pr-3">{{ $loanId ? '#' . $loanId : '' }}</td><td class="pr-3">{{ $day }}</td><td class="pr-4 font-mono">{{ $ref }}</td></tr>
      @empty
        <tr><td class="px-4 py-2 text-gray-500">Nothing to post.</td></tr>
      @endforelse
    </tbody></table></div>
  </div>
@endif
