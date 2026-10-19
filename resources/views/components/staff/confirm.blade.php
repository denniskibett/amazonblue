{{-- Button that opens a confirm <dialog>; confirming submits a form (with CSRF). Same props as x-az.confirm:
     id, action, method, title, trigger, confirm, cancel, tone (danger), trigger-class; slot = message; <x-slot:fields> = extra inputs --}}
@props(['id', 'action', 'method' => 'POST', 'title', 'trigger' => 'Continue', 'confirm' => 'Confirm', 'cancel' => 'Cancel', 'tone' => null,
  'triggerClass' => 'inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'])
<button type="button" class="{{ $triggerClass }}" onclick="document.getElementById('{{ $id }}').showModal()">{{ $trigger }}</button>
<dialog {{ $attributes->class(['m-auto w-[min(480px,calc(100%-32px))] rounded-2xl bg-white p-6 shadow-theme-xl backdrop:bg-gray-900/50 dark:bg-gray-900']) }} id="{{ $id }}" aria-labelledby="{{ $id }}-title" onclick="if (event.target === this) this.close()">
  <form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if (strtoupper($method) !== 'POST') @method($method) @endif
    <h2 id="{{ $id }}-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
    @if (! $slot->isEmpty())<p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $slot }}</p>@endif
    {{ $fields ?? '' }}
    <div class="flex flex-wrap-reverse justify-end gap-3 pt-2">
      <button type="button" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" onclick="this.closest('dialog').close()">{{ $cancel }}</button>
      <button type="submit" @class(['rounded-lg px-4 py-2.5 text-theme-sm font-medium text-white', 'bg-error-600 hover:bg-error-700' => $tone === 'danger', 'bg-brand-500 hover:bg-brand-600' => $tone !== 'danger'])>{{ $confirm }}</button>
    </div>
  </form>
</dialog>
