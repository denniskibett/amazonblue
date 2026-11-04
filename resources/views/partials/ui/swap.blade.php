{{-- In-place updates (owner, 22 Oct 2026): staying on the same page never reloads the whole screen.
     - Links and GET forms that stay on this page (tabs, filters, page numbers, ?folder=...) fetch the page quietly
       and swap only the content area; the address bar and Back button still work.
     - POST forms on the page (approve, save, record, close a modal...) are sent in the background; when the server
       comes back to this same page only the content area is swapped and its message shows as a toast.
       If the server sends you to another page, the browser simply goes there.
     - Links to other pages start loading as soon as the pointer rests on them (speculation rules), so they open fast.
     - Sidebar links (owner, 3 Nov 2026) open the next page the same way: only the content area, the menu and the title
       change, and that page's own scripts (@push('scripts')) run once. A page that needs a script file or stylesheet
       this one hasn't loaded, or isn't a staff page, loads in full instead.
     Opt out with data-no-swap on a link or form. Downloads and new tabs are never touched.
     - Deletes (owner, 1 Nov 2026): when the server answers a delete with another page (e.g. the list), you stay where
       you are; this page is refreshed in the background and the server's message shows as a notification. Only when
       this page no longer exists (you deleted the record you were looking at) does the browser go to the other page.
     - window.azRefresh() (owner, 23 Oct 2026): after a modal saves, the cards, tables and counts on screen are fetched
       again in the background instead of reloading the page.
     - <a data-drawer href="..."> opens that page in a side drawer over the current one. Forms in the drawer are sent in
       the background: a save that stays on the drawer's page shows there; a JSON save (data-ajax-form) closes the
       drawer. Either way the page underneath refreshes quietly. --}}
<script type="speculationrules">
{"prerender": [{"where": {"and": [{"href_matches": "/*"}, {"not": {"href_matches": "/logout*"}}, {"not": {"href_matches": "/*export*"}}, {"not": {"href_matches": "/*download*"}}, {"not": {"href_matches": "/*.pdf*"}}, {"not": {"selector_matches": "[data-no-swap], [download], [target=_blank]"}}]}, "eagerness": "moderate"}]}
</script>
<div id="az-progress" class="pointer-events-none fixed left-0 top-0 z-[1000002] h-0.5 w-0 bg-brand-500 opacity-0 transition-all duration-300"></div>
<div id="az-drawer" hidden class="fixed inset-0 z-[100000]">
  <div class="az-backdrop absolute inset-0" data-drawer-close></div>
  <section class="absolute inset-y-0 right-0 flex w-full max-w-4xl flex-col bg-gray-50 shadow-theme-xl dark:bg-gray-900" role="dialog" aria-modal="true">
    <header class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
      <p class="truncate text-base font-semibold text-gray-800 dark:text-white/90" data-drawer-title></p>
      <button type="button" data-drawer-close class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5" aria-label="Close">✕</button>
    </header>
    <div class="az-drawer-body flex-1 overflow-y-auto p-4 sm:p-6" data-drawer-body></div>
  </section>
</div>
<style>.az-drawer-body nav[aria-label="breadcrumb"], .az-drawer-body nav[aria-label="Breadcrumb"] { display: none; }</style>
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
  function runScripts(root, list) {
    const add = document.addEventListener, addW = window.addEventListener;
    // alpine:init runs straight away (synchronously), so components the new content registers with Alpine.data()
    // exist before Alpine starts the new content; with a delay they were missing and modals stopped opening
    // after a background refresh (owner, 23 Oct 2026). DOMContentLoaded code runs just after.
    const now = (type, fn, ...rest) => type === 'alpine:init' ? (fn(), true) : (type === 'DOMContentLoaded' ? setTimeout(fn, 0) : null);
    document.addEventListener = function (type, fn, ...rest) { if (now(type, fn) === null) return add.call(document, type, fn, ...rest); };
    window.addEventListener = function (type, fn, ...rest) { if (type === 'load' ? (setTimeout(fn, 0), true) : now(type, fn) !== null) return; return addW.call(window, type, fn, ...rest); };
    try {
      if (list) {
        // another page's own scripts: run once, in a block so a second visit can't trip over const/let already declared
        list.forEach((old) => {
          const s = document.createElement('script');
          const text = old.textContent;
          s.textContent = /['"]use strict['"]/.test(text) ? text : '{\n' + text + '\n}';
          s.setAttribute('data-az-page', '');
          root.appendChild(s);
        });
        return;
      }
      root.querySelectorAll('script').forEach((old) => {
        if (old.type && !/javascript|module/.test(old.type)) return;
        const s = document.createElement('script');
        Array.from(old.attributes).forEach(a => s.setAttribute(a.name, a.value));
        s.textContent = old.textContent;
        old.replaceWith(s);
      });
    } finally { document.addEventListener = add; window.addEventListener = addW; }
  }

  // the page's own scripts: between the two markers the layout puts around the pushed scripts stack
  function pageScripts(doc) {
    const start = doc.querySelector('script[type="text/x-az-page-scripts"][data-start]');
    const out = [];
    for (let n = start && start.nextElementSibling; n && !n.matches('script[type="text/x-az-page-scripts"][data-end]'); n = n.nextElementSibling) {
      if (n.tagName === 'SCRIPT' && (!n.type || /javascript|module/.test(n.type)) && !n.src) out.push(n);
      else n.querySelectorAll && n.querySelectorAll('script:not([src])').forEach(x => out.push(x));
    }
    return out;
  }
  // a different page can be swapped in only if it is a staff page that needs no file this one hasn't loaded
  function swappable(doc) {
    if (!doc.querySelector('aside.sidebar') || !doc.querySelector('script[type="text/x-az-page-scripts"][data-start]')) return false;
    const have = new Set(Array.from(document.querySelectorAll('script[src], link[rel="stylesheet"]')).map(e => (e.src || e.href).split('?')[0]));
    const needs = Array.from(doc.querySelectorAll('script[src], link[rel="stylesheet"]'))
      .map(e => new URL(e.getAttribute('src') || e.getAttribute('href'), location.href).href.split('?')[0]);
    const missing = needs.filter(u => !have.has(u));
    if (missing.length) console.info('loading in full: this page needs', missing);
    return missing.length === 0;
  }

  function apply(html, url, push, cross) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const fresh = doc.querySelector('main');
    if (!fresh || !main()) return false;
    if (cross && !swappable(doc)) return false;
    main().innerHTML = fresh.innerHTML;
    // the sidebar counts change after most actions (approved, sent, reviewed)
    const nav = document.querySelector('aside.sidebar nav'), nav2 = doc.querySelector('aside.sidebar nav');
    if (nav && nav2) nav.replaceWith(nav2);
    if (doc.title) document.title = doc.title;
    if (cross) {
      document.querySelectorAll('script[data-az-page]').forEach(x => x.remove());
      runScripts(document.body, pageScripts(doc));
      const scroller = main().parentElement; if (scroller) scroller.scrollTop = 0;
      window.dispatchEvent(new CustomEvent('az:navigated', {detail: {url}}));
    }
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

  // a full load, with the reason kept for anyone checking why a click did not swap (sessionStorage azLastFull)
  const full = (href, why) => { try { sessionStorage.setItem('azLastFull', why + ' ' + href); } catch (e) {} location.assign(href); };

  async function load(url, opts = {}, push = 'push') {
    if (controller) controller.abort();
    controller = new AbortController();
    progress(true);
    try {
      const res = await fetch(url, Object.assign({credentials: 'same-origin', signal: controller.signal, headers: {'X-Requested-With': 'AzSwap', 'Accept': 'text/html'}}, opts));
      const type = res.headers.get('content-type') || '';
      const final = new URL(res.url || url, location.href);
      if (res.redirected && final.pathname.startsWith('/login')) { full(final.href, 'load-1'); return; }
      if (!type.includes('text/html') || res.status === 419) {                   // a file, JSON or an expired session
        if (opts.method === 'POST') location.reload(); else full(final.href, 'load-2');
        return;
      }
      if (!samePage(final) && opts.stay) {                                        // a delete: stay here, refresh, notify
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const here = await fetch(location.href, {credentials: 'same-origin', headers: {'X-Requested-With': 'AzSwap', 'Accept': 'text/html'}});
        if (!here.ok || here.redirected || !apply(await here.text(), location.href, 'none')) { full(final.href, 'load-3'); return; }
        try { JSON.parse((doc.getElementById('az-flash') || {}).textContent || '[]').forEach(t => window.dispatchEvent(new CustomEvent('toast', {detail: t}))); } catch (e) {}
        return;
      }
      if (!samePage(final) && !opts.cross) { full(final.href, 'load-4'); return; }   // the server moved us on: go there
      const html = await res.text();
      if (!apply(html, final.href, opts.method === 'POST' ? 'replace' : push, opts.cross && !samePage(final))) full(final.href, 'load-5');
    } catch (e) {
      if (e.name !== 'AbortError') { console.warn('in-place load failed, loading in full', e); full(typeof url === 'string' ? url : url.href, 'load-6'); }
    } finally { progress(false); }
  }
  window.azSwap = {load, apply};
  // background refresh of the page on screen (cards, tables, counts); falls back to a reload outside the staff layout
  window.azRefresh = () => load(location.href, {}, 'none');

  // ---------- side drawer: another page opened over this one ----------
  const drawer = document.getElementById('az-drawer');
  const dBody = drawer.querySelector('[data-drawer-body]');
  const dTitle = drawer.querySelector('[data-drawer-title]');
  let dUrl = null;
  const toastAll = (doc) => { try { JSON.parse((doc.getElementById('az-flash') || {}).textContent || '[]').forEach(t => window.dispatchEvent(new CustomEvent('toast', {detail: t}))); } catch (e) {} };
  function dShow(html, url) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    const fresh = doc.querySelector('main');
    if (!fresh) return false;
    dUrl = url;
    dBody.innerHTML = fresh.innerHTML;
    dTitle.textContent = (doc.querySelector('main h1, main h2') || {}).textContent || (doc.title || '').split('|')[0];
    runScripts(dBody);
    toastAll(doc);
    return true;
  }
  async function dLoad(url, opts = {}) {
    drawer.hidden = false;
    document.documentElement.classList.add('overflow-hidden');
    if (!opts.method) dBody.innerHTML = '<div class="p-8 text-center text-theme-sm text-gray-500">Loading…</div>';
    progress(true);
    try {
      const res = await fetch(url, Object.assign({credentials: 'same-origin', headers: {'X-Requested-With': 'AzSwap', 'Accept': 'text/html'}}, opts));
      const final = new URL(res.url || url, location.href);
      if (res.redirected && final.pathname.startsWith('/login')) { location.assign(final.href); return; }
      if (final.pathname === location.pathname && opts.method) { dClose(); const html = await res.text(); apply(html, location.href, 'none'); return; }
      if (final.pathname !== new URL(dUrl || url, location.href).pathname && opts.method) { location.assign(final.href); return; }
      if (!dShow(await res.text(), final.href)) location.assign(final.href);
      if (opts.method) window.azRefresh();          // the drawer saved something: refresh what is underneath
    } catch (e) { dBody.innerHTML = '<div class="p-8 text-center text-theme-sm text-error-600">Could not load this. Check your connection and try again.</div>'; }
    finally { progress(false); }
  }
  function dClose() { drawer.hidden = true; dBody.innerHTML = ''; dUrl = null; document.documentElement.classList.remove('overflow-hidden'); }
  window.azDrawer = {open: (url) => { dUrl = url; return dLoad(url); }, close: dClose};
  drawer.querySelectorAll('[data-drawer-close]').forEach(b => b.addEventListener('click', dClose));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !drawer.hidden) dClose(); });

  // links in the drawer: same page → stay in the drawer; the page underneath → just close; anything else → go there
  drawer.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || e.defaultPrevented || a.target || a.hasAttribute('download') || e.metaKey || e.ctrlKey) return;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || /export|download|\.csv|\.pdf/i.test(url.pathname + url.search)) return;
    if (url.pathname === location.pathname && !url.search) { e.preventDefault(); dClose(); return; }
    if (dUrl && url.pathname === new URL(dUrl, location.href).pathname) { e.preventDefault(); dLoad(url.href); }
  });
  // forms in the drawer
  drawer.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || e.defaultPrevented || form.target) return;
    e.preventDefault();
    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    const action = new URL(form.getAttribute('action') || dUrl, location.href);
    const submitter = e.submitter;
    const data = new FormData(form, submitter || undefined);
    if (method === 'GET') { action.search = new URLSearchParams(data).toString(); return dLoad(action.href); }
    if (!form.hasAttribute('data-ajax-form')) {
      if (submitter) { submitter.disabled = true; setTimeout(() => { submitter.disabled = false; }, 4000); }
      return dLoad(action.href, {method: 'POST', body: data});
    }
    // JSON forms (disburse, record payment...): errors stay in the drawer; success closes it
    const box = form.querySelector('[data-errors]');
    const label = submitter ? submitter.textContent : '';
    if (box) { box.hidden = true; box.textContent = ''; }
    if (submitter) { submitter.disabled = true; submitter.textContent = 'Saving…'; }
    try {
      const res = await fetch(action.href, {method: 'POST', body: data, credentials: 'same-origin', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
      const body = await res.json().catch(() => ({}));
      if (res.ok && body.success !== false) {
        dClose();
        window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', message: body.message || 'Saved.'}}));
        window.azRefresh();
        return;
      }
      const list = body.errors ? Object.values(body.errors).flat() : [body.message || ('The server answered ' + res.status + '.')];
      if (box) { box.innerHTML = ''; list.forEach(m => { const p = document.createElement('p'); p.textContent = m; box.appendChild(p); }); box.hidden = false; box.scrollIntoView({block: 'nearest'}); }
      else window.dispatchEvent(new CustomEvent('toast', {detail: {type: 'error', title: 'Not saved', message: list.join('\n')}}));
    } catch (err) {
      if (box) { box.textContent = 'Could not reach the server. Nothing was saved.'; box.hidden = false; }
    } finally { if (submitter) { submitter.disabled = false; submitter.textContent = label; } }
  });

  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const d = e.target.closest('a[data-drawer][href]');
    if (d && !d.closest('#az-drawer')) { e.preventDefault(); window.azDrawer.open(d.href); return; }
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

  // sidebar: open the next staff page in place. Capture phase, so no other click handler on the page gets there first.
  document.addEventListener('click', (e) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    // and a module's tabs (partials.module-tabs): the module reads as one page
    const a = e.target.closest && e.target.closest('aside.sidebar nav a[href], nav[data-module-tabs] a[href]');
    if (!a || a.closest('[data-no-swap]') || a.target || a.hasAttribute('download')) return;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin || /logout|preview|export|download|\.csv|\.pdf/i.test(url.pathname + url.search)) return;
    e.preventDefault();
    e.stopPropagation();
    if (window.innerWidth < 1024) document.body.dispatchEvent(new CustomEvent('az:close-sidebar'));
    load(url.href, {cross: true});
  }, true);

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
    load(action.href, {method: 'POST', body: data, stay: String(data.get('_method') || '').toUpperCase() === 'DELETE'});
  });

  if (!history.state) history.replaceState({az: 1}, '', location.href);   // so Back to the first page swaps too
  window.addEventListener('popstate', (e) => { if (e.state && e.state.az) load(location.href, {cross: true}, 'none'); });
})();
</script>
