{{-- One message (owner, 16 Nov 2026). HTML mail is shown in a sandboxed frame: no scripts, no forms, no remote
     images or fonts (they would tell the sender the mail was opened), links open in a new tab. --}}
@extends('layouts.app')

@php
  $label = $folders[$folder]['label'] ?? 'Inbox';
  $btn = 'flex size-10 items-center justify-center rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5';
  $who = fn ($a) => $a['name'] ? $a['name'] . ' <' . $a['email'] . '>' : $a['email'];
  $initials = collect(preg_split('/\s+/', trim($m['from']['name'] ?: $m['from']['email'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $size = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' MB' : ($b >= 1024 ? number_format($b / 1024) . ' KB' : $b . ' B');
  $srcdoc = $m['html'] !== null
    ? '<!doctype html><html><head><meta charset="utf-8">'
      . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src data:; style-src \'unsafe-inline\'; font-src data:">'
      . '<base target="_blank"><style>body{margin:0;padding:0 2px;font:14px/1.6 system-ui,-apple-system,Segoe UI,sans-serif;color:#344054;word-wrap:break-word}img{max-width:100%;height:auto}</style></head><body>'
      . $m['html'] . '</body></html>'
    : null;
  $list = route('staff.mail.index', array_filter(['folder' => $folder === 'inbox' ? null : $folder, 'q' => $q ?: null]));
  $one = fn ($a, $title, $icon, $confirm = null) => '<form method="POST" action="' . e(route('staff.mail.bulk', $folder)) . '"' . ($confirm ? ' onsubmit="return confirm(' . e(json_encode($confirm)) . ')"' : '') . '>'
      . csrf_field() . '<input type="hidden" name="action" value="' . $a . '"><input type="hidden" name="uids[]" value="' . $m['uid'] . '"><input type="hidden" name="q" value="' . e($q) . '">'
      . '<button type="submit" class="' . $btn . '" title="' . e($title) . '" aria-label="' . e($title) . '">' . \Illuminate\Support\Facades\Blade::render('<x-ui.icon :name="$i" class="size-5" />', ['i' => $icon]) . '</button></form>';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => $label, 'sub' => 'Your ' . auth()->user()->email . ' mailbox.', 'metrics' => false])

  <div class="flex flex-col gap-5 xl:flex-row">
    @include('staff.mail._folders')

    <section class="flex min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      {{-- Toolbar --}}
      <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-4 dark:border-gray-800 sm:px-6">
        <div class="flex items-center gap-2">
          <a href="{{ $list }}" class="{{ $btn }}" title="Back to {{ $label }}" aria-label="Back"><x-ui.icon name="chevron-right" class="size-5 rotate-180" /></a>
          {!! $one('delete', $folder === 'trash' ? 'Delete for good' : 'Delete', 'trash', $folder === 'trash' ? 'Delete this message for good? This cannot be undone.' : null) !!}
          @if ($folder !== 'spam') {!! $one('spam', 'Move to Spam', 'alert') !!} @else {!! $one('inbox', 'Not spam: move to Inbox', 'inbox') !!} @endif
          @if ($folder !== 'archive') {!! $one('archive', 'Archive', 'archive') !!} @endif
          {!! $one('unread', 'Mark as unread', 'mail') !!}
        </div>
        <div class="flex items-center gap-2">
          @if ($position)<span class="hidden text-theme-sm text-gray-500 dark:text-gray-400 sm:inline">{{ $position }} of {{ number_format($total) }}</span>@endif
          @if ($prev)
            <a href="{{ route('staff.mail.show', array_filter(['folder' => $folder, 'uid' => $prev, 'q' => $q ?: null])) }}" class="{{ $btn }}" title="Newer" aria-label="Newer"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /></a>
          @else
            <span class="{{ $btn }} opacity-40" aria-hidden="true"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /></span>
          @endif
          @if ($next)
            <a href="{{ route('staff.mail.show', array_filter(['folder' => $folder, 'uid' => $next, 'q' => $q ?: null])) }}" class="{{ $btn }}" title="Older" aria-label="Older"><x-ui.icon name="chevron-right" class="size-4" /></a>
          @else
            <span class="{{ $btn }} opacity-40" aria-hidden="true"><x-ui.icon name="chevron-right" class="size-4" /></span>
          @endif
        </div>
      </div>

      {{-- Header --}}
      <div class="px-4 sm:px-6">
        <div class="flex flex-col gap-3 border-b border-gray-100 py-5 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-50 text-theme-sm font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $initials ?: '?' }}</span>
            <div class="min-w-0">
              <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $m['subject'] !== '' ? $m['subject'] : '(no subject)' }}</h2>
              <p class="text-theme-sm text-gray-800 dark:text-white/90">
                <span class="font-medium">{{ $m['from']['name'] ?: $m['from']['email'] }}</span>
                @if ($m['from']['name'])<span class="text-gray-500 dark:text-gray-400">&lt;{{ $m['from']['email'] }}&gt;</span>@endif
              </p>
              @if ($m['to'])<p class="mt-0.5 break-words text-theme-xs text-gray-500 dark:text-gray-400">To: {{ implode(', ', array_map($who, $m['to'])) }}</p>@endif
              @if ($m['cc'])<p class="break-words text-theme-xs text-gray-500 dark:text-gray-400">Cc: {{ implode(', ', array_map($who, $m['cc'])) }}</p>@endif
            </div>
          </div>
          <p class="shrink-0 text-theme-xs text-gray-500 dark:text-gray-400">{{ $m['date']?->format('M j, Y (g:i A)') }}</p>
        </div>
      </div>

      {{-- Body --}}
      <div class="flex-1 px-4 py-5 sm:px-6">
        @if ($srcdoc !== null)
          <iframe title="Message" srcdoc="{{ $srcdoc }}" sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer"
                  class="h-[60vh] w-full rounded-lg border-0 bg-white"></iframe>
          <p class="mt-2 text-theme-xs text-gray-400">Pictures from the internet are not loaded, so the sender can't tell you opened this.</p>
        @else
          <div class="whitespace-pre-wrap break-words text-theme-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $m['text'] ?? '' }}</div>
        @endif

        @if ($m['attachments'])
          <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
            <p class="mb-3 text-theme-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ count($m['attachments']) }} {{ \Illuminate\Support\Str::plural('attachment', count($m['attachments'])) }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
              @foreach ($m['attachments'] as $a)
                <a href="{{ route('staff.mail.attachment', ['folder' => $folder, 'uid' => $m['uid'], 'index' => $a['index']]) }}"
                   class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 hover:border-brand-300 dark:border-gray-800 dark:bg-gray-900">
                  <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/15"><x-ui.icon name="file" class="size-5" /></span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $a['name'] }}</span>
                    <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ strtoupper(pathinfo($a['name'], PATHINFO_EXTENSION) ?: 'file') }} &middot; {{ $size($a['size']) }}</span>
                  </span>
                  <x-ui.icon name="download" class="size-5 text-gray-400" />
                </a>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      {{-- Reply --}}
      <div class="flex flex-wrap gap-3 border-t border-gray-200 px-4 py-4 dark:border-gray-800 sm:px-6">
        @php $c = 'inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5'; @endphp
        <a href="{{ route('staff.mail.compose', ['mode' => 'reply', 'folder' => $folder, 'uid' => $m['uid']]) }}" class="{{ $c }}"><x-ui.icon name="chevron-right" class="size-4 rotate-180" /> Reply</a>
        <a href="{{ route('staff.mail.compose', ['mode' => 'reply-all', 'folder' => $folder, 'uid' => $m['uid']]) }}" class="{{ $c }}"><x-ui.icon name="users" class="size-4" /> Reply all</a>
        <a href="{{ route('staff.mail.compose', ['mode' => 'forward', 'folder' => $folder, 'uid' => $m['uid']]) }}" class="{{ $c }}"><x-ui.icon name="send" class="size-4" /> Forward</a>
      </div>
    </section>
  </div>
@endsection
