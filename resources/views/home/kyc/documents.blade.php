@extends('home.layouts.kyc', ['step' => 5, 'title' => 'Identity documents'])

@php
  $upIcon = '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M6 10l6-6 6 6M4 20h16" /></svg>';
  $passport = old('id_type', $user->id_type) === 'passport';
  // a row shows as done when the file is already on record; a validation error replaces the hint
  $hint = function ($field, $onFile, $text) use ($errors) {
    if ($errors->has($field)) {
      return '<span class="u-hint" style="color:#9B2C3B">' . e($errors->first($field)) . '</span>';
    }
    return '<span class="u-hint" data-hint="' . e($text) . '">' . e($onFile ? 'Uploaded · on file' : $text) . '</span>';
  };
@endphp

@section('content')
  <div class="kyc-head">
    <h1>Identity documents</h1>
    <p class="muted">JPG, PNG or PDF, up to 10 MB each. Last step: your ID, a photo, three quick selfies and your signature.</p>
    <p class="muted paste-tip">Have it on your clipboard? Tap a row, then press <kbd>Ctrl</kbd>+<kbd>V</kbd> (<kbd>⌘</kbd>+<kbd>V</kbd> on a Mac) to paste it.</p>
  </div>
  <form class="kyc-form kyc-docs" method="POST" action="{{ route('kyc.documents.store') }}" enctype="multipart/form-data"
    data-validate data-server novalidate>
    @csrf
    @if ($lockSaved && $user->id_type)
      <input type="hidden" name="id_type" value="{{ $user->id_type }}" />
    @endif
    <input class="kyc-radio" type="radio" name="id_type" id="dt-id" value="national_id" @checked(!$passport) @disabled($lockSaved && $user->id_type) /><input
      class="kyc-radio" type="radio" name="id_type" id="dt-pp" value="passport" @checked($passport) @disabled($lockSaved && $user->id_type) />
    <div class="field">
      <span class="flabel">Which document will you use?<span class="req">*</span></span>
      <div class="kyc-seg">
        <label for="dt-id" class="l-id"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6" /></svg
          >National ID</label
        ><label for="dt-pp" class="l-pp"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          >Passport</label
        >
      </div>
    </div>
    <div class="uploads">
      <div class="upload only-id {{ $user->id_front_path && !$passport ? 'done' : '' }}">
        <span class="chip">{!! $upIcon !!}</span>
        <div class="u-text">
          <b>National ID — front<span class="req">*</span></b
          >{!! $hint('id_front', $user->id_front_path && !$passport, 'All four corners visible, no glare') !!}
        </div>
        @if ($lockSaved && $user->id_front_path && ! $passport)<span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>@else
        <label class="btn btn-ghost u-btn" for="u-front">{{ $user->id_front_path && !$passport ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-front" name="id_front" accept="image/*,application/pdf" />
        @endif
      </div>
      <div class="upload only-id {{ $user->id_back_path ? 'done' : '' }}">
        <span class="chip">{!! $upIcon !!}</span>
        <div class="u-text">
          <b>National ID — back<span class="req">*</span></b
          >{!! $hint('id_back', $user->id_back_path, 'Photo of the back of your ID') !!}
        </div>
        @if ($lockSaved && $user->id_back_path)<span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>@else
        <label class="btn btn-ghost u-btn" for="u-back">{{ $user->id_back_path ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-back" name="id_back" accept="image/*,application/pdf" />
        @endif
      </div>
      <div class="upload only-pp {{ $user->id_front_path && $passport ? 'done' : '' }}">
        <span class="chip">{!! $upIcon !!}</span>
        <div class="u-text">
          <b>Passport bio page<span class="req">*</span></b
          >{!! $hint('passport_bio', $user->id_front_path && $passport, 'The page with your photo and details') !!}
        </div>
        @if ($lockSaved && $user->id_front_path && $passport)<span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>@else
        <label class="btn btn-ghost u-btn" for="u-bio">{{ $user->id_front_path && $passport ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-bio" name="passport_bio" accept="image/*,application/pdf" />
        @endif
      </div>
      <div class="upload {{ $user->avatar ? 'done' : '' }}">
        <span class="chip"
          ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 8h3l2-3h6l2 3h3v11H4zM12 17a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></svg
        ></span>
        <div class="u-text">
          <b>Passport-size photo<span class="req">*</span></b
          >{!! $hint('photo', $user->avatar, 'Recent, plain light background') !!}
        </div>
        @if ($lockSaved && $user->avatar)<span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>@else
        <label class="btn btn-ghost u-btn" for="u-photo">{{ $user->avatar ? 'Replace' : 'Upload' }}</label
        ><input class="u-input" type="file" id="u-photo" name="photo" accept="image/*" />
        @endif
      </div>
      {{-- three selfies: straight, left, right (front camera on phones) --}}
      @foreach ([
        'selfie_center' => ['selfie_center_path', 'Selfie: looking straight', 'Face the camera, eyes open, no hat or sunglasses'],
        'selfie_left' => ['selfie_left_path', 'Selfie: head turned left', 'Turn your head to your left so we see the side of your face'],
        'selfie_right' => ['selfie_right_path', 'Selfie: head turned right', 'Turn your head to your right so we see the other side'],
      ] as $field => [$column, $label, $text])
        <div class="upload {{ $user->{$column} ? 'done' : '' }}">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21c1-4 4-6 8-6s7 2 8 6" /></svg
          ></span>
          <div class="u-text">
            <b>{{ $label }}<span class="req">*</span></b
            >{!! $hint($field, $user->{$column}, $text) !!}
          </div>
          @if ($lockSaved && $user->{$column})<span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>@else
          <label class="btn btn-ghost u-btn" for="u-{{ $field }}">{{ $user->{$column} ? 'Retake' : 'Take' }}</label
          ><input class="u-input" type="file" id="u-{{ $field }}" name="{{ $field }}" accept="image/*" capture="user" />
          @endif
        </div>
      @endforeach
      <div class="upload sign {{ $user->signature ? 'done' : '' }}">
        <div class="sig-head">
          <span class="chip"
            ><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg
          ></span>
          <div class="u-text">
            <b>Signature<span class="req">*</span></b
            >@if ($errors->has('signature'))<span class="u-hint" style="color:#9B2C3B">{{ $errors->first('signature') }}</span
            >@else<span class="u-hint">{{ $user->signature ? 'On file. Sign again or upload to replace it.' : 'Sign below, or upload a photo of your signature.' }}</span
            >@endif
          </div>
          @if ($lockSaved && $user->signature)<span class="saved-tag"><svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8 11V8a4 4 0 0 1 8 0v3" /></svg>Saved</span>@else
          <label class="btn btn-ghost u-btn" for="u-sign">Upload</label
          ><input class="u-input" type="file" id="u-sign" name="signature_file" accept="image/*" />
          @endif
        </div>
        @unless ($lockSaved && $user->signature)
        <div class="sig-pad">
          <canvas aria-label="Signature pad: draw your signature" data-sign></canvas
          ><span class="sig-line"></span><button type="button" class="sig-clear" data-sign-clear>Clear</button>
        </div>
        @endunless
        <input type="hidden" name="signature_data" data-sign-out />
      </div>
    </div>
    <p class="quality-warning" role="alert" hidden data-quality-warning></p>
    <div class="kyc-nav">
      <a href="{{ route('kyc.contacts') }}">Back</a><button class="btn btn-primary" type="submit">Continue</button>
    </div>
  </form>

  <script>
    // Paste (Ctrl+V / ⌘V) an image into the selected row, and warn about blurry or dark photos before upload.
    (function () {
      var form = document.querySelector('.kyc-docs');
      if (!form) return;
      var rows = Array.prototype.slice.call(form.querySelectorAll('.upload')).filter(function (row) {
        return row.querySelector('.u-input');
      });
      var active = null;

      function visible(row) { return row.offsetParent !== null; }
      function select(row) {
        rows.forEach(function (r) { r.classList.toggle('paste-target', r === row); });
        active = row;
      }
      rows.forEach(function (row) {
        row.tabIndex = 0;
        row.addEventListener('pointerdown', function () { select(row); });
        row.addEventListener('focusin', function () { select(row); });
      });

      // the chosen row, or the first visible one still waiting for a file (not the signature)
      function target() {
        if (active && visible(active)) return active;
        return rows.filter(function (r) { return visible(r) && !r.classList.contains('done') && !r.classList.contains('sign'); })[0] || null;
      }

      document.addEventListener('paste', function (e) {
        var items = (e.clipboardData && e.clipboardData.items) || [];
        var file = null;
        for (var i = 0; i < items.length; i++) {
          if (items[i].kind === 'file' && /^image\//.test(items[i].type)) { file = items[i].getAsFile(); break; }
        }
        if (!file) return;
        var row = target();
        if (!row) return;
        e.preventDefault();
        var input = row.querySelector('.u-input');
        var ext = (file.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
        var named = new File([file], 'pasted-' + input.name + '.' + ext, { type: file.type });
        var dt = new DataTransfer();
        dt.items.add(named);
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        select(row);
      });

      // ---- photo quality. Sharpness = edge strength of the strongest 0.5% of pixels (|Laplacian|, on a
      // greyscale copy at most 1000px wide): blur flattens edges, and unlike the overall variance it
      // doesn't drop for plain backgrounds. Tested: sharp ID ~210, slightly soft ~80, unreadable < 40.
      var MIN_SHARPNESS = { id_front: 40, id_back: 40, passport_bio: 40, photo: 25, selfie_center: 25, selfie_left: 25, selfie_right: 25 };
      function quality(file, done) {
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () {
          var scale = Math.min(1, 1000 / Math.max(img.naturalWidth, img.naturalHeight));
          var w = Math.max(3, Math.round(img.naturalWidth * scale)), h = Math.max(3, Math.round(img.naturalHeight * scale));
          var c = document.createElement('canvas');
          c.width = w; c.height = h;
          var ctx = c.getContext('2d');
          ctx.drawImage(img, 0, 0, w, h);
          var px = ctx.getImageData(0, 0, w, h).data, g = new Float32Array(w * h), light = 0;
          for (var i = 0, j = 0; i < px.length; i += 4, j++) {
            g[j] = 0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2];
            light += g[j];
          }
          var edges = new Float32Array((w - 2) * (h - 2)), n = 0;
          for (var y = 1; y < h - 1; y++) {
            for (var x = 1; x < w - 1; x++) {
              var k = y * w + x;
              edges[n++] = Math.abs(g[k - w] + g[k + w] + g[k - 1] + g[k + 1] - 4 * g[k]);
            }
          }
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
          if (!f || !/^image\//.test(f.type) || !(input.name in MIN_SHARPNESS)) return;
          quality(f, function (q) {
            if (!q || input.files[0] !== f) return;
            var problem = null;
            if (Math.min(q.width, q.height) < 500) problem = 'This photo is very small. Use the camera at full size.';
            else if (q.brightness < 45) problem = 'This photo is too dark. Retake it in good light.';
            else if (q.sharpness < MIN_SHARPNESS[input.name]) problem = 'This photo looks blurry. Hold the phone steady, tap to focus and retake it.';
            if (problem) {
              row.classList.add('blurry');
              input.dataset.problem = problem;
              if (hint) { hint.textContent = problem; hint.style.color = '#9B2C3B'; }
            }
          });
        });
      });

      // a flagged photo can still be sent, but only after a second press
      var warned = false;
      form.addEventListener('submit', function (e) {
        var flagged = rows.filter(function (r) { return visible(r) && r.classList.contains('blurry'); });
        if (flagged.length && !warned) {
          e.preventDefault();
          e.stopImmediatePropagation();
          warned = true;
          flagged[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
          flagged[0].focus({ preventScroll: true });
          var note = form.querySelector('[data-quality-warning]');
          note.textContent = flagged[0].querySelector('.u-input').dataset.problem + ' Unclear photos slow down your approval. Press Continue again to send it anyway.';
          note.hidden = false;
        }
      }, true);
    })();
  </script>
@endsection
