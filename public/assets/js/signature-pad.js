/*
 * Signature pad served by the app itself (owner, 24 Oct 2026): replaces signature_pad@4.0.0 from the jsDelivr CDN,
 * which the loan form, the loan modal and the profile page used to load at runtime. Same constructor and the three
 * methods the app calls: new SignaturePad(canvas, options), clear(), isEmpty(), toDataURL(type).
 * Options read: backgroundColor, penColor, minWidth, maxWidth, throttle (ms), velocityFilterWeight.
 * Mouse, touch and pen all work through pointer events; the stroke gets thinner the faster it is drawn.
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
    this._last = p;
    this._lastVelocity = 0;
    this._lastWidth = (this.minWidth + this.maxWidth) / 2;
    this._lastTime = p.t;
    // a tap leaves a dot
    var ctx = this.ctx;
    ctx.fillStyle = this.penColor;
    ctx.beginPath();
    ctx.arc(p.x, p.y, this._lastWidth / 2, 0, Math.PI * 2);
    ctx.fill();
    this._empty = false;
  };

  SignaturePad.prototype._onMove = function (e) {
    if (!this._drawing) return;
    e.preventDefault();
    var p = this._point(e);
    if (p.t - this._lastTime < this.throttle && p.t !== this._lastTime) return;
    var last = this._last;
    var dist = Math.hypot(p.x - last.x, p.y - last.y);
    if (dist < 0.5) return;
    var dt = Math.max(1, p.t - last.t);
    var v = this.velocityFilterWeight * (dist / dt) + (1 - this.velocityFilterWeight) * this._lastVelocity;
    var width = Math.max(this.minWidth, Math.min(this.maxWidth, this.maxWidth / (v + 1)));
    var ctx = this.ctx;
    ctx.strokeStyle = this.penColor;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.lineWidth = (width + this._lastWidth) / 2;
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.quadraticCurveTo(last.x, last.y, (last.x + p.x) / 2, (last.y + p.y) / 2);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    this._last = p;
    this._lastTime = p.t;
    this._lastVelocity = v;
    this._lastWidth = width;
    this._empty = false;
  };

  SignaturePad.prototype._onUp = function () {
    this._drawing = false;
    this._last = null;
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
