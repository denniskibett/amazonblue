{{-- Staff mail: Compose and the mailbox folders (TailAdmin mailbox). Needs $folders and $folder. --}}
@php
  $icons = ['inbox' => 'inbox', 'sent' => 'send', 'drafts' => 'file', 'spam' => 'alert', 'trash' => 'trash', 'archive' => 'archive'];
@endphp
<aside class="w-full rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] xl:w-[280px] xl:shrink-0">
  <a href="{{ route('staff.mail.compose') }}"
     class="mb-5 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
    <x-ui.icon name="edit" class="size-5" /> Compose
  </a>
  <p class="mb-2 px-3 text-theme-xs uppercase tracking-wide text-gray-400">Mailbox</p>
  <nav class="flex flex-col gap-1">
    @foreach ($folders as $key => $f)
      @php $on = $key === $folder; $n = $key === 'drafts' ? $f['total'] : $f['unread']; @endphp
      <a href="{{ route('staff.mail.index', $key === 'inbox' ? [] : ['folder' => $key]) }}"
         @class(['group flex items-center justify-between rounded-lg px-3 py-2.5 text-theme-sm font-medium',
                 'bg-brand-50 text-brand-600 dark:bg-brand-500/[0.12] dark:text-brand-400' => $on,
                 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $on])>
        <span class="flex items-center gap-3">
          <x-ui.icon :name="$icons[$key]" class="size-5 {{ $on ? 'text-brand-500 dark:text-brand-400' : 'text-gray-500 dark:text-gray-400' }}" />
          {{ $f['label'] }}
        </span>
        @if ($n > 0)<span class="text-theme-xs font-semibold">{{ number_format($n) }}</span>@endif
      </a>
    @endforeach
  </nav>
  <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-800">
    <p class="truncate px-3 text-theme-xs text-gray-500 dark:text-gray-400" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</p>
    <form method="POST" action="{{ route('staff.mail.lock') }}" class="mt-2">
      @csrf
      <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5">
        <x-ui.icon name="lock" class="size-5 text-gray-500 dark:text-gray-400" /> Lock mailbox
      </button>
    </form>
  </div>
</aside>
