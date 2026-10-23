{{-- Crop a KYC picture (owner, 22 Oct 2026). Opened by: $dispatch('open-crop', {src, action, document, original})
     Staff set the width and height in pixels (or pick a shape), drag the box over the part to keep, turn the picture
     if it is sideways, and save. The server keeps the upload as {file}.original, so "Undo crop" brings it back. --}}
<template x-teleport="body">
<div x-data="azCrop()" @open-crop.window="open($event.detail)" @keydown.escape.window="show = false"
     x-show="show" x-cloak class="fixed inset-0 z-[100001] flex items-center justify-center p-4 az-backdrop" style="display:none">
  <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-theme-xl dark:bg-gray-900" @click.outside="show = false">
    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Crop picture</h3>
      <button type="button" class="flex size-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5" @click="show = false" aria-label="Close"><x-ui.icon name="x" class="size-5" /></button>
    </div>
    <div class="grid gap-5 p-5 md:grid-cols-[1fr_220px]">
      <div class="relative flex min-h-64 items-center justify-center overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800" x-ref="stage">
        <template x-if="loading"><p class="text-theme-sm text-gray-500">Loading picture…</p></template>
        <div class="relative select-none" x-show="!loading" :style="`width:${dw}px;height:${dh}px`">
          <img :src="view" class="absolute inset-0 h-full w-full" draggable="false" alt="">
          {{-- shade outside the box --}}
          <div class="pointer-events-none absolute inset-0 bg-black/45" :style="`clip-path: polygon(0 0,100% 0,100% 100%,0 100%,0 ${by}px,${bx}px ${by}px,${bx}px ${by + bh}px,${bx + bw}px ${by + bh}px,${bx + bw}px ${by}px,0 ${by}px)`"></div>
          <div class="absolute cursor-move border-2 border-white shadow-[0_0_0_1px_rgba(0,0,0,.4)]" :style="`left:${bx}px;top:${by}px;width:${bw}px;height:${bh}px`" @pointerdown.prevent="drag($event, 'move')">
            <span class="absolute -bottom-2 -right-2 size-4 cursor-nwse-resize rounded-sm border-2 border-white bg-brand-500" @pointerdown.prevent.stop="drag($event, 'size')"></span>
          </div>
        </div>
      </div>
      <form method="POST" :action="action" class="space-y-4">
        @csrf
        <input type="hidden" name="document" :value="documentKey">
        <input type="hidden" name="x" :value="Math.round(bx / k)"><input type="hidden" name="y" :value="Math.round(by / k)">
        <input type="hidden" name="rotate" :value="rotate">
        <div>
          <p class="mb-1.5 text-theme-sm font-medium text-gray-700 dark:text-gray-400">Shape</p>
          <div class="grid grid-cols-2 gap-1.5">
            <template x-for="p in presets" :key="p.name">
              <button type="button" @click="preset(p)" class="rounded-lg border px-2 py-1.5 text-theme-xs font-medium"
                      :class="ratio === p.ratio ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-300 text-gray-600 dark:border-gray-700 dark:text-gray-400'" x-text="p.name"></button>
            </template>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <label class="block"><span class="mb-1 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Width (px)</span>
            <input type="number" name="width" min="40" :max="nw" x-model.number="cw" @input="fromInputs('w')" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm dark:border-gray-700 dark:text-white/90"></label>
          <label class="block"><span class="mb-1 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Height (px)</span>
            <input type="number" name="height" min="40" :max="nh" x-model.number="ch" @input="fromInputs('h')" class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm dark:border-gray-700 dark:text-white/90"></label>
        </div>
        <p class="text-theme-xs text-gray-500 dark:text-gray-400">Picture is <span x-text="nw"></span> x <span x-text="nh"></span> px. Drag the box to the part to keep; drag its corner to resize.</p>
        <button type="button" @click="turn()" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5"><x-ui.icon name="refresh" class="size-4" /> Turn 90°</button>
        <div class="flex gap-2 pt-1">
          <button type="submit" class="flex-1 rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Save crop</button>
        </div>
        <template x-if="hasOriginal">
          <button type="submit" name="restore" value="1" formnovalidate class="w-full rounded-lg border border-gray-300 px-4 py-2 text-theme-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5">Undo crop (put the original back)</button>
        </template>
      </form>
    </div>
  </div>
</div>
</template>
<script>
function azCrop() {
  return {
    show: false, loading: false, src: '', view: '', action: '', documentKey: '', hasOriginal: false,
    img: null, rotate: 0, nw: 0, nh: 0, dw: 0, dh: 0, k: 1, bx: 0, by: 0, bw: 0, bh: 0, cw: 0, ch: 0, ratio: null,
    presets: [{name: 'Free', ratio: null}, {name: 'ID card', ratio: 85.6 / 54}, {name: 'Passport photo', ratio: 35 / 45}, {name: 'Square', ratio: 1}],
    open(d) {
      Object.assign(this, {src: d.src, action: d.action, documentKey: d.document || '', hasOriginal: !!d.original, rotate: 0, ratio: null, show: true, loading: true});
      const img = new Image();
      img.onload = () => { this.img = img; this.$nextTick(() => requestAnimationFrame(() => this.render())); };
      img.onerror = () => { this.loading = false; this.show = false; window.uiAlert ? uiAlert('This picture is not on this computer, so it cannot be cropped here.') : null; };
      img.src = d.src + (d.src.includes('?') ? '&' : '?') + 't=' + Date.now();
    },
    render() {
      const r = this.rotate % 180 !== 0, w = r ? this.img.naturalHeight : this.img.naturalWidth, h = r ? this.img.naturalWidth : this.img.naturalHeight;
      const c = document.createElement('canvas'); c.width = w; c.height = h;
      const x = c.getContext('2d'); x.translate(w / 2, h / 2); x.rotate(this.rotate * Math.PI / 180);
      x.drawImage(this.img, -this.img.naturalWidth / 2, -this.img.naturalHeight / 2);
      this.view = c.toDataURL('image/jpeg', 0.9);
      this.nw = w; this.nh = h;
      const box = this.$refs.stage.getBoundingClientRect(), maxW = Math.max(240, box.width - 16), maxH = Math.min(window.innerHeight * 0.6, 520);
      this.k = Math.min(maxW / w, maxH / h, 1);
      this.dw = Math.round(w * this.k); this.dh = Math.round(h * this.k);
      // start with most of the picture selected
      this.bw = Math.round(this.dw * 0.9); this.bh = Math.round(this.dh * 0.9); this.bx = Math.round((this.dw - this.bw) / 2); this.by = Math.round((this.dh - this.bh) / 2);
      this.sync(); this.loading = false;
    },
    sync() { this.cw = Math.round(this.bw / this.k); this.ch = Math.round(this.bh / this.k); },
    clamp() {
      this.bw = Math.max(20, Math.min(this.bw, this.dw)); this.bh = Math.max(20, Math.min(this.bh, this.dh));
      this.bx = Math.max(0, Math.min(this.bx, this.dw - this.bw)); this.by = Math.max(0, Math.min(this.by, this.dh - this.bh));
    },
    fromInputs(which) {
      let w = (this.cw || 0) * this.k, h = (this.ch || 0) * this.k;
      if (this.ratio) { if (which === 'w') h = w / this.ratio; else w = h * this.ratio; }
      this.bw = w; this.bh = h; this.clamp();
      if (this.ratio) this.sync();
    },
    preset(p) {
      this.ratio = p.ratio;
      if (!p.ratio) return;
      let w = this.bw, h = w / p.ratio;
      if (h > this.dh) { h = this.dh * 0.9; w = h * p.ratio; }
      this.bw = w; this.bh = h; this.clamp(); this.sync();
    },
    turn() { this.rotate = (this.rotate + 90) % 360; this.render(); },
    drag(e, mode) {
      const sx = e.clientX, sy = e.clientY, ox = this.bx, oy = this.by, ow = this.bw, oh = this.bh;
      const move = (ev) => {
        const dx = ev.clientX - sx, dy = ev.clientY - sy;
        if (mode === 'move') { this.bx = ox + dx; this.by = oy + dy; }
        else { this.bw = ow + dx; this.bh = this.ratio ? (ow + dx) / this.ratio : oh + dy; }
        this.clamp(); this.sync();
      };
      const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); };
      window.addEventListener('pointermove', move); window.addEventListener('pointerup', up);
    },
  };
}
</script>
