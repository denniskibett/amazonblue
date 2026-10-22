{{-- The existing loans.update / loans.reject / disbursements.store / repayments.store answer JSON to AJAX.
     Forms with data-ajax-form submit with fetch, show the server's validation errors in [data-errors],
     and on success go to data-done (a staff desk page with ?done=...). --}}
@once
@push('scripts')
<script>
document.addEventListener('submit', async (event) => {
  const form = event.target.closest('form[data-ajax-form]');
  if (!form) return;
  event.preventDefault();
  const box = form.querySelector('[data-errors]');
  const button = form.querySelector('[type=submit]');
  const label = button ? button.textContent : '';
  if (box) { box.hidden = true; box.textContent = ''; }
  if (button) { button.disabled = true; button.textContent = 'Saving…'; }
  try {
    const res = await fetch(form.action, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: new FormData(form),
      credentials: 'same-origin',
    });
    const body = await res.json().catch(() => ({}));
    if (res.ok && body.success !== false) {
      let done = form.dataset.done || window.location.href;
      const id = body?.data?.id || body?.repayment?.id || body?.loan?.id;
      if (id) done += (done.includes('?') ? '&' : '?') + 'ref=' + encodeURIComponent(id);
      window.location.href = done;
      return;
    }
    const list = body.errors ? Object.values(body.errors).flat() : [body.message || ('The server answered ' + res.status + '.')];
    if (box) {
      box.innerHTML = '';
      list.forEach((m) => { const p = document.createElement('p'); p.textContent = m; box.appendChild(p); });
      box.hidden = false;
    } else {
      uiAlert(list.join('\n'));
    }
  } catch (e) {
    if (box) { box.textContent = 'Could not reach the server. Nothing was saved.'; box.hidden = false; }
  } finally {
    if (button) { button.disabled = false; button.textContent = label; }
  }
});
</script>
@endpush
@endonce
