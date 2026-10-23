{{-- Person side panel (owner, 22 Oct 2026). Any name in the staff pages opens it: links to /users/{id}, or any
     element with data-user-peek="{id}". Ctrl/Cmd-click still opens the full profile in a new tab. --}}
@if (auth()->check() && auth()->user()->can('view users') && \Illuminate\Support\Facades\Route::has('staff.users.peek'))
<div x-data="azPeek(@js(url('/staff/users')), @js(url('/users')))" @keydown.escape.window="close()" @az-peek.window="open($event.detail)">
  <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-[100000] az-backdrop" @click="close()"></div>
  <aside x-show="show" x-cloak
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[100001] flex w-full max-w-md flex-col bg-white shadow-theme-xl dark:bg-gray-900" role="dialog" aria-modal="true" aria-label="Person details">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
      <a :href="profileUrl" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-theme-sm font-medium text-white hover:bg-brand-600">Open full profile</a>
      <button type="button" @click="close()" class="flex size-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
    </div>
    <div class="flex-1 overflow-y-auto p-5" x-ref="body">
      <div x-show="loading" class="space-y-3">
        <div class="h-16 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"></div>
        <div class="h-24 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"></div>
        <div class="h-40 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"></div>
      </div>
      <div x-show="!loading" x-ref="content"></div>
    </div>
  </aside>
</div>
<script>
function azPeek(peekBase, profileBase) {
  return {
    show: false, loading: false, profileUrl: '#', cache: {},
    init() {
      document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const el = e.target.closest('[data-user-peek], a[href]');
        if (!el || el.closest('[data-no-peek]') || el.closest('aside[role=dialog]')) return;
        let id = el.dataset.userPeek;
        if (!id && el.tagName === 'A') {
          const u = new URL(el.href, location.href);
          const m = u.origin === location.origin && u.pathname.match(/^\/users\/(\d+)\/?$/);
          if (!m || el.target) return;
          id = m[1];
        }
        if (!id) return;
        e.preventDefault(); e.stopPropagation();
        this.open(id);
      }, true);
    },
    async open(id) {
      this.show = true; this.profileUrl = profileBase + '/' + id;
      if (this.cache[id]) { this.$refs.content.innerHTML = this.cache[id]; this.loading = false; return; }
      this.loading = true;
      try {
        const r = await fetch(peekBase + '/' + id + '/peek', {credentials: 'same-origin', headers: {'Accept': 'text/html'}});
        if (!r.ok) { location.assign(this.profileUrl); return; }
        const html = await r.text();
        this.cache[id] = html;
        this.$refs.content.innerHTML = html;
      } catch (e) { location.assign(this.profileUrl); } finally { this.loading = false; }
    },
    close() { this.show = false; },
  };
}
</script>
@endif
