{{-- Print a loan statement (owner, 16 Nov 2026): only the .az-statement card, portrait A4, as it looks on screen. --}}
<style>
    /* Print Statement (owner, 16 Nov 2026): only the statement card, portrait A4, as it looks on screen */
    @page { size: A4 portrait; margin: 12mm; }
    @media print {
        html, body { background: #fff !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print { display: none !important; }
        /* everything that is not the statement, not inside it and not on the way to it */
        body *:not(:has(.az-statement)):not(.az-statement):not(.az-statement *) { display: none !important; }
        /* the wrappers around it: no sidebar offsets, padding, fixed heights or scrolling */
        body *:has(.az-statement) {
            display: block !important; position: static !important; margin: 0 !important; padding: 0 !important;
            width: auto !important; max-width: none !important; height: auto !important; min-height: 0 !important;
            overflow: visible !important; border: 0 !important; box-shadow: none !important; transform: none !important; background: #fff !important;
        }
        .az-statement { width: 100% !important; max-width: none !important; margin: 0 !important; border-radius: 12px; }
        .az-statement > .p-5 { padding: 20px 24px !important; }
        .az-statement .grid > div, .az-statement .rounded-2xl, .az-statement .rounded-xl { break-inside: avoid; page-break-inside: avoid; }
        .az-statement .animate-pulse { animation: none !important; }
        .az-statement [class*="min-w-"] { min-width: 0 !important; }
        .az-statement .overflow-x-auto, .az-statement .overflow-hidden { overflow: visible !important; }
        .az-statement button, .az-statement form, .az-statement a[href*="/edit"] { display: none !important; }
    }
</style>
<script>
    // print in light mode even when the desk is dark
    (function () {
        let wasDark = false;
        window.addEventListener('beforeprint', () => { wasDark = document.body.classList.contains('dark'); document.body.classList.remove('dark'); });
        window.addEventListener('afterprint', () => { if (wasDark) document.body.classList.add('dark'); });
    })();
</script>
