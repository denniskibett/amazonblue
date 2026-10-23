/*
 * Date pickers for the whole app (owner, 23 Oct 2026). Every date field uses flatpickr, set up from the markup:
 *   <input type="date">                    one date        (sent as 2026-10-23)
 *   <input type="datetime-local">          date and time   (sent as 2026-10-23T14:05)
 *   <input type="time">                    a time          (sent as 14:05)
 *   <input type="date" data-range-end="to"> two dates: picks a range; the start goes in this field and the end in the
 *                                          field named "to" in the same form, which is then hidden
 *   data-date="date|datetime|time|range"   the same on a text field; data-no-picker leaves a field alone
 * min / max / required carry over. Picking a date fires "input" and "change" on the real field, so Alpine (x-model),
 * live previews and forms see it; setting the field's value from code (x-model, a reset) updates the picker too.
 * Fields added later (modals, the side drawer, background refreshes) are picked up by themselves.
 * flatpickr comes from the app bundle on staff pages; elsewhere it is loaded only when a page has a date field.
 */
(function () {
  if (window.__azDates) return;
  window.__azDates = true;
  var SEL = 'input[type=date], input[type=datetime-local], input[type=time], input[data-date]';
  var CDN = 'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/';
  var loading = null;

  function load() {
    if (window.flatpickr) return Promise.resolve();
    if (loading) return loading;
    var css = document.createElement('link');
    css.rel = 'stylesheet'; css.href = CDN + 'flatpickr.min.css';
    document.head.appendChild(css);
    loading = new Promise(function (ok) {
      var s = document.createElement('script');
      s.src = CDN + 'flatpickr.min.js'; s.onload = ok; s.onerror = ok;
      document.head.appendChild(s);
    });
    return loading;
  }

  function fire(el) {
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  // keep the picker in step when code sets the field's value (x-model, form resets)
  var native = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
  function watchValue(el, sync) {
    var busy = false;
    try {
      Object.defineProperty(el, 'value', {
        configurable: true,
        get: function () { return native.get.call(this); },
        set: function (v) {
          native.set.call(this, v);
          if (!busy && el._azPicker) { busy = true; try { sync(v); } finally { busy = false; } }
        },
      });
    } catch (e) { /* old browsers: the picker just won't follow code changes */ }
  }

  function kind(el) {
    if (el.dataset.date) return el.dataset.date;
    if (el.dataset.rangeEnd) return 'range';
    return { 'datetime-local': 'datetime', time: 'time' }[el.type] || 'date';
  }

  function init(el) {
    if (el._flatpickr || el.dataset.noPicker !== undefined || el.dataset.picked) return;
    el.dataset.picked = '1';
    var k = kind(el);
    var form = el.form || el.closest('form') || document;
    var end = k === 'range' && el.dataset.rangeEnd ? form.querySelector('[name="' + el.dataset.rangeEnd + '"]') : null;
    // a range is picked in a field of its own; the real start and end fields only hold the dates
    var target = el;
    if (k === 'range') {
      target = document.createElement('input');
      target.type = 'text';
      target.className = el.className;
      target.placeholder = el.placeholder || 'From – to';
      el.parentNode.insertBefore(target, el);
      el.type = 'hidden';
      if (end) {
        end.type = 'hidden'; end.dataset.noPicker = '';
        // the end field's own box (its label and wrapper) goes: the range field shows both dates
        var box = end.closest('[data-range-box]') || end.closest('.az-field') || end.closest('label');
        if (box) box.hidden = true; else { var lbl = end.id && document.querySelector('label[for="' + end.id + '"]'); if (lbl) lbl.hidden = true; }
      }
    } else if (el.type !== 'text' && el.type !== 'hidden') {
      try { el.type = 'text'; } catch (e) {}
    }
    var cfg = {
      allowInput: true, disableMobile: true, altInput: true, monthSelectorType: 'static',
      minDate: el.min || null, maxDate: el.max || null,
      dateFormat: 'Y-m-d', altFormat: 'j M Y',
      onReady: function (d, s, fp) {
        if (fp.altInput) {
          fp.altInput.required = el.required;
          fp.altInput.placeholder = el.placeholder || (k === 'range' ? 'From – to' : (k === 'time' ? 'Time' : 'Pick a date'));
          fp.altInput.disabled = el.disabled;
          fp.altInput.classList.add('az-date');
        }
        fp.calendarContainer.classList.add('az-cal');
      },
      onChange: function (dates, str, fp) {
        if (k === 'range') {
          if (dates.length === 1) return;                                  // wait for the second date
          native.set.call(el, dates[0] ? fp.formatDate(dates[0], 'Y-m-d') : '');
          if (end) { native.set.call(end, dates[1] ? fp.formatDate(dates[1], 'Y-m-d') : ''); fire(end); }
        }
        fire(el);
      },
    };
    if (k === 'datetime') Object.assign(cfg, { enableTime: true, time_24hr: true, dateFormat: 'Y-m-d\\TH:i', altFormat: 'j M Y, H:i' });
    if (k === 'time') Object.assign(cfg, { enableTime: true, noCalendar: true, time_24hr: true, dateFormat: 'H:i', altFormat: 'H:i' });
    if (k === 'range') { cfg.mode = 'range'; cfg.altFormat = 'j M y'; cfg.locale = { rangeSeparator: ' – ' }; cfg.defaultDate = [el.value, end && end.value].filter(Boolean); }
    else cfg.defaultDate = el.value || null;

    var fp = window.flatpickr(target, cfg);
    if (!fp || !fp.config) return;
    el._azPicker = fp;
    // code that sets the real field (x-model, a reset) moves the picker; the picker's own writes are skipped
    var same = function (v) { return fp.selectedDates.map(function (d) { return fp.formatDate(d, fp.config.dateFormat); }).join(' to ') === (v || ''); };
    if (k === 'range') {
      var both = function () { fp.setDate([el.value, end && end.value].filter(Boolean), false); };
      watchValue(el, both);
      if (end) watchValue(end, both);
    } else {
      watchValue(el, function (v) { if (!same(v)) fp.setDate(v || null, false); });
    }
    // disabled / required changes made by Alpine follow onto the visible field
    if (fp.altInput && window.MutationObserver) {
      new MutationObserver(function () { fp.altInput.disabled = el.disabled; fp.altInput.required = el.required; })
        .observe(el, { attributes: true, attributeFilter: ['disabled', 'required'] });
    }
  }

  function scan(root) {
    if (!root || !root.querySelectorAll) return;
    var list = Array.prototype.slice.call(root.querySelectorAll(SEL));
    if (root.matches && root.matches(SEL)) list.push(root);
    list = list.filter(function (el) { return !el._flatpickr && !el._azPicker && !el.dataset.picked && el.dataset.noPicker === undefined && !el.classList.contains('flatpickr-input'); });
    if (!list.length) return;
    load().then(function () { if (window.flatpickr) list.forEach(init); });
  }

  var pending = [];
  function queue(node) {
    pending.push(node);
    if (pending.length > 1) return;
    (window.requestAnimationFrame || setTimeout)(function () { var n = pending; pending = []; n.forEach(scan); });
  }

  function start() {
    scan(document);
    if (window.MutationObserver) {
      new MutationObserver(function (records) {
        records.forEach(function (r) { r.addedNodes.forEach(function (n) { if (n.nodeType === 1) queue(n); }); });
      }).observe(document.body, { childList: true, subtree: true });
    }
  }
  // a softer calendar that follows the brand colour and dark mode
  function theme() {
    if (document.getElementById('az-cal-css')) return;
    var st = document.createElement('style');
    st.id = 'az-cal-css';
    st.textContent = [
      '.az-cal{--c:var(--primary-color,#465fff);border:0!important;border-radius:16px!important;box-shadow:0 18px 40px -12px rgba(16,24,40,.28),0 0 0 1px rgba(16,24,40,.06)!important;padding:6px!important;font-family:inherit}',
      '.az-cal:before,.az-cal:after{display:none!important}',
      '.az-cal .flatpickr-months{padding:4px 4px 0}.az-cal .flatpickr-current-month{font-size:14px;font-weight:600}',
      '.az-cal .flatpickr-weekday{font-size:11px;font-weight:600;color:#98a2b3}',
      '.az-cal .flatpickr-day{border-radius:10px;font-size:13px}',
      '.az-cal .flatpickr-day.today{border-color:transparent;background:rgba(70,95,255,.08);color:var(--c)}',
      '.az-cal .flatpickr-day.selected,.az-cal .flatpickr-day.startRange,.az-cal .flatpickr-day.endRange{background:var(--c)!important;border-color:var(--c)!important;color:#fff!important}',
      '.az-cal .flatpickr-day.inRange{background:rgba(70,95,255,.1)!important;border-color:transparent!important;box-shadow:none!important}',
      '.az-cal .flatpickr-day:hover{background:#f2f4f7;border-color:#f2f4f7}',
      '.az-cal .flatpickr-time input:hover,.az-cal .flatpickr-time .flatpickr-am-pm:hover{background:#f2f4f7}',
      '.dark .az-cal{background:#1d2939;box-shadow:0 18px 40px -12px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.06)!important}',
      '.dark .az-cal .flatpickr-month,.dark .az-cal .flatpickr-current-month .flatpickr-monthDropdown-months,.dark .az-cal .flatpickr-weekday,.dark .az-cal .flatpickr-day,.dark .az-cal .flatpickr-time input,.dark .az-cal span.flatpickr-weekday{color:#e4e7ec;fill:#e4e7ec;background:transparent}',
      '.dark .az-cal .flatpickr-day.prevMonthDay,.dark .az-cal .flatpickr-day.nextMonthDay,.dark .az-cal .flatpickr-day.flatpickr-disabled{color:#667085}',
      '.dark .az-cal .flatpickr-day:hover{background:#344054;border-color:#344054}',
      'input.az-date{cursor:pointer}'
    ].join('');
    document.head.appendChild(st);
  }
  theme();
  window.azDates = { scan: scan };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
