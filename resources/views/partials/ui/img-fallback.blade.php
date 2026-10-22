{{-- A picture whose file is missing on this computer (uploads live on the server; scripts/pull-live-files.sh copies
     them here) shows a quiet placeholder instead of a broken image: initials for a person, a file mark otherwise. --}}
<script>
function azImgFallback(img) {
  if (!img || img.tagName !== 'IMG' || img.dataset.fallbackDone || img.hasAttribute('onerror') || img.closest('[data-no-fallback]')) return;
  var src = img.getAttribute('src') || '';
  if (!src || /^(data|blob):/.test(src) || img.hasAttribute(':src') || img.hasAttribute('x-bind:src')) return;   // drawn by script, not a stored file
  img.dataset.fallbackDone = '1';
  var name = (img.getAttribute('alt') || '').replace(/photo|profile|avatar|logo|selfie|picture/ig, '').trim();
  var initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w[0].toUpperCase(); }).join('');
  var w = img.width || img.clientWidth || 40, h = img.height || img.clientHeight || 40;
  var round = /rounded-full/.test(img.className);
  if (round) { var d = Math.min(w, h) || 40; w = d; h = d; }
  var box = document.createElement('span');
  box.className = img.className + ' inline-flex items-center justify-center bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 ' + (round ? 'rounded-full' : 'rounded-lg');
  box.style.width = w + 'px'; box.style.height = h + 'px'; box.style.fontSize = Math.max(10, Math.round(Math.min(w, h) / 2.6)) + 'px'; box.style.fontWeight = '600';
  box.title = 'File not on this computer';
  box.textContent = initials || '';
  if (!initials) box.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:45%;height:45%"><path d="M4 16l4.6-4.6a2 2 0 0 1 2.8 0L16 16m-2-2 1.6-1.6a2 2 0 0 1 2.8 0L20 14M4 6h16v12H4z"/></svg>';
  img.replaceWith(box);
}
document.addEventListener('error', function (e) { azImgFallback(e.target); }, true);
// pictures that already failed before this ran
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('img').forEach(function (i) { if (i.complete && i.naturalWidth === 0 && i.getAttribute('src')) azImgFallback(i); });
});
</script>
