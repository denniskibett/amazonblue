{{-- API keys (owner, 3 Nov 2026): keys other systems send to /api/v1 as "Authorization: Bearer <key>".
     A new or regenerated key is shown once; only its hash is stored, so it cannot be shown again. --}}
@extends('layouts.app')

@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
  $btn = 'inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5';
  $new = session('new_key');
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'API keys', 'sub' => 'Keys let other systems call ' . $base . '. Send one as "Authorization: Bearer <key>". Test a key with GET ' . $base . '/ping.'])

  @if ($new)
    <div x-data="{ copied: false }" class="mb-6 rounded-2xl border border-success-500/30 bg-success-50 p-5 dark:bg-success-500/10">
      <p class="mb-2 text-sm font-medium text-success-700 dark:text-success-400">Copy the key for {{ $new['name'] }} now. It is not shown again.</p>
      <div class="flex items-center gap-2">
        <input x-ref="key" type="text" readonly value="{{ $new['value'] }}" class="{{ $input }} font-mono text-xs">
        <button type="button" class="{{ $btn }}" @click="navigator.clipboard.writeText($refs.key.value); copied = true; setTimeout(() => copied = false, 1500)" x-text="copied ? 'Copied' : 'Copy'">Copy</button>
      </div>
    </div>
  @endif

  {{-- The test APK behind /app (owner, 14 Nov 2026): upload a build here instead of copying it onto the server. --}}
  <div x-data="{ uploading: {{ $errors->has('apk') || $errors->has('version') ? 'true' : 'false' }} }" class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col justify-between gap-5 p-5 sm:flex-row sm:items-center sm:px-6">
      <div class="flex items-start gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15"><x-ui.icon name="phone" class="size-6" /></span>
        <div>
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Mobile app (test APK)</h3>
          @if ($apk)
            <p class="text-sm text-gray-500 dark:text-gray-400">Version {{ $apk['version'] }} · {{ number_format($apk['size'] / 1048576, 1) }} MB · up since {{ $apk['updated']->format('j M Y, H:i') }}</p>
            <p class="font-mono text-xs break-all text-gray-400">SHA-256 {{ $apk['sha256'] }}</p>
          @else
            <p class="text-sm text-gray-500 dark:text-gray-400">No APK is up. The download page says "not up yet".</p>
          @endif
          <p class="mt-1 text-sm"><a href="{{ route('site.white-label') }}" target="_blank" rel="noopener" class="text-brand-500 hover:underline">{{ route('site.white-label') }}</a>
            <span class="text-gray-400">· build it with a "Mobile app" key from the list below</span></p>
        </div>
      </div>
      <div class="flex shrink-0 gap-2">
        @if ($apk)
          <form method="POST" action="{{ route('staff.api-keys.apk.remove') }}">@csrf @method('DELETE')
            <button type="submit" class="{{ $btn }}" data-confirm="Take version {{ $apk['version'] }} down? The download page will say it is not up yet." data-confirm-danger>Take down</button>
          </form>
        @endif
        <button type="button" @click="uploading = true" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">{{ $apk ? 'Upload new build' : 'Upload APK' }}</button>
      </div>
    </div>

    <div x-cloak x-show="uploading" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="uploading = false">
      <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px] dark:bg-gray-900/70" @click="uploading = false"></div>
      <div class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
        <h4 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">Upload the test APK</h4>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">It replaces the file on {{ route('site.white-label') }}. Phones that have the app keep working; testers install the new one over it.</p>
        <form method="POST" action="{{ route('staff.api-keys.apk') }}" enctype="multipart/form-data" class="space-y-4">@csrf
          <div>
            <label for="apk-file" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">APK file</label>
            <input id="apk-file" type="file" name="apk" accept=".apk,application/vnd.android.package-archive" required class="{{ $input }} !h-auto file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm dark:file:bg-white/10">
            @error('apk')<p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>@enderror
            @php $limit = min(array_map(fn ($v) => (int) $v * (['k' => 1 / 1024, 'm' => 1, 'g' => 1024][strtolower(substr(trim($v), -1))] ?? 1 / 1048576), [ini_get('upload_max_filesize'), ini_get('post_max_size')])); @endphp
            <p class="mt-1.5 text-xs {{ $limit < 50 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-500' }}">This server takes files up to {{ rtrim(rtrim(number_format($limit, 1), '0'), '.') }} MB{{ $limit < 50 ? '. A release APK is about 25 MB, so raise upload_max_filesize and post_max_size in PHP first.' : '.' }}</p>
          </div>
          <div>
            <label for="apk-version" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Version</label>
            <input id="apk-version" name="version" required maxlength="20" value="{{ old('version', $apk['version'] ?? config('mobile.apk.version')) }}" placeholder="0.1.1" class="{{ $input }}">
            @error('version')<p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>@enderror
          </div>
          <div>
            <label for="apk-notes" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">What changed</label>
            <textarea id="apk-notes" name="notes" rows="3" maxlength="300" class="{{ $input }} !h-auto" placeholder="Shown on the download page">{{ old('notes', $apk['notes'] ?? '') }}</textarea>
          </div>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
            <button type="button" @click="uploading = false" class="{{ $btn }}">Close</button>
            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600">Upload</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div x-data="{ adding: false }" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col justify-between gap-5 border-b border-gray-100 p-5 dark:border-gray-800 sm:flex-row sm:items-center sm:px-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">API keys</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $keys->where('status', 'active')->count() }} active of {{ $keys->count() }}. Every change is in the audit log.</p>
      </div>
      <button type="button" @click="adding = true" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">+ Add API key</button>
    </div>

    <div class="w-full overflow-x-auto">
      <table class="w-full min-w-[750px]">
        <thead class="border-b border-gray-100 dark:border-gray-800">
          <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400">
            <th class="py-3 pl-4 sm:pl-6">Name &amp; key</th><th class="px-4 py-3">Used by</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th><th class="px-4 py-3">Last used</th><th class="px-4 py-3">On / off</th><th class="py-3 pr-4 text-center sm:pr-6">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($keys as $k)
            <tr>
              <td class="py-4 pl-4 sm:pl-6">
                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $k->name }}</span>
                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $k->masked() }}</span>
              </td>
              <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ \App\Models\ApiKey::CLIENTS[$k->client ?? 'system'] ?? 'Other system' }}<span class="block text-xs text-gray-500">{{ ($k->client ?? 'system') === 'mobile' ? '/api/mobile/v1' : '/api/v1' }}</span></td>
              <td class="px-4 py-4">
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $k->status === 'active' ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-100 text-gray-700 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($k->status) }}</span>
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $k->created_at?->format('j M Y') }}</td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $k->last_used_at ? $k->last_used_at->diffForHumans() . ' · ' . $k->last_used_ip : 'Never' }}</td>
              <td class="px-4 py-4">
                <form method="POST" action="{{ route('staff.api-keys.toggle', $k) }}">@csrf
                  <button type="submit" role="switch" aria-checked="{{ $k->status === 'active' ? 'true' : 'false' }}" aria-label="Switch {{ $k->name }} {{ $k->status === 'active' ? 'off' : 'on' }}"
                    class="relative inline-flex h-6 w-[52px] items-center rounded-full p-0.5 {{ $k->status === 'active' ? 'bg-brand-500 hover:bg-brand-600' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700' }}">
                    <span class="block h-5 w-[30px] rounded-full bg-white shadow-theme-sm transition-transform {{ $k->status === 'active' ? 'translate-x-[18px]' : '' }}"></span>
                  </button>
                </form>
              </td>
              <td class="py-4 pr-4 sm:pr-6">
                <div class="flex items-center justify-center gap-2">
                  <form method="POST" action="{{ route('staff.api-keys.regenerate', $k) }}">@csrf
                    <button type="submit" class="{{ $btn }} !h-9 !px-3 text-xs" data-confirm="Make a new secret for {{ $k->name }}? The old one stops working at once.">Regenerate</button>
                  </form>
                  <form method="POST" action="{{ route('staff.api-keys.destroy', $k) }}">@csrf @method('DELETE')
                    <button type="submit" class="inline-flex size-9 items-center justify-center rounded-lg text-gray-500 hover:bg-error-500/10 hover:text-error-500" aria-label="Delete {{ $k->name }}" data-confirm="Delete {{ $k->name }}? Anything using it gets 401." data-confirm-danger>
                      <x-ui.icon name="trash" class="size-5" />
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No API keys yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div x-cloak x-show="adding" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="adding = false">
      <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px] dark:bg-gray-900/70" @click="adding = false"></div>
      <div class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
        <h4 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">Generate API key</h4>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Name it after the system that will use it, so you know what breaks if you switch it off.</p>
        <form method="POST" action="{{ route('staff.api-keys.store') }}" class="space-y-4">@csrf
          <label for="key-name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Application name</label>
          <input id="key-name" name="name" required maxlength="80" placeholder="e.g. Mobile app (test APK) or Accounting sync" class="{{ $input }}">
          <label for="key-client" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Used by</label>
          <select id="key-client" name="client" class="{{ $input }}">@foreach (\App\Models\ApiKey::CLIENTS as $v => $l)<option value="{{ $v }}">{{ $l }}{{ $v === 'mobile' ? ' (/api/mobile/v1)' : ' (/api/v1)' }}</option>@endforeach</select>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
            <button type="button" @click="adding = false" class="{{ $btn }}">Close</button>
            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600">Generate key</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
