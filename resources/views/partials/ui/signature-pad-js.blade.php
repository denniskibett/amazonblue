{{-- The signature pad (owner, 25 Oct 2026): the original library from jsDelivr, which writes like a pen, and the app's own
     copy only if the CDN can't be reached, so signing never breaks. Include once on any page with a signature canvas. --}}
@once
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>window.SignaturePad || document.write('<script src="{{ asset('assets/js/signature-pad.js') }}?v={{ @filemtime(public_path('assets/js/signature-pad.js')) }}"><\/script>');</script>
@endonce
