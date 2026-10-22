{{-- A person cell in the Loans Due look: initials, the name (opens the person panel) and one line under it.
     <x-staff.person :user="$loan->user" :sub="$loan->user->phone" /> --}}
@props(['user' => null, 'name' => null, 'sub' => null, 'id' => null])
@php
  $name = $name ?? ($user->name ?? '—');
  $id = $id ?? ($user->id ?? null);
  $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
@endphp
<div class="flex items-center gap-3">
  <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-theme-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $initials }}</span>
  <div class="min-w-0">
    <p class="truncate font-medium text-gray-800 text-theme-sm dark:text-white/90 {{ $id ? 'cursor-pointer hover:text-brand-500' : '' }}" @if ($id) data-user-peek="{{ $id }}" @endif>{{ $name }}</p>
    @if ($sub)<span class="block truncate text-theme-xs text-gray-500 dark:text-gray-400">{{ $sub }}</span>@endif
  </div>
</div>
