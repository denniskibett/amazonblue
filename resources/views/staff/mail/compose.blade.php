{{-- Compose / reply / forward (owner, 16 Nov 2026). Sent over SMTP as the signed-in staff member; a copy goes to
     their Sent folder on the mail server. Nothing is kept here: if sending fails, this page comes back filled in. --}}
@extends('layouts.app')

@php
  $title = ['reply' => 'Reply', 'reply-all' => 'Reply all', 'forward' => 'Forward'][$v['mode']] ?? 'New message';
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $lbl = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
  $size = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1) . ' MB' : ($b >= 1024 ? number_format($b / 1024) . ' KB' : $b . ' B');
  $back = $v['mode'] && $v['ref_uid'] ? route('staff.mail.show', ['folder' => $v['ref_folder'], 'uid' => $v['ref_uid']]) : route('staff.mail.index');
@endphp

@section('content')
  @include('staff.desk._header', ['title' => $title, 'sub' => 'From ' . auth()->user()->name . ' <' . auth()->user()->email . '>', 'metrics' => false])

  <div class="flex flex-col gap-5 xl:flex-row">
    @include('staff.mail._folders')

    <section class="min-w-0 flex-1 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <form method="POST" action="{{ route('staff.mail.send') }}" enctype="multipart/form-data" class="space-y-5 p-4 sm:p-6"
            x-data="{ copies: @js(($v['cc'] ?? '') !== '' || ($v['bcc'] ?? '') !== ''), sending: false }" @submit="sending = true">
        @csrf
        @foreach (['mode', 'ref_folder', 'ref_uid'] as $h)
          @if ($v[$h])<input type="hidden" name="{{ $h }}" value="{{ $v[$h] }}">@endif
        @endforeach

        @error('send')
          <div class="rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-theme-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400">{{ $message }}</div>
        @enderror

        <div>
          <div class="flex items-center justify-between">
            <label for="mail-to" class="{{ $lbl }}">To</label>
            <button type="button" class="mb-1.5 text-theme-xs text-brand-500 hover:underline" x-show="! copies" @click="copies = true">Cc / Bcc</button>
          </div>
          <input id="mail-to" name="to" type="text" value="{{ $v['to'] }}" class="{{ $input }}" placeholder="name@example.com, another@example.com" required autocomplete="off" {{ $v['to'] === '' ? 'autofocus' : '' }}>
          @error('to')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2" x-show="copies" x-cloak>
          <div>
            <label for="mail-cc" class="{{ $lbl }}">Cc</label>
            <input id="mail-cc" name="cc" type="text" value="{{ $v['cc'] }}" class="{{ $input }}" autocomplete="off">
            @error('cc')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
          </div>
          <div>
            <label for="mail-bcc" class="{{ $lbl }}">Bcc</label>
            <input id="mail-bcc" name="bcc" type="text" value="{{ $v['bcc'] }}" class="{{ $input }}" autocomplete="off">
            @error('bcc')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
          </div>
        </div>
        <div>
          <label for="mail-subject" class="{{ $lbl }}">Subject</label>
          <input id="mail-subject" name="subject" type="text" value="{{ $v['subject'] }}" maxlength="500" class="{{ $input }}" autocomplete="off">
          @error('subject')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        </div>
        <div>
          <label for="mail-body" class="{{ $lbl }}">Message</label>
          <textarea id="mail-body" name="body" rows="14" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 font-normal text-theme-sm leading-relaxed text-gray-800 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    {{ $v['to'] !== '' ? 'autofocus' : '' }} x-init="if ($el.hasAttribute('autofocus')) { $el.setSelectionRange(0, 0); $el.scrollTop = 0 }">{{ $v['body'] }}</textarea>
          @error('body')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        </div>

        @if ($v['forward_attachments'])
          <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
            <input type="hidden" name="include_attachments" value="0">
            <input type="checkbox" name="include_attachments" value="1" checked class="mt-0.5 size-4 rounded border-gray-300 text-brand-500">
            <span class="text-theme-sm text-gray-700 dark:text-gray-300">
              Include the original {{ \Illuminate\Support\Str::plural('attachment', count($v['forward_attachments'])) }}:
              <span class="text-gray-500 dark:text-gray-400">{{ collect($v['forward_attachments'])->map(fn ($a) => $a['name'] . ' (' . $size($a['size']) . ')')->implode(', ') }}</span>
            </span>
          </label>
        @endif

        <div>
          <label for="mail-files" class="{{ $lbl }}">Attach files</label>
          <input id="mail-files" name="attachments[]" type="file" multiple
                 class="block w-full text-theme-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:text-theme-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:text-gray-400 dark:file:bg-white/5 dark:file:text-gray-300">
          <p class="mt-1.5 text-theme-xs text-gray-400">Up to 10 files, {{ round(config('staffmail.max_attachment_kb') / 1024) }} MB each, {{ round(config('staffmail.max_total_attachment_kb') / 1024) }} MB in all.@if ($errors->any()) Pick any files again: they are not kept when a message comes back.@endif</p>
          @error('attachments')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
          @error('attachments.*')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
          <button type="submit" :disabled="sending" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:opacity-60">
            <x-ui.icon name="send" class="size-4" /> <span x-text="sending ? 'Sending…' : 'Send'">Send</span>
          </button>
          <a href="{{ $back }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Discard</a>
        </div>
      </form>
    </section>
  </div>
@endsection
