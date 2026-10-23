{{-- Placeholder for a missing personal document, with paste / drop / choose-file upload (staff with `edit users`).
     Props: user, document (photo | id-front | id-back | signature), title, hint (what belongs here), icon (Font Awesome),
     initials (shown in a circle instead of the icon, for the passport photo).
     Posts to users.documents.store; the page reloads when the image is saved. --}}
@props(['user', 'document', 'title', 'hint', 'icon' => 'fa-image', 'initials' => null])
@php $canUpload = auth()->user()?->can('edit users'); @endphp
<div @if ($canUpload) x-data="staffDocDrop(@js(route('users.documents.store', [$user, $document])))" tabindex="0"
       @paste.window="if ($el.contains(document.activeElement)) paste($event)"
       @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="drop($event)" @endif
     {{ $attributes->class(['flex h-48 w-full items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-4 text-center outline-none dark:border-gray-600 dark:bg-gray-700',
        'cursor-pointer focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20' => $canUpload]) }}
     @if ($canUpload) :class="over && '!border-brand-500 !bg-brand-50 dark:!bg-brand-500/10'" @endif>
  <div>
    @if ($initials)
      <span class="mx-auto mb-2 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-lg font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $initials }}</span>
    @else
      <i class="fas {{ $icon }} mb-2 text-3xl text-gray-400 dark:text-gray-500"></i>
    @endif
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $title }}</p>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @if ($canUpload)
      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="!busy">
        Click here and paste (Ctrl+V / ⌘V), drop an image, or
        <button type="button" class="font-medium text-brand-500 hover:underline" @click="$refs.input.click()">choose a file</button>
      </p>
      <p class="mt-2 text-xs font-medium text-brand-500" x-show="busy" x-cloak>Saving…</p>
      <p class="mt-2 text-xs font-medium text-red-600" x-show="error" x-text="error" x-cloak></p>
      <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" x-ref="input" @change="send($event.target.files[0])">
    @endif
  </div>
</div>

@once
  <script>
    // one paste/drop/choose uploader per placeholder; reloads the page once the image is saved
    window.staffDocDrop = function (url) {
      return {
        over: false, busy: false, error: null,
        paste(e) {
          const item = [...(e.clipboardData?.items || [])].find(i => i.type.startsWith('image/'));
          if (item) { e.preventDefault(); this.send(item.getAsFile()); }
          else { this.error = 'The clipboard has no image. Copy the picture itself, then paste here.'; }
        },
        drop(e) { this.over = false; this.send(e.dataTransfer?.files?.[0]); },
        async send(file) {
          if (!file) return;
          if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { this.error = 'Use a JPG, PNG or WebP image.'; return; }
          if (file.size > 10 * 1024 * 1024) { this.error = 'The image is over 10 MB.'; return; }
          this.busy = true; this.error = null;
          const body = new FormData();
          body.append('file', file, file.name || ('pasted.' + file.type.split('/')[1]));
          try {
            const res = await fetch(url, { method: 'POST', body, headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
            if (!res.ok) {
              const data = await res.json().catch(() => ({}));
              throw new Error(data.errors?.file?.[0] || data.message || 'Could not save the image.');
            }
            (window.azRefresh ? window.azRefresh() : location.reload());
          } catch (err) { this.error = err.message; this.busy = false; }
        },
      };
    };
  </script>
@endonce
