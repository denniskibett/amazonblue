{{-- Staff desk dialog whose form submits by AJAX to an existing JSON route (see staff/desk/_ajax).
     Like x-staff.confirm, plus: done (where to go after success). Slot = fields. --}}
@props(['id', 'action', 'method' => 'POST', 'title', 'trigger', 'confirm' => 'Confirm', 'tone' => null, 'done', 'intro' => null,
  'triggerClass' => 'inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'])
<button type="button" class="{{ $triggerClass }}" onclick="document.getElementById('{{ $id }}').showModal()">{{ $trigger }}</button>
<dialog class="m-auto w-[min(520px,calc(100%-32px))] rounded-2xl bg-white p-6 text-left shadow-theme-xl backdrop:bg-gray-900/50 dark:bg-gray-900" id="{{ $id }}" aria-labelledby="{{ $id }}-title" onclick="if (event.target === this) this.close()">
  <form method="POST" action="{{ $action }}" class="space-y-4 whitespace-normal" data-ajax-form data-done="{{ $done }}">
    @csrf
    @if (strtoupper($method) !== 'POST') @method($method) @endif
    <h2 id="{{ $id }}-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
    @if ($intro)<p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $intro }}</p>@endif
    {{ $slot }}
    <div data-errors hidden class="rounded-lg border border-error-500/30 bg-error-50 p-3 text-theme-sm text-error-800 dark:bg-error-500/15 dark:text-error-400" role="alert"></div>
    <div class="flex flex-wrap-reverse justify-end gap-3 pt-2">
      <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" onclick="this.closest('dialog').close()">Cancel</button>
      <button type="submit" @class(['rounded-lg px-4 py-2.5 text-theme-sm font-medium text-white disabled:opacity-60', 'bg-error-600 hover:bg-error-700' => $tone === 'danger', 'bg-brand-500 hover:bg-brand-600' => $tone !== 'danger'])>{{ $confirm }}</button>
    </div>
  </form>
</dialog>
