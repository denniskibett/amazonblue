{{-- A person's picture wherever a user is shown (owner, 16 Nov 2026): the profile or KYC passport photo when there is one,
     loaded straight away (eager, high priority on the first rows), with the initials underneath so the circle is never
     empty while it loads or if it fails. <x-ui.avatar :user="$loan->user" class="size-10" /> --}}
@props(['user' => null, 'name' => null, 'text' => 'text-theme-xs'])
@php
  $name = $name ?? ($user->name ?? '');
  $initials = collect(preg_split('/\s+/u', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
  $photo = $user && method_exists($user, 'getAvatarUrl') ? $user->getAvatarUrl() : null;
  $photo = $photo && ! str_starts_with($photo, 'data:') ? $photo : null;
@endphp
<span {{ $attributes->class(['relative inline-flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-50 font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400', $text]) }}>
  <span aria-hidden="true">{{ $initials }}</span>
  @if ($photo)
    <img src="{{ $photo }}" alt="{{ $name }}" loading="eager" decoding="async" fetchpriority="high" draggable="false"
         class="absolute inset-0 size-full object-cover" onerror="this.remove()">
  @endif
</span>
