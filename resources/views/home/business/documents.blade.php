@extends('home.business.layout', ['step' => 3, 'title' => 'Company documents'])

@php
  $upIcon = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M6 10l6-6 6 6M4 20h16" /></svg>';
  $lock = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>';
  $locked = ! $business->isEditable();
  // CR12 only matters for limited companies; the rest are optional extras
  $types = collect(\App\Models\BusinessDocument::TYPES)->reject(fn ($t, $key) => $key === 'cr12' && ! $business->isLimitedCompany());
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Company documents</h1>
    <p class="muted">JPG, PNG or PDF, up to 10 MB each. Only you and our verification team can open them.</p>
    @unless ($locked)
      <p class="muted paste-tip">Have it on your clipboard? Tap a row, then press <kbd>Ctrl</kbd>+<kbd>V</kbd> (<kbd>⌘</kbd>+<kbd>V</kbd> on a Mac) to paste it.</p>
    @endunless
  </div>
  <form class="kyc-form kyc-docs biz-docs" method="POST" action="{{ route('account.business.documents.store', $business) }}" enctype="multipart/form-data" data-server novalidate>
    @csrf
    <div class="uploads">
      @foreach ($types as $type => [$label, $hint])
        @php
          $doc = $documents[$type] ?? null;
          $isRequired = in_array($type, $required, true);
          $err = $errors->first("docs.$type");
        @endphp
        <div class="upload {{ $doc ? 'done' : '' }}">
          <span class="chip">{!! $upIcon !!}</span>
          <div class="u-text">
            <b>{{ $label }}@if ($isRequired)<span class="req">*</span>@else <span class="muted biz-opt">optional</span>@endif</b>
            @if ($err)
              <span class="u-hint" style="color:#9B2C3B">{{ $err }}</span>
            @elseif ($doc)
              <span class="u-hint">On file · <a href="{{ $doc->url() }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($doc->original_name ?: 'view', 32) }}</a></span>
            @else
              <span class="u-hint">{{ $hint }}</span>
            @endif
          </div>
          @if ($locked)
            @if ($doc)<span class="saved-tag">{!! $lock !!}Saved</span>@endif
          @else
            <label class="btn btn-ghost u-btn" for="d-{{ $type }}">{{ $doc ? 'Replace' : 'Upload' }}</label
            ><input class="u-input" type="file" id="d-{{ $type }}" name="docs[{{ $type }}]" accept="image/*,application/pdf" data-min-sharpness="40" />
          @endif
        </div>
      @endforeach
    </div>
    <p class="quality-warning" role="alert" hidden data-quality-warning></p>
    <div class="kyc-nav">
      <a href="{{ route('account.business.members', $business) }}">Back</a>
      @if ($locked)
        <a class="btn btn-primary" href="{{ route('account.business.financials', $business) }}">Next</a>
      @else
        <button class="btn btn-primary" type="submit">Continue</button>
      @endif
    </div>
  </form>

  @unless ($locked)
  <script>
    // Same as the individual KYC documents step: paste (Ctrl+V / ⌘V) an image into the selected row, and warn
    // about blurry, dark or tiny photos before upload (press Continue again to send anyway).
    (function () {
      var form = document.querySelector('.biz-docs');
      if (!form) return;
      var rows = Array.prototype.slice.call(form.querySelectorAll('.upload')).filter(function (row) { return row.querySelector('.u-input'); });
      var active = null;
      function select(row) { rows.forEach(function (r) { r.classList.toggle('paste-target', r === row); }); active = row; }
      rows.forEach(function (row) {
        row.tabIndex = 0;
        row.addEventListener('pointerdown', function () { select(row); });
        row.addEventListener('focusin', function () { select(row); });
      });
      function target() { return active || rows.filter(function (r) { return !r.classList.contains('done'); })[0] || null; }

      document.addEventListener('paste', function (e) {
        var items = (e.clipboardData && e.clipboardData.items) || [], file = null;
        for (var i = 0; i < items.length; i++) {
          if (items[i].kind === 'file' && /^image\//.test(items[i].type)) { file = items[i].getAsFile(); break; }
        }
        var row = file && target();
        if (!row) return;
        e.preventDefault();
        var input = row.querySelector('.u-input');
        var ext = (file.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
        var dt = new DataTransfer();
        dt.items.add(new File([file], 'pasted-' + input.id + '.' + ext, { type: file.type }));
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        select(row);
      });

      // sharpness = edge strength of the strongest 0.5% of pixels (|Laplacian| on a greyscale copy, max 1000px wide)
      function quality(file, done) {
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () {
          var scale = Math.min(1, 1000 / Math.max(img.naturalWidth, img.naturalHeight));
          var w = Math.max(3, Math.round(img.naturalWidth * scale)), h = Math.max(3, Math.round(img.naturalHeight * scale));
          var c = document.createElement('canvas'); c.width = w; c.height = h;
          var ctx = c.getContext('2d'); ctx.drawImage(img, 0, 0, w, h);
          var px = ctx.getImageData(0, 0, w, h).data, g = new Float32Array(w * h), light = 0;
          for (var i = 0, j = 0; i < px.length; i += 4, j++) { g[j] = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]; light += g[j]; }
          var edges = new Float32Array((w - 2) * (h - 2)), n = 0;
          for (var y = 1; y < h - 1; y++) for (var x = 1; x < w - 1; x++) { var k = y * w + x; edges[n++] = Math.abs(g[k - w] + g[k + w] + g[k - 1] + g[k + 1] - 4 * g[k]); }
          edges.sort();
          URL.revokeObjectURL(url);
          done({ sharpness: edges[Math.floor(n * 0.995)], brightness: light / (w * h), width: img.naturalWidth, height: img.naturalHeight });
        };
        img.onerror = function () { URL.revokeObjectURL(url); done(null); };
        img.src = url;
      }
      rows.forEach(function (row) {
        var input = row.querySelector('.u-input'), hint = row.querySelector('.u-hint');
        input.addEventListener('change', function () {
          var f = input.files && input.files[0];
          row.classList.remove('blurry');
          delete input.dataset.problem;
          if (!f || !/^image\//.test(f.type)) return;
          quality(f, function (q) {
            if (!q || input.files[0] !== f) return;
            var problem = null;
            if (Math.min(q.width, q.height) < 500) problem = 'This photo is very small. Scan it, or photograph it at full size.';
            else if (q.brightness < 45) problem = 'This photo is too dark. Retake it in good light.';
            else if (q.sharpness < Number(input.dataset.minSharpness || 40)) problem = 'This photo looks blurry. Hold the phone steady, tap to focus and retake it.';
            if (problem) { row.classList.add('blurry'); input.dataset.problem = problem; if (hint) { hint.textContent = problem; hint.style.color = '#9B2C3B'; } }
          });
        });
      });
      var warned = false;
      form.addEventListener('submit', function (e) {
        var flagged = rows.filter(function (r) { return r.classList.contains('blurry'); });
        if (flagged.length && !warned) {
          e.preventDefault(); e.stopImmediatePropagation(); warned = true;
          flagged[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
          var note = form.querySelector('[data-quality-warning]');
          note.textContent = flagged[0].querySelector('.u-input').dataset.problem + ' Unclear documents slow down your approval. Press Continue again to send it anyway.';
          note.hidden = false;
        }
      }, true);
    })();
  </script>
  @endunless
@endsection
