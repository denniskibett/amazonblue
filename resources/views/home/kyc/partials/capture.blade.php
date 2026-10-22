{{-- KYC helpers for borrower and business onboarding (owner, 22 Oct 2026). Included once by the KYC and business layouts.

     1. Photos are shrunk in the browser before upload (longest side 1600px, JPEG). Phone pictures are 3-8 MB; the
        server takes about 2 MB per file, which is why selfies "were not taking". PDFs are sent as they are.
     2. Everything typed or chosen is kept in this browser until the server accepts the step: text in localStorage,
        files in IndexedDB. A failed send, a lost connection or a closed tab never loses the work; once the step is
        saved (the next step opens) the copy here is deleted.
     3. Selfies use a guided camera: the front camera opens with an oval, the person looks straight, then turns left,
        then right, and the picture is taken by itself once the head is at the right angle (face landmarks from
        MediaPipe, loaded only when the camera opens). Without camera access it falls back to the phone's own camera. --}}
@php $draftOwner = auth()->id() ?: 'guest'; @endphp
<style>
  .az-cam{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;background:rgba(10,15,30,.86);padding:16px}
  .az-cam[hidden]{display:none}
  .az-cam-box{width:100%;max-width:420px;background:#fff;border-radius:20px;overflow:hidden;font-family:inherit;color:#1b2b6b}
  .az-cam-view{position:relative;aspect-ratio:3/4;background:#0b1020}
  .az-cam-view video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
  .az-cam-oval{position:absolute;left:50%;top:46%;width:62%;height:66%;transform:translate(-50%,-50%);border:3px dashed rgba(255,255,255,.75);border-radius:50%;transition:border-color .2s}
  .az-cam-oval.ok{border:3px solid #12b76a}
  .az-cam-arrow{position:absolute;top:44%;font-size:42px;color:#fff;opacity:.9;transition:opacity .2s}
  .az-cam-msg{padding:14px 18px 6px;font-weight:600;font-size:16px;text-align:center}
  .az-cam-sub{padding:0 18px 10px;font-size:13px;text-align:center;color:#5b6478}
  .az-cam-steps{display:flex;justify-content:center;gap:8px;padding-bottom:12px}
  .az-cam-steps span{width:34px;height:6px;border-radius:6px;background:#e4e7ec}
  .az-cam-steps span.on{background:#2b4fd8}.az-cam-steps span.done{background:#12b76a}
  .az-cam-actions{display:flex;gap:10px;padding:0 18px 18px}
  .az-cam-actions button{flex:1;height:44px;border-radius:12px;border:1px solid #d0d5dd;background:#fff;font-weight:600;color:#344054;cursor:pointer}
  .az-cam-actions button.primary{background:#2b4fd8;border-color:#2b4fd8;color:#fff}
  .az-cam-actions button[hidden]{display:none}
  .az-draft-note{margin:0 0 12px;padding:10px 14px;border-radius:12px;background:#eef2ff;color:#1b2b6b;font-size:13px}
  .az-busy{opacity:.6;pointer-events:none}
</style>

<div class="az-cam" hidden data-az-cam role="dialog" aria-modal="true" aria-label="Take selfies">
  <div class="az-cam-box">
    <div class="az-cam-view">
      <video playsinline muted autoplay></video>
      <div class="az-cam-oval" data-oval></div>
      <div class="az-cam-arrow" data-arrow style="left:8%"></div>
    </div>
    <div class="az-cam-steps" data-steps><span></span><span></span><span></span></div>
    <div class="az-cam-msg" data-az-msg>Starting the camera…</div>
    <div class="az-cam-sub" data-az-sub>Hold the phone at eye level in good light.</div>
    <div class="az-cam-actions">
      <button type="button" data-cam-close>Cancel</button>
      <button type="button" class="primary" data-cam-snap hidden>Take photo</button>
    </div>
  </div>
</div>

<script>
(function () {
  var OWNER = @json((string) $draftOwner);
  var KEY = 'az-kyc:' + OWNER + ':' + location.pathname;
  var PENDING = 'az-kyc-pending:' + OWNER;

  // ------------------------------------------------------------------ IndexedDB for files
  function db() {
    return new Promise(function (ok, no) {
      if (!window.indexedDB) return no('no idb');
      var r = indexedDB.open('az-kyc', 1);
      r.onupgradeneeded = function () { r.result.createObjectStore('files'); };
      r.onsuccess = function () { ok(r.result); };
      r.onerror = function () { no(r.error); };
    });
  }
  function idb(mode, fn) { return db().then(function (d) { return new Promise(function (ok, no) { var t = d.transaction('files', mode); var s = t.objectStore('files'); var out = fn(s); t.oncomplete = function () { ok(out && out.result); }; t.onerror = function () { no(t.error); }; }); }); }
  var files = {
    put: function (k, v) { return idb('readwrite', function (s) { return s.put(v, k); }).catch(function () {}); },
    get: function (k) { return idb('readonly', function (s) { return s.get(k); }).catch(function () { return null; }); },
    clear: function (prefix) { return idb('readwrite', function (s) { var r = s.openCursor(); r.onsuccess = function () { var c = r.result; if (!c) return; if (String(c.key).indexOf(prefix) === 0) c.delete(); c.continue(); }; }).catch(function () {}); },
  };
  function ls(k, v) { try { if (v === undefined) return JSON.parse(localStorage.getItem(k) || 'null'); if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, JSON.stringify(v)); } catch (e) { return null; } }

  // a step that was sent and then left behind (the next step opened) was saved: forget its copy here
  var pending = ls(PENDING);
  var hasErrors = !!document.querySelector('.field-error, .u-hint[style*="9B2C3B"], [data-server-errors], .alert-error, .errors li');
  if (pending && pending.path && (pending.path !== location.pathname || !hasErrors) && Date.now() - pending.at < 864e5) {
    if (pending.path !== location.pathname) { ls('az-kyc:' + OWNER + ':' + pending.path, null); files.clear('az-kyc:' + OWNER + ':' + pending.path); }
    ls(PENDING, null);
  }

  // ------------------------------------------------------------------ shrink photos before upload
  var MAX = 1600, QUALITY = 0.82;
  function shrink(file) {
    return new Promise(function (ok) {
      if (!file || !/^image\/(jpe?g|png|webp|heic|heif)/i.test(file.type) || file.size < 450 * 1024) return ok(file);
      var url = URL.createObjectURL(file), img = new Image();
      img.onload = function () {
        var s = Math.min(1, MAX / Math.max(img.naturalWidth, img.naturalHeight));
        var c = document.createElement('canvas'); c.width = Math.round(img.naturalWidth * s); c.height = Math.round(img.naturalHeight * s);
        var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(function (b) { ok(b && b.size < file.size ? new File([b], file.name.replace(/\.\w+$/, '') + '.jpg', {type: 'image/jpeg', lastModified: Date.now()}) : file); }, 'image/jpeg', QUALITY);
      };
      img.onerror = function () { URL.revokeObjectURL(url); ok(file); };
      img.src = url;
    });
  }
  function setFile(input, file) { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; }

  // ------------------------------------------------------------------ the forms on this step
  var forms = Array.prototype.slice.call(document.querySelectorAll('form[method="POST"], form[method="post"]')).filter(function (f) {
    return !/logout/.test(f.action) && f.querySelector('input:not([type=hidden]), textarea, select');
  });
  if (!forms.length) return;

  var saved = ls(KEY) || {};
  var restored = 0;
  forms.forEach(function (form, fi) {
    var fields = form.querySelectorAll('input, textarea, select');
    // text: fill only what the page left empty (what the server sent back always wins)
    Array.prototype.forEach.call(fields, function (el) {
      if (!el.name || el.type === 'file' || el.type === 'hidden' || el.type === 'password' || el.name === '_token') return;
      var k = fi + ':' + el.name + (el.type === 'radio' || el.type === 'checkbox' ? ':' + el.value : '');
      if (!(k in saved)) return;
      if (el.type === 'radio' || el.type === 'checkbox') { if (saved[k] && !el.checked && !el.disabled) { el.checked = true; el.dispatchEvent(new Event('change', {bubbles: true})); restored++; } }
      else if (!el.value && saved[k]) { el.value = saved[k]; el.dispatchEvent(new Event('input', {bubbles: true})); restored++; }
    });
    var save = function () {
      Array.prototype.forEach.call(form.querySelectorAll('input, textarea, select'), function (el) {
        if (!el.name || el.type === 'file' || el.type === 'hidden' || el.type === 'password' || el.name === '_token') return;
        var k = fi + ':' + el.name + (el.type === 'radio' || el.type === 'checkbox' ? ':' + el.value : '');
        saved[k] = (el.type === 'radio' || el.type === 'checkbox') ? el.checked : el.value;
      });
      ls(KEY, saved);
    };
    form.addEventListener('input', save);
    form.addEventListener('change', save);

    // files: shrink, keep a copy, and put back a copy after a failed send
    Array.prototype.forEach.call(form.querySelectorAll('input[type=file]'), function (input) {
      var fkey = KEY + ':' + fi + ':' + input.name;
      files.get(fkey).then(function (f) {
        if (f && f.name && !(input.files && input.files.length) && !input.disabled) {
          setFile(input, new File([f.blob], f.name, {type: f.type}));
          input.dataset.restored = '1';
          input.dispatchEvent(new Event('change', {bubbles: true}));
          restored++; note();
        }
      });
      input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f || input.dataset.shrinking) return;
        input.dataset.shrinking = '1'; form.classList.add('az-busy');
        shrink(f).then(function (small) {
          if (small !== f) { setFile(input, small); }
          delete input.dataset.shrinking; if (!form.querySelector('[data-shrinking]')) form.classList.remove('az-busy');
          var g = input.files[0];
          files.put(fkey, {blob: g, name: g.name, type: g.type});
          if (small !== f) input.dispatchEvent(new Event('change', {bubbles: true}));
        });
      });
    });

    form.addEventListener('submit', function (e) {
      if (form.querySelector('[data-shrinking]')) { e.preventDefault(); setTimeout(function () { form.requestSubmit(); }, 300); return; }
      save();
      ls(PENDING, {path: location.pathname, at: Date.now()});
    });
  });

  function note() {
    if (!restored || document.querySelector('.az-draft-note')) return;
    var p = document.createElement('p'); p.className = 'az-draft-note';
    p.textContent = 'We kept what you entered on this phone last time. Check it and press Continue.';
    forms[0].insertBefore(p, forms[0].firstChild);
  }
  note();

  // ------------------------------------------------------------------ guided selfies
  var STEPS = [
    {field: 'selfie_center', say: 'Look straight at the camera', sub: 'Fit your face in the oval. Eyes open, no hat or glasses.', want: function (r) { return r > 0.42 && r < 0.58; }, arrow: ''},
    {field: 'selfie_left', say: 'Now turn your head to your left', sub: 'Slowly, until we see the side of your face.', want: function (r) { return r > 0.68; }, arrow: 'left'},
    {field: 'selfie_right', say: 'Now turn your head to your right', sub: 'Slowly, until we see the other side.', want: function (r) { return r < 0.32; }, arrow: 'right'},
  ];
  var inputs = {};
  STEPS.forEach(function (s) { var i = document.querySelector('input[type=file][name="' + s.field + '"]'); if (i) inputs[s.field] = i; });
  if (!Object.keys(inputs).length || !(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) return;

  var cam = document.querySelector('[data-az-cam]'), video = cam.querySelector('video'), oval = cam.querySelector('[data-oval]'), arrow = cam.querySelector('[data-arrow]');
  var msg = cam.querySelector('[data-az-msg]'), sub = cam.querySelector('[data-az-sub]'), snap = cam.querySelector('[data-cam-snap]'), dots = cam.querySelectorAll('[data-steps] span');
  var stream = null, landmarker = null, step = 0, held = 0, running = false, manual = false;

  function loadModel() {
    if (landmarker) return Promise.resolve(landmarker);
    return import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14').then(function (v) {
      return v.FilesetResolver.forVisionTasks('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.14/wasm').then(function (fs) {
        return v.FaceLandmarker.createFromOptions(fs, {baseOptions: {modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/face_landmarker/face_landmarker/float16/1/face_landmarker.task', delegate: 'GPU'}, runningMode: 'VIDEO', numFaces: 2});
      });
    }).then(function (l) { landmarker = l; return l; });
  }

  function show(i) {
    step = i; held = 0;
    var s = STEPS[i];
    msg.textContent = s.say; sub.textContent = manual ? s.sub + ' Then press Take photo.' : s.sub;
    arrow.textContent = s.arrow === 'left' ? '←' : s.arrow === 'right' ? '→' : '';
    arrow.style.left = s.arrow === 'left' ? '6%' : ''; arrow.style.right = s.arrow === 'right' ? '6%' : '';
    Array.prototype.forEach.call(dots, function (d, k) { d.className = k < i ? 'done' : k === i ? 'on' : ''; });
  }

  function take() {
    var s = STEPS[step], input = inputs[s.field];
    var w = video.videoWidth, h = video.videoHeight, k = Math.min(1, 1280 / Math.max(w, h));
    var c = document.createElement('canvas'); c.width = Math.round(w * k); c.height = Math.round(h * k);
    c.getContext('2d').drawImage(video, 0, 0, c.width, c.height);
    c.toBlob(function (b) {
      if (input && b) {
        setFile(input, new File([b], s.field + '.jpg', {type: 'image/jpeg', lastModified: Date.now()}));
        input.dispatchEvent(new Event('change', {bubbles: true}));
        var row = input.closest('.upload'); if (row) { row.classList.add('done'); var hint = row.querySelector('.u-hint'); if (hint) { hint.textContent = 'Taken. Retake if it is not clear.'; hint.style.color = ''; } }
      }
      // next selfie still missing, or finish
      var next = STEPS.findIndex(function (t, k) { return k > step && inputs[t.field] && !(inputs[t.field].files && inputs[t.field].files.length); });
      if (next === -1) { close(); return; }
      show(next);
    }, 'image/jpeg', 0.9);
  }

  function loop() {
    if (!running) return;
    if (landmarker && video.readyState >= 2) {
      var res = landmarker.detectForVideo(video, performance.now());
      var faces = (res && res.faceLandmarks) || [];
      var ok = false;
      if (faces.length === 1) {
        var p = faces[0], nose = p[1], a = p[33], b = p[263];
        var left = Math.min(a.x, b.x), span = Math.abs(b.x - a.x) || 1e-6, ratio = (nose.x - left) / span;
        var close_ = span > 0.12, centred = nose.x > 0.25 && nose.x < 0.75 && nose.y > 0.2 && nose.y < 0.75;
        ok = close_ && centred && STEPS[step].want(ratio);
        if (!close_) sub.textContent = 'Come a little closer.';
        else if (!centred) sub.textContent = 'Keep your face inside the oval.';
        else if (!ok) sub.textContent = STEPS[step].sub;
        else sub.textContent = 'Hold still…';
      } else {
        sub.textContent = faces.length > 1 ? 'Only one face, please.' : 'We can\'t see your face yet.';
      }
      oval.classList.toggle('ok', ok);
      held = ok ? held + 1 : 0;
      if (held > 12) { held = -40; take(); }   // about half a second at the right angle
    }
    requestAnimationFrame(loop);
  }

  function open(start) {
    cam.hidden = false; manual = false; snap.hidden = true;
    show(Math.max(0, STEPS.findIndex(function (s) { return s.field === start; })));
    msg.textContent = 'Starting the camera…';
    navigator.mediaDevices.getUserMedia({video: {facingMode: 'user', width: {ideal: 1280}, height: {ideal: 960}}, audio: false}).then(function (s) {
      stream = s; video.srcObject = s; running = true;
      show(step);
      sub.textContent = 'Getting ready…';
      loadModel().then(function () { requestAnimationFrame(loop); }).catch(function () {
        // no face model (offline or blocked): the person takes each photo with the button
        manual = true; snap.hidden = false; show(step);
      });
    }).catch(function () {
      close();
      var i = inputs[start]; if (i) { i.setAttribute('capture', 'user'); i.click(); }
    });
  }
  function close() {
    running = false; cam.hidden = true;
    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
    stream = null; video.srcObject = null;
  }
  cam.querySelector('[data-cam-close]').addEventListener('click', close);
  snap.addEventListener('click', take);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !cam.hidden) close(); });

  // the "Take" buttons of the selfie rows open the guided camera instead of the file picker
  Object.keys(inputs).forEach(function (field) {
    var input = inputs[field];
    var label = document.querySelector('label[for="' + input.id + '"]');
    (label ? [label] : []).forEach(function (l) {
      l.addEventListener('click', function (e) { e.preventDefault(); open(field); });
    });
  });
})();
</script>
