{{-- Alert dialog in the website style, opened for a flashed alert: ->with('alert', ['type' => success|error|warning|info, 'title', 'message']) --}}
@if ($alert = session('alert'))
  @php
    $tone = ['success' => 'sage', 'error' => 'rose', 'warning' => 'sand', 'info' => 'cloud'][$alert['type'] ?? 'info'] ?? 'cloud';
    $path = [
      'success' => 'M5 12l4 4 10-10',
      'error' => 'M6 6l12 12M18 6L6 18',
      'warning' => 'M12 8v5M12 16h.01M12 3l10 18H2z',
      'info' => 'M12 11v6M12 7h.01',
    ][$alert['type'] ?? 'info'] ?? 'M12 11v6M12 7h.01';
  @endphp
  <dialog class="modal" id="alert-dialog" aria-labelledby="alert-dialog-title">
    <span class="chip big {{ $tone }}"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path }}" /></svg></span>
    <h2 id="alert-dialog-title">{{ $alert['title'] ?? '' }}</h2>
    <p class="muted">{{ $alert['message'] ?? '' }}</p>
    <form method="dialog" style="margin-top:20px"><button class="btn btn-primary btn-block" autofocus>OK</button></form>
  </dialog>
  <script>
    (function () {
      var d = document.getElementById('alert-dialog');
      if (d && d.showModal) d.showModal();
      d.addEventListener('click', function (e) {
        if (e.target === d) d.close();
      });
    })();
  </script>
@endif
