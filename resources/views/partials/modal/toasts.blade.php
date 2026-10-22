{{-- Toasts for the whole app (owner, 22 Oct 2026). Every flashed message shows here, whichever page it lands on:
     with('success'|'error'|'warning'|'info', '...') (status codes stay with their pages), with('alert', ['type','title','message']) and form errors.
     From script: toast('Saved', 'success') or window.dispatchEvent(new CustomEvent('toast', {detail: {type, message}})). --}}
@php
  $flash = [];
  foreach (['success', 'error', 'warning', 'info'] as $k) {
      if (session()->has($k) && is_string(session($k)) && trim(session($k)) !== '') {
          $flash[] = ['type' => $k, 'message' => session($k)];
      }
  }
  if (is_array(session('alert'))) {
      $a = session('alert');
      $flash[] = ['type' => $a['type'] ?? 'info', 'title' => $a['title'] ?? null, 'message' => $a['message'] ?? ''];
  }
  if (isset($errors) && $errors->any()) {
      $flash[] = ['type' => 'error', 'title' => 'Please check the form', 'message' => implode("\n", array_slice($errors->all(), 0, 4))];
  }
@endphp
<script type="application/json" id="az-flash">@json($flash)</script>
<div x-data="uiToasts(@js($flash))" @toast.window="push($event.detail)"
     class="pointer-events-none fixed right-4 top-4 z-[1000001] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3 sm:right-6 sm:top-6" aria-live="polite">
  <template x-for="t in items" :key="t.id">
    <div x-show="t.show" x-transition.opacity.duration.200ms
         class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-4 shadow-theme-lg dark:bg-gray-900"
         :class="{'border-success-500': t.type === 'success', 'border-error-500': t.type === 'error', 'border-amber-500': t.type === 'warning', 'border-brand-500': t.type === 'info'}">
      <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full"
            :class="{'bg-success-50 text-success-600 dark:bg-success-500/15': t.type === 'success', 'bg-error-50 text-error-600 dark:bg-error-500/15': t.type === 'error', 'bg-amber-50 text-amber-600 dark:bg-amber-500/15': t.type === 'warning', 'bg-brand-50 text-brand-500 dark:bg-brand-500/15': t.type === 'info'}">
        <template x-if="t.type === 'success'"><x-ui.icon name="check" class="size-4" /></template>
        <template x-if="t.type === 'error' || t.type === 'warning'"><x-ui.icon name="alert" class="size-4" /></template>
        <template x-if="t.type === 'info'"><x-ui.icon name="info" class="size-4" /></template>
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="t.title"></p>
        <p class="mt-0.5 whitespace-pre-line text-sm text-gray-600 dark:text-gray-400" x-text="t.message"></p>
      </div>
      <button type="button" @click="close(t)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close"><x-ui.icon name="x" class="size-4" /></button>
    </div>
  </template>
</div>
<script>
  function uiToasts(initial) {
    return {
      items: [], n: 0,
      init() { (initial || []).forEach(t => this.push(t)); window.__uiToasts = this; },
      push(t) {
        const type = ({danger: 'error'})[t.type] || t.type || 'info';
        const item = {id: ++this.n, show: true, type, message: t.message || '',
          title: t.title || ({success: 'Done', error: 'Something went wrong', warning: 'Check this', info: 'Notice'})[type] || 'Notice'};
        this.items.push(item);
        // errors stay until closed; the rest go after a few seconds
        if (type !== 'error') setTimeout(() => this.close(item), type === 'warning' ? 8000 : 5000);
      },
      close(item) { item.show = false; setTimeout(() => { this.items = this.items.filter(i => i !== item); }, 250); },
    };
  }
  window.toast = (message, type = 'success', title = null) => window.dispatchEvent(new CustomEvent('toast', {detail: {message, type, title}}));
</script>
