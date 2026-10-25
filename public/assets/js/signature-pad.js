/*
 * Signature pad served by the app itself (owner, 24 Oct 2026): replaces signature_pad@4.0.0 from the jsDelivr CDN,
 * which the loan form, the loan modal and the profile page used to load at runtime. Same constructor and the three
 * methods the app calls: new SignaturePad(canvas, options), clear(), isEmpty(), toDataURL(type).
 * Options read: backgroundColor, penColor, minWidth, maxWidth, throttle (ms), velocityFilterWeight.
 * Mouse, touch and pen all work through pointer events. Used only when the original library (signature_pad from
 * jsDelivr) can't load: partials/ui/signature-pad-js loads that first (owner, 25 Oct 2026).
 */
(function (global) {
  'use strict';
  if (global.SignaturePad) return;

  function SignaturePad(canvas, options) {
    var o = options || {};
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d');
    this.backgroundColor = o.backgroundColor || 'rgba(0,0,0,0)';
    this.penColor = o.penColor || 'black';
    this.minWidth = o.minWidth != null ? o.minWidth : 0.5;
    this.maxWidth = o.maxWidth != null ? o.maxWidth : 2.5;
    this.throttle = o.throttle != null ? o.throttle : 16;
    this.velocityFilterWeight = o.velocityFilterWeight != null ? o.velocityFilterWeight : 0.7;
    this._empty = true;
    this._drawing = false;
    this._last = null;
    this._lastWidth = (this.minWidth + this.maxWidth) / 2;
    this._lastVelocity = 0;
    this._lastTime = 0;
    canvas.style.touchAction = 'none';
    this._down = this._onDown.bind(this);
    this._move = this._onMove.bind(this);
    this._up = this._onUp.bind(this);
    canvas.addEventListener('pointerdown', this._down);
    canvas.addEventListener('pointermove', this._move);
    canvas.addEventListener('pointerup', this._up);
    canvas.addEventListener('pointercancel', this._up);
    canvas.addEventListener('pointerleave', this._up);
    this.clear();
  }

  SignaturePad.prototype._point = function (e) {
    var r = this.canvas.getBoundingClientRect();
    // the canvas may be drawn at a different size than it is shown (the app sets width = 2x)
    var sx = r.width ? this.canvas.width / r.width : 1;
    var sy = r.height ? this.canvas.height / r.height : 1;
    return { x: (e.clientX - r.left) * sx, y: (e.clientY - r.top) * sy, t: Date.now() };
  };

  SignaturePad.prototype._onDown = function (e) {
    if (e.button !== undefined && e.button > 0) return;
    e.preventDefault();
    if (this.canvas.setPointerCapture) { try { this.canvas.setPointerCapture(e.pointerId); } catch (_) {} }
    this._drawing = true;
    var p = this._point(e);
    this._pts = [p];
    this._lastWidth = (this.minWidth + this.maxWidth) / 2;
    this._lastVelocity = 0;
    // a tap leaves a dot
    var ctx = this.ctx;
    ctx.fillStyle = this.penColor;
    ctx.beginPath();
    ctx.arc(p.x, p.y, this._lastWidth / 2, 0, Math.PI * 2);
    ctx.fill();
    this._empty = false;
  };

  // every sample the device gives (coalesced events), joined with smooth curves through the midpoints,
  // so the line follows the hand like a pen instead of straight segments (owner, 25 Oct 2026)
  SignaturePad.prototype._onMove = function (e) {
    if (!this._drawing) return;
    e.preventDefault();
    var events = e.getCoalescedEvents ? e.getCoalescedEvents() : [];
    if (!events.length) events = [e];
    for (var i = 0; i < events.length; i++) this._add(this._point(events[i]));
  };

  SignaturePad.prototype._add = function (p) {
    var pts = this._pts;
    var last = pts[pts.length - 1];
    var dist = Math.hypot(p.x - last.x, p.y - last.y);
    if (dist < 1) return;
    var dt = Math.max(1, p.t - last.t);
    var v = this.velocityFilterWeight * this._lastVelocity + (1 - this.velocityFilterWeight) * (dist / dt);
    // a gentle change of width with speed; never jumps between strokes of one line
    var target = Math.max(this.minWidth, Math.min(this.maxWidth, this.maxWidth - (this.maxWidth - this.minWidth) * Math.min(1, v / 3)));
    var width = this._lastWidth + (target - this._lastWidth) * 0.35;
    pts.push(p);
    if (pts.length > 3) pts.shift();
    var ctx = this.ctx;
    ctx.strokeStyle = this.penColor;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.lineWidth = width;
    ctx.beginPath();
    if (pts.length < 3) {
      ctx.moveTo(pts[0].x, pts[0].y);
      ctx.lineTo((pts[0].x + p.x) / 2, (pts[0].y + p.y) / 2);
    } else {
      var a = pts[0], b = pts[1], c = pts[2];
      ctx.moveTo((a.x + b.x) / 2, (a.y + b.y) / 2);
      ctx.quadraticCurveTo(b.x, b.y, (b.x + c.x) / 2, (b.y + c.y) / 2);
    }
    ctx.stroke();
    this._lastVelocity = v;
    this._lastWidth = width;
    this._empty = false;
  };

  SignaturePad.prototype._onUp = function () {
    if (this._drawing && this._pts && this._pts.length > 1) {
      // finish the last half-segment to where the pen lifted
      var pts = this._pts, b = pts[pts.length - 2], c = pts[pts.length - 1], ctx = this.ctx;
      ctx.beginPath();
      ctx.lineWidth = this._lastWidth;
      ctx.moveTo((b.x + c.x) / 2, (b.y + c.y) / 2);
      ctx.lineTo(c.x, c.y);
      ctx.stroke();
    }
    this._drawing = false;
    this._pts = null;
  };

  SignaturePad.prototype.clear = function () {
    var ctx = this.ctx;
    ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    ctx.fillStyle = this.backgroundColor;
    ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    this._empty = true;
  };

  SignaturePad.prototype.isEmpty = function () {
    return this._empty;
  };

  SignaturePad.prototype.toDataURL = function (type, quality) {
    return this.canvas.toDataURL(type || 'image/png', quality);
  };

  SignaturePad.prototype.off = function () {
    this.canvas.removeEventListener('pointerdown', this._down);
    this.canvas.removeEventListener('pointermove', this._move);
    this.canvas.removeEventListener('pointerup', this._up);
    this.canvas.removeEventListener('pointercancel', this._up);
    this.canvas.removeEventListener('pointerleave', this._up);
  };

  global.SignaturePad = SignaturePad;
})(window);
