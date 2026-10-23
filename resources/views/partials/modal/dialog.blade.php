{{-- The one dialog for the whole app (owner, 22 Oct 2026): the alert modal's look, for messages and confirmations.
     No browser alert() or confirm() anywhere. Included once by the layouts.
       uiAlert('Saved.', {type: 'success', title: 'Done'})          -> Promise (resolves when closed)
       uiConfirm('Delete this repayment?', {danger: true})            -> Promise<boolean>
       <form data-confirm="Change this permission for everyone?">    -> asks before submitting
       <button type="submit" data-confirm="Record forbearance?">     -> asks before submitting its form
       <button type="button" data-confirm="End forbearance?" data-confirm-submit="end-forbearance-form"> --}}
<div x-data="uiDialog()" x-show="open" x-cloak @keydown.escape.window="open && answer(false)"
     class="fixed inset-0 z-[1000000] flex items-center justify-center overflow-y-auto p-5" role="dialog" aria-modal="true" :aria-label="title">
  <div class="fixed inset-0 az-backdrop" @click="answer(false)"></div>
  <div class="relative w-full max-w-md rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10" x-show="open" x-transition>
    <button type="button" @click="answer(false)" aria-label="Close"
            class="absolute right-3 top-3 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
      <x-ui.icon name="x-circle" class="hidden" /><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
    <div class="text-center">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
           :class="{'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500': type === 'success', 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500': type === 'error' || type === 'danger',
                    'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400': type === 'warning', 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400': type === 'info' || type === 'confirm'}">
        <template x-if="type === 'success'"><x-ui.icon name="check-circle" class="size-8" /></template>
        <template x-if="type === 'error' || type === 'danger'"><x-ui.icon name="alert" class="size-8" /></template>
        <template x-if="type === 'warning'"><x-ui.icon name="alert" class="size-8" /></template>
        <template x-if="type === 'info' || type === 'confirm'"><x-ui.icon name="info" class="size-8" /></template>
      </div>
      <h3 class="mt-4 text-lg font-semibold text-gray-800 dark:text-white/90" x-text="title"></h3>
      <p class="mt-2 whitespace-pre-line text-sm text-gray-500 dark:text-gray-400" x-text="message"></p>
    </div>
    <div class="mt-7 flex justify-center gap-3">
      <button type="button" x-show="mode === 'confirm'" @click="answer(false)"
              class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]" x-text="cancelText"></button>
      <button type="button" x-ref="ok" @click="answer(true)" class="rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs"
              :class="type === 'danger' ? 'bg-error-500 hover:bg-error-600' : 'bg-brand-500 hover:bg-brand-600'" x-text="okText"></button>
    </div>
  </div>
</div>
<script>
  function uiDialog() {
    return {
      open: false, mode: 'alert', type: 'info', title: '', message: '', okText: 'OK', cancelText: 'Cancel', resolve: null, queue: [],
      init() {
        window.__uiDialog = this;
      },
      ask(mode, message, o = {}) {
        return new Promise(resolve => {
          const show = () => {
            Object.assign(this, {mode, message: String(message ?? ''), resolve,
              type: o.type || (mode === 'confirm' ? (o.danger ? 'danger' : 'confirm') : 'info'),
              title: o.title || (mode === 'confirm' ? 'Are you sure?' : ({success: 'Done', error: 'Something went wrong', warning: 'Check this'}[o.type] || 'Notice')),
              okText: o.okText || (mode === 'confirm' ? (o.danger ? 'Yes, go ahead' : 'Confirm') : 'OK'), cancelText: o.cancelText || 'Cancel', open: true});
            this.$nextTick(() => this.$refs.ok && this.$refs.ok.focus());
          };
          this.open ? this.queue.push(show) : show();
        });
      },
      answer(yes) {
        if (! this.open) return;
        this.open = false;
        const r = this.resolve; this.resolve = null;
        r && r(this.mode === 'confirm' ? yes : true);
        const next = this.queue.shift(); if (next) setTimeout(next, 150);
      },
    };
  }
  window.uiAlert = (message, o = {}) => window.__uiDialog ? window.__uiDialog.ask('alert', message, o) : Promise.resolve(true);
  window.uiConfirm = (message, o = {}) => window.__uiDialog ? window.__uiDialog.ask('confirm', message, o) : Promise.resolve(false);
  window.alert = (message) => { window.uiAlert(message); };
  // pages without the old alert modal still answer showAlert(type, title, message); a page that includes
  // partials.modal.alert-modal replaces this with its own (only one of them listens, so no double pop-ups)
  window.showAlert = window.showAlert || ((type, title, message) => window.uiAlert(message, {type, title}));
  // forms and buttons that ask first: data-confirm="question" (data-confirm-danger for destructive ones)
  document.addEventListener('submit', async (e) => {
    const form = e.target, by = e.submitter;
    const q = (by && by.dataset.confirm) || form.dataset.confirm;
    if (! q || form.dataset.confirmed === '1') return;
    e.preventDefault();
    const danger = (by && 'confirmDanger' in by.dataset) || 'confirmDanger' in form.dataset;
    if (await window.uiConfirm(q, {danger})) { form.dataset.confirmed = '1'; by ? form.requestSubmit(by) : form.requestSubmit(); delete form.dataset.confirmed; }
  }, true);
  document.addEventListener('click', async (e) => {
    const el = e.target.closest('[data-confirm]:not(form):not([type="submit"])');
    if (! el) return;
    e.preventDefault(); e.stopImmediatePropagation();
    if (! await window.uiConfirm(el.dataset.confirm, {danger: 'confirmDanger' in el.dataset})) return;
    if (el.dataset.confirmSubmit) { const f = document.getElementById(el.dataset.confirmSubmit); f && f.submit(); }
    else if (el.href) { window.location = el.href; }
  }, true);
</script>
