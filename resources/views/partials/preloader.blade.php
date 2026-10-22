{{-- Page loader (owner, 22 Oct 2026): plain CSS and a few lines of script, so it never depends on Alpine starting.
     It fades out as soon as the page is ready, is skipped when the page comes back from the browser cache, and can
     never stay up longer than 2.5 seconds. --}}
<div id="page-loader" class="fixed inset-0 z-999999 flex items-center justify-center bg-white transition-opacity duration-200 dark:bg-gray-900">
  <div class="h-12 w-12 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
</div>
<script>
  (function () {
    var el = document.getElementById('page-loader');
    var hide = function () { if (!el) return; el.style.opacity = '0'; el.style.pointerEvents = 'none'; setTimeout(function () { el && el.remove(); el = null; }, 220); };
    if (document.readyState !== 'loading') hide(); else document.addEventListener('DOMContentLoaded', hide);
    window.addEventListener('pageshow', hide);
    setTimeout(hide, 2500);
  })();
</script>
