{{-- The one table engine for every list in the app (owner, 22 Oct 2026). Plain script, no build step.

     <table data-table>                          turns a table into a standard list:
       - search box (looks through every cell, or the cells marked data-search)
       - filters: any <th data-filter> becomes a dropdown of that column's values (td[data-filter-value] wins over text)
       - sort: click a header (td[data-sort-value] wins over text; numbers sort as numbers); <th data-nosort> to skip
       - rows per page: 5, 10, 25, 50, 100, 250, 500, 1000, offered only up to what the table holds
       - Previous on the left, page numbers in the middle, Next on the right
       - a cell with three or more buttons or links (or td[data-actions]) folds into a "more" menu
     Options on the table: data-table-page="25" (first page size), data-table-search="Search loans",
     data-table-buttons="#id" (elements moved into the toolbar, e.g. the button that opens a modal).
     Rows: tr[data-table-skip] stays as it is (headings, empty notes); tr[data-table-child] travels with the row above.
     After changing rows from script: table.azTable.refresh(). --}}
<script>
(function () {
  const SIZES = [5, 10, 25, 50, 100, 250, 500, 1000];
  const cls = {
    input: 'h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2 pl-9 pr-3 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 sm:w-64',
    select: 'h-10 w-full rounded-lg sm:w-auto sm:max-w-48 border border-gray-300 bg-transparent px-3 pr-8 text-theme-sm text-gray-700 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
    pageBtn: 'flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-theme-sm font-medium',
    navBtn: 'inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]',
  };
  const icon = {
    search: '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    left: '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>',
    right: '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>',
    more: '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>',
    sort: '<svg class="ml-1 inline size-3 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>',
  };
  const text = (el) => (el ? el.textContent : '').replace(/\s+/g, ' ').trim();
  const num = (v) => { const n = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); return /\d/.test(v) && !isNaN(n) ? n : null; };
  const make = (tag, className, html) => { const e = document.createElement(tag); if (className) e.className = className; if (html != null) e.innerHTML = html; return e; };

  // ---------------------------------------------------------------- actions menus
  let openMenu = null;
  const closeMenu = () => { if (openMenu) { openMenu.panel.classList.add('hidden'); openMenu.btn.setAttribute('aria-expanded', 'false'); openMenu = null; } };
  document.addEventListener('click', (e) => { if (openMenu && !openMenu.panel.contains(e.target) && !openMenu.btn.contains(e.target)) closeMenu(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
  window.addEventListener('scroll', closeMenu, true);

  function foldActions(td) {
    if (td.dataset.actionsDone) return;
    const items = Array.from(td.querySelectorAll('a[href], button, [role="button"]')).filter(el => !el.closest('[data-actions-keep]'));
    if (!('actions' in td.dataset) && items.length < 3) return;
    td.dataset.actionsDone = '1';
    const wrap = make('div', 'relative inline-block text-left');
    const btn = make('button', 'flex size-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200', icon.more);
    btn.type = 'button'; btn.setAttribute('aria-label', 'Actions'); btn.setAttribute('aria-haspopup', 'menu'); btn.setAttribute('aria-expanded', 'false');
    const panel = make('div', 'hidden fixed z-[99999] min-w-44 rounded-xl border border-gray-200 bg-white p-1.5 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 [&>*]:flex [&>*]:w-full [&>*]:items-center [&>*]:gap-2 [&>*]:rounded-lg [&>*]:px-3 [&>*]:py-2 [&>*]:text-left [&>*]:text-theme-sm [&>*]:text-gray-700 hover:[&>*]:bg-gray-100 dark:[&>*]:text-gray-300 dark:hover:[&>*]:bg-white/5 [&_form]:m-0 [&_form>button]:w-full [&_form>button]:text-left');
    panel.setAttribute('role', 'menu');
    Array.from(td.childNodes).forEach(n => { if (n.nodeType === 1 || text(n)) panel.appendChild(n); });
    // inner wrappers (flex rows of buttons) are flattened so every action is one line in the menu
    panel.querySelectorAll(':scope > div:not([x-data])').forEach(d => { if (d.querySelector('a,button,form')) { while (d.firstChild) panel.insertBefore(d.firstChild, d); d.remove(); } });
    // icon-only links get their title written out, so the menu reads "View", "Edit", "Delete"
    panel.querySelectorAll('a, button').forEach(a => { if (!text(a) && (a.title || a.getAttribute('aria-label'))) a.append(make('span', '', a.title || a.getAttribute('aria-label'))); });
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (openMenu && openMenu.btn === btn) return closeMenu();
      closeMenu();
      const r = btn.getBoundingClientRect();
      panel.classList.remove('hidden');
      const w = panel.offsetWidth, h = panel.offsetHeight;
      panel.style.left = Math.max(8, Math.min(window.innerWidth - w - 8, r.right - w)) + 'px';
      panel.style.top = (r.bottom + h + 8 > window.innerHeight ? r.top - h - 4 : r.bottom + 4) + 'px';
      btn.setAttribute('aria-expanded', 'true');
      openMenu = {btn, panel};
    });
    panel.addEventListener('click', (e) => { if (e.target.closest('a,button')) setTimeout(closeMenu, 0); });
    wrap.append(btn); td.append(wrap); document.body.append(panel);
    td.classList.add('text-right');
  }

  // ---------------------------------------------------------------- the table
  function enhance(table) {
    if (table.azTable || table.dataset.table === 'off') return;
    const tbody = table.tBodies[0];
    if (!tbody) return;
    const head = Array.from(table.tHead ? table.tHead.rows : []).filter(r => getComputedStyle(r.parentElement).display !== 'none').pop() || (table.tHead && table.tHead.rows[0]);
    const ths = head ? Array.from(head.cells) : [];
    // old hand-made sort arrows give way to the engine's own
    ths.forEach(th => th.querySelectorAll('.sort-icon').forEach(i => i.remove()));
    const state = {q: '', page: 1, size: parseInt(table.dataset.tablePage || '10', 10), sort: null, dir: 1, filters: {}};

    // rows, keeping child rows with their parent
    let groups = [];
    function collect() {
      groups = [];
      Array.from(tbody.rows).forEach(tr => {
        if ('tableSkip' in tr.dataset) return;
        if ('tableChild' in tr.dataset && groups.length) { groups[groups.length - 1].rows.push(tr); return; }
        groups.push({rows: [tr], main: tr});
      });
      groups.forEach((g, i) => { g.i = i; g.text = (g.main.dataset.search || text(g.main)).toLowerCase(); });
      tbody.querySelectorAll('td[data-actions]').forEach(foldActions);
      // the actions cell is the last one shown on a desktop screen (phone-only cells, .sm:hidden, don't count)
      Array.from(tbody.rows).forEach(tr => { const cells = Array.from(tr.cells).filter(c => !c.classList.contains('sm:hidden')); if (cells.length) foldActions(cells[cells.length - 1]); });
    }
    collect();
    const cellOf = (g, col) => g.main.cells[col];
    const cellVal = (td, attr) => td ? (td.dataset[attr] ?? text(td)) : '';

    // toolbar, in the Loans Due layout (owner, 22 Oct 2026): the card title with "Showing 1 to 10 of N entries"
    // under it on the left; Show, search, filters and the page's buttons on the right
    const box = table.closest('.overflow-x-auto, .overflow-auto, [data-table-box]') || table;
    const card = table.closest('.rounded-2xl') || box.parentNode;
    const title = (table.dataset.tableTitle && document.querySelector(table.dataset.tableTitle))
      || Array.from(card.querySelectorAll('h2, h3')).find(h => !h.closest('table') && (h.compareDocumentPosition(box) & Node.DOCUMENT_POSITION_FOLLOWING));
    const bar = make('div', 'mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between');
    const left = make('div', 'min-w-0');
    const right = make('div', 'flex flex-wrap items-center gap-3');
    const counter = make('p', 'mt-1 text-sm text-gray-500 dark:text-gray-400');
    const titleWrap = title ? title.parentElement : null;
    if (title) { left.append(title); left.append(counter); } else { left.append(counter); }

    const sizeSel = make('select', 'appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2 pr-8 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200');
    sizeSel.setAttribute('aria-label', 'Rows per page');
    sizeSel.addEventListener('change', () => { state.size = parseInt(sizeSel.value, 10); state.page = 1; render(); });
    const sizeWrap = make('div', 'flex items-center', '<span class="mr-2 hidden text-sm text-gray-500 dark:text-gray-400 sm:inline">Show:</span>');
    const selBox = make('div', 'relative');
    selBox.prepend(sizeSel); sizeWrap.append(selBox); right.append(sizeWrap);

    const sWrap = make('div', 'relative min-w-[150px] flex-1', '<span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">' + icon.search.replace('size-4', 'size-5') + '</span>');
    const search = make('input', 'w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs placeholder:text-gray-400 hover:bg-gray-50 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]');
    search.type = 'search'; search.placeholder = (table.dataset.tableSearch || 'Search') + '...'; search.setAttribute('aria-label', table.dataset.tableSearch || 'Search');
    sWrap.append(search); right.append(sWrap);
    let t; search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { state.q = search.value.trim().toLowerCase(); state.page = 1; render(); }, 120); });

    ths.forEach((th, col) => {
      if (!('filter' in th.dataset)) return;
      const values = [...new Set(groups.map(g => cellVal(cellOf(g, col), 'filterValue')).filter(Boolean))].sort();
      if (values.length < 2) return;
      const sel = make('select', 'appearance-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-8 text-theme-sm font-medium text-gray-700 shadow-theme-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400');
      sel.append(new Option('All ' + (th.dataset.filter || text(th)).toLowerCase(), ''));
      values.forEach(v => sel.append(new Option(v, v)));
      sel.addEventListener('change', () => { state.filters[col] = sel.value; state.page = 1; render(); });
      const w = make('div', 'relative');
      w.prepend(sel); right.append(w);
    });
    (table.dataset.tableButtons ? document.querySelectorAll(table.dataset.tableButtons) : []).forEach(b => right.append(b));
    bar.append(left, right);

    // footer: "Showing 1 to 10 of N results" on the left, Previous / pages / Next on the right
    const foot = make('div', 'flex flex-col items-center justify-between gap-3 px-2 py-4 sm:flex-row sm:px-0');
    const info = make('p', 'text-sm text-gray-700 dark:text-gray-400');
    const nav = make('div', 'flex items-center');
    const btnCls = 'relative inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700';
    const prev = make('button', btnCls, 'Previous'); prev.type = 'button';
    const next = make('button', btnCls + ' ml-3', 'Next'); next.type = 'button';
    const pages = make('div', 'ml-3 hidden items-center gap-1 sm:flex');
    nav.append(prev, pages, next); foot.append(info, nav);
    prev.addEventListener('click', () => { state.page--; render(true); });
    next.addEventListener('click', () => { state.page++; render(true); });

    const empty = make('tr', '', '<td colspan="99" class="px-5 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No rows match.</td>');
    empty.dataset.tableSkip = '';

    if (titleWrap && titleWrap !== card && !titleWrap.contains(box) && titleWrap.textContent.trim() === '') titleWrap.replaceWith(bar);
    else box.parentNode.insertBefore(bar, box);
    box.after(foot);

    // sort
    ths.forEach((th, col) => {
      if ('nosort' in th.dataset || !text(th) || col === ths.length - 1 && /action/i.test(text(th))) return;
      th.classList.add('cursor-pointer', 'select-none'); th.insertAdjacentHTML('beforeend', icon.sort);
      th.addEventListener('click', () => { state.dir = state.sort === col ? -state.dir : 1; state.sort = col; render(); });
    });

    function render(scroll) {
      let list = groups.filter(g => (!state.q || g.text.includes(state.q)) &&
        Object.entries(state.filters).every(([col, v]) => !v || cellVal(cellOf(g, +col), 'filterValue') === v));
      if (state.sort !== null) {
        const col = state.sort;
        list = list.slice().sort((a, b) => {
          const x = cellVal(cellOf(a, col), 'sortValue'), y = cellVal(cellOf(b, col), 'sortValue');
          const nx = num(x), ny = num(y);
          return (nx !== null && ny !== null ? nx - ny : x.localeCompare(y, undefined, {numeric: true})) * state.dir || a.i - b.i;
        });
      }
      const total = list.length;
      const options = SIZES.filter((s, i) => s < total || (SIZES[i - 1] || 0) < total || i === 0);
      sizeSel.innerHTML = ''; options.forEach(s => sizeSel.append(new Option(s, s)));
      if (!options.includes(state.size)) state.size = options.filter(s => s <= state.size).pop() || options[0];
      sizeSel.value = state.size;
      sizeWrap.classList.toggle('hidden', options.length < 2);
      const last = Math.max(1, Math.ceil(total / state.size));
      state.page = Math.min(Math.max(1, state.page), last);
      const from = (state.page - 1) * state.size, shown = list.slice(from, from + state.size);

      groups.forEach(g => g.rows.forEach(r => { r.hidden = true; }));
      shown.forEach(g => { g.rows.forEach(r => { r.hidden = false; tbody.append(r); }); });
      empty.remove(); if (!total && groups.length) tbody.append(empty);

      prev.disabled = state.page <= 1; next.disabled = state.page >= last;
      pages.innerHTML = '';
      const nums = [];
      for (let p = 1; p <= last; p++) if (p === 1 || p === last || Math.abs(p - state.page) <= 1) nums.push(p);
      nums.forEach((p, i) => {
        if (i && p - nums[i - 1] > 1) pages.append(make('span', 'relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-400', '...'));
        const b = make('button', 'relative inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium ' + (p === state.page ? 'bg-brand-500 text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700'), String(p));
        b.type = 'button'; if (p === state.page) b.setAttribute('aria-current', 'page');
        b.addEventListener('click', () => { state.page = p; render(true); });
        pages.append(b);
      });
      const a1 = total ? from + 1 : 0, a2 = Math.min(from + state.size, total), extra = total !== groups.length ? ` (filtered from ${groups.length})` : '';
      counter.textContent = `Showing ${a1} to ${a2} of ${total} entries` + extra;
      info.textContent = `Showing ${a1} to ${a2} of ${total} results`;
      nav.classList.toggle('invisible', last < 2);
      foot.classList.toggle('hidden', groups.length === 0);
      sWrap.classList.toggle('hidden', groups.length === 0);
      if (scroll) bar.scrollIntoView({block: 'nearest', behavior: 'smooth'});
    }
    table.azTable = {refresh() { collect(); render(); }, render};
    render();
  }

  window.AzTable = {enhance, scan(root = document) { root.querySelectorAll('table[data-table]').forEach(enhance); }};
  const start = () => window.AzTable.scan();
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start) : start();
  // tables that arrive later (a part of the page reloaded in place, a modal filled from the server)
  document.addEventListener('az:updated', (e) => window.AzTable.scan(e.target || document));
})();
</script>
