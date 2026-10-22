{{-- In-place updates (owner, 22 Oct 2026): staying on the same page never reloads the whole screen.
     - Links and GET forms that stay on this page (tabs, filters, page numbers, ?folder=...) fetch the page quietly
       and swap only the content area; the address bar and Back button still work.
     - POST forms on the page (approve, save, record, close a modal...) are sent in the background; when the server
       comes back to this same page only the content area is swapped and its message shows as a toast.
       If the server sends you to another page, the browser simply goes there.
     - Links to other pages start loading as soon as the pointer rests on them (speculation rules), so they open fast.
     Opt out with data-no-swap on a link or form. Downloads and new tabs are never touched. --}}
<script type="speculationrules">
{"prerender": [{"where": {"and": [{"href_matches": "/*"}, {"not": {"href_matches": "/logout*"}}, {"not": {"href_matches": "/*export*"}}, {"not": {"href_matches": "/*download*"}}, {"not": {"href_matches": "/*.pdf*"}}, {"not": {"selector_matches": "[data-no-swap], [download], [target=_blank]"}}]}, "eagerness": "moderate"}]}
</script>
<div id="az-progress" class="pointer-events-none fixed left-0 top-0 z-[1000002] h-0.5 w-0 bg-brand-500 opacity-0 transition-all duration-300"></div>
<script>
(function () {
  const main = () => document.querySelector('main');
  const bar = document.getElementById('az-progress');
  let busy = 0, controller = null;
  const progress = (on) => {
    busy += on ? 1 : -1;
    if (busy > 0) { bar.style.opacity = '1'; bar.style.width = '70%'; }
    else { bar.style.width = '100%'; setTimeout(() => { if (!busy) { bar.style.opacity = '0'; bar.style.width = '0'; } }, 250); }
  };
  const samePage = (url) => url.origin === location.origin && url.pathname === location.pathname;

  // scripts inside the new content run again; code waiting for "page ready" (DOMContentLoaded / alpine:init)
  // runs straight away, because that moment has already passed
  function runScripts(root) {
    const add = document.addEventListener, addW = window.addEventListener;
    const now = (type, fn, ...rest) => (type === 'DOMContentLoaded' || type === 'alpine:init') ? setTimeout(fn, 0) : null;
    document.addEventListener = function (type, fn, ...rest) { if (now(type, fn) === null) return add.call(document, type, fn, ...rest); };
    window.addEventListener = function (type, fn, ...rest) { if (type === 'load' ? (setTimeout(fn, 0), true) : now(type, fn) !== null) return; return addW.call(window, type, fn, ...rest); };
    try {
      root.querySelectorAll('script').forEach((old) => {
        if (old.type && !/javascript|module/.test(old.type)) return;
        const s = document.createElement('script');
        Array.from(old.attributes).forEach(a => s.setAttribute(a.name, a.value));
        s.textContent = old.textContent;
        old.replaceWith(s);
      });
    } finally { document.addEventListener = add; window.addEventListener = addW; }
  }

  function apply(html, url, push) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const fresh = doc.querySelector('main');
    if (!fresh || !main()) return false;
    main().innerHTML = fresh.innerHTML;
    // the sidebar counts change after most actions (approved, sent, reviewed)
    const nav = document.querySelector('aside.sidebar nav'), nav2 = doc.querySelector('aside.sidebar nav');
    if (nav && nav2) nav.replaceWith(nav2);
    if (doc.title) document.title = doc.title;
    runScripts(main());
    try {
      const flash = JSON.parse((doc.getElementById('az-flash') || {}).textContent || '[]');
      flash.forEach(t => window.dispatchEvent(new CustomEvent('toast', {detail: t})));
    } catch (e) {}
    if (push === 'push') history.pushState({az: 1}, '', url);
    else if (push === 'replace') history.replaceState({az: 1}, '', url);
    main().dispatchEvent(new CustomEvent('az:updated', {bubbles: true}));
    return true;
  }

  async function load(url, opts = {}, push = 'push') {
    if (controller) controller.abort();
    controller = new AbortController();
    progress(true);
    try {
      const res = await fetch(url, Object.assign({credentials: 'same-origin', signal: controller.signal, headers: {'X-Requested-With': 'AzSwap', 'Accept': 'text/html'}}, opts));
      const type = res.headers.get('content-type') || '';
      const final = new URL(res.url || url, location.href);
      if (res.redirected && final.pathname.startsWith('/login')) { location.assign(final.href); return; }
      if (!type.includes('text/html') || res.status === 419) {                   // a file, JSON or an expired session
        if (opts.method === 'POST') location.reload(); else location.assign(final.href);
        return;
      }
      if (!samePage(final)) { location.assign(final.href); return; }            // the server moved us on: go there
      const html = await res.text();
      if (!apply(html, final.href, opts.method === 'POST' ? 'replace' : push)) location.assign(final.href);
    } catch (e) {
      if (e.name !== 'AbortError') location.assign(typeof url === 'string' ? url : url.href);
    } finally { progress(false); }
  }
  window.azSwap = {load, apply};

  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const a = e.target.closest('a[href]');
    if (!a || !a.closest('main') || a.closest('[data-no-swap]') || a.target || a.hasAttribute('download')) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    const url = new URL(a.href, location.href);
    if (!samePage(url) || /export|download|\.csv|\.pdf/i.test(url.search + url.pathname)) return;
    if (url.search === location.search && url.hash) return;   // a jump within the page
    e.preventDefault();
    load(url.href);
  });

  document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.closest('main') || form.closest('[data-no-swap]') || form.hasAttribute('data-ajax-form') || form.target) return;
    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    const action = new URL(form.getAttribute('action') || location.href, location.href);
    if (/export|download|\.csv|\.pdf/i.test(action.pathname + action.search)) return;   // files go the normal way
    const submitter = e.submitter;
    const data = new FormData(form, submitter || undefined);
    if (method === 'GET') {
      if (!samePage(action) || data.get('export')) return;
      e.preventDefault();
      action.search = new URLSearchParams(data).toString();
      load(action.href);
      return;
    }
    e.preventDefault();
    if (submitter) { submitter.disabled = true; setTimeout(() => { submitter.disabled = false; }, 4000); }
    load(action.href, {method: 'POST', body: data});
  });

  window.addEventListener('popstate', (e) => { if (e.state && e.state.az) load(location.href, {}, 'none'); });
})();
</script>
