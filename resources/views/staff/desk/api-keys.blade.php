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
            <th class="py-3 pl-4 sm:pl-6">Name &amp; key</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Created</th><th class="px-4 py-3">Last used</th><th class="px-4 py-3">On / off</th><th class="py-3 pr-4 text-center sm:pr-6">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($keys as $k)
            <tr>
              <td class="py-4 pl-4 sm:pl-6">
                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $k->name }}</span>
                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $k->masked() }}</span>
              </td>
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
            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No API keys yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div x-cloak x-show="adding" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="adding = false">
      <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="adding = false"></div>
      <div class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
        <h4 class="mb-1 text-title-sm font-semibold text-gray-800 dark:text-white/90">Generate API key</h4>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Name it after the system that will use it, so you know what breaks if you switch it off.</p>
        <form method="POST" action="{{ route('staff.api-keys.store') }}" class="space-y-4">@csrf
          <label for="key-name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Application name</label>
          <input id="key-name" name="name" required maxlength="80" placeholder="e.g. Accounting sync" class="{{ $input }}">
          <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
            <button type="button" @click="adding = false" class="{{ $btn }}">Close</button>
            <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white hover:bg-brand-600">Generate key</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
