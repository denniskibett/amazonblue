{{-- AML screening panel (owner, 23 Oct 2026) for a business review or a partner's KYC review.
     @include('aml._panel', ['type' => 'business'|'user', 'subject' => $model]) --}}
@php
  $screening = app(\App\Services\Aml\Screening::class);
  $aml = $screening->current($subject);
  $hits = $aml['hits'] ?? [];
  $checks = $aml['checks'] ?? [];
@endphp
<section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6" id="aml">
  <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div>
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">AML screening</h2>
      <p class="text-theme-sm text-gray-500">
        @if (! empty($aml['screened_at']))
          Screened {{ \Illuminate\Support\Carbon::parse($aml['screened_at'])->format('j M Y, H:i') }} by {{ $aml['screened_by_name'] ?? '#' . ($aml['screened_by'] ?? '') }}
          against {{ $aml['lists'] ? collect($aml['lists'])->map(fn ($n, $l) => $l . ' (' . number_format($n) . ')')->implode(', ') : 'no uploaded lists' }}.
        @else
          Not screened yet. {{ \App\Services\Aml\Screening::enabled() ? 'Required before approval.' : 'Optional while screening is switched off.' }}
        @endif
      </p>
    </div>
    <div class="flex items-center gap-2">
      @if (! empty($aml['clear']))<x-staff.pill tone="green" dot>Clear</x-staff.pill>
      @elseif (! empty($aml['screened_at']))<x-staff.pill tone="amber" dot>Not finished</x-staff.pill>@endif
      @can('edit users')
        <form method="POST" action="{{ route('aml.screen', [$type, $subject->id]) }}">@csrf
          <button class="rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">{{ empty($aml['screened_at']) ? 'Run screening' : 'Run again' }}</button>
        </form>
      @endcan
    </div>
  </div>

  @if ($hits)
    <div class="mb-4 space-y-3">
      @foreach ($hits as $label => $matches)
        <div class="rounded-xl border border-orange-500/30 bg-orange-50 p-3 dark:bg-orange-500/10">
          <p class="text-theme-sm font-semibold text-orange-800 dark:text-orange-300">{{ preg_replace('/ #\d+$/', '', $label) }}</p>
          <ul class="mt-1 space-y-1 text-theme-xs text-orange-900 dark:text-orange-200">
            @foreach ($matches as $m)
              <li>{{ $m['name'] }} · {{ $m['list'] }}{{ $m['reference'] ? ' ' . $m['reference'] : '' }}{{ $m['dob'] ? ' · born ' . $m['dob'] : '' }}{{ $m['country'] ? ' · ' . $m['country'] : '' }} · {{ round($m['score'] * 100) }}% of the words</li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
  @endif

  @if (! empty($aml['screened_at']))
    @can('edit users')
      <form method="POST" action="{{ route('aml.decide', [$type, $subject->id]) }}" class="space-y-3">
        @csrf
        @foreach (\App\Services\Aml\Screening::CHECKS as $key => $label)
          <label class="flex items-start gap-3 text-theme-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="aml_checks[]" value="{{ $key }}" class="mt-0.5 size-4 rounded border-gray-300 text-brand-500" @checked($checks[$key] ?? false)>
            <span>{{ $label }}</span>
          </label>
        @endforeach
        <x-staff.field type="textarea" name="aml_note" :label="$hits ? 'Why the matches are someone else' : 'Note (optional)'" :value="$aml['note'] ?? ''" :required="(bool) $hits" />
        @error('aml')<p class="text-theme-xs text-error-600">{{ $message }}</p>@enderror
        <button class="rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Save screening</button>
        @if (! empty($aml['decided_at']))
          <p class="text-theme-xs text-gray-500">Last saved {{ \Illuminate\Support\Carbon::parse($aml['decided_at'])->format('j M Y, H:i') }} by {{ $aml['decided_by_name'] ?? '' }}.</p>
        @endif
      </form>
    @endcan
  @endif
</section>
