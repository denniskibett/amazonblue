{{-- Sysadmin "view as" bar (owner, 23 Oct 2026): shown on every page while previewing. Exit runs as the sysadmin. --}}
@if (app()->bound('az.preview'))
  @php $pv = app('az.preview'); @endphp
  <div style="position:sticky;top:0;z-index:100000;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:8px 14px;padding:8px 14px;background:#1B2B6B;color:#fff;font:500 13px/1.4 system-ui,-apple-system,'Segoe UI',sans-serif" role="status">
    <span>Previewing as <b>{{ $pv['label'] }}</b>. Nothing you do is saved or sent.</span>
    <a href="{{ route('preview.index') }}" style="color:#C9D6F0;text-decoration:underline">Switch</a>
    <form method="POST" action="{{ route('preview.stop') }}" style="margin:0" data-no-swap>@csrf<button type="submit" style="background:#fff;color:#1B2B6B;border:0;border-radius:999px;padding:4px 12px;font:600 12px system-ui,sans-serif;cursor:pointer">Exit preview</button></form>
  </div>
  @if (session('az_preview_note'))
    <script>document.addEventListener('alpine:initialized', () => window.toast && window.toast(@js(session('az_preview_note')), 'info'));</script>
  @endif
@endif
