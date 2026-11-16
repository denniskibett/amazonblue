{{-- Unlock staff mail (owner, 16 Nov 2026): the mailbox password, once per app login. See MailCredentials. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Mail', 'sub' => 'Open your ' . $email . ' mailbox.', 'metrics' => false])

  <div class="mx-auto max-w-lg rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
    <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15"><x-ui.icon name="lock" class="size-6" /></span>
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Type your mailbox password</h2>
    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
      This is the password for <span class="font-medium text-gray-700 dark:text-gray-300">{{ $email }}</span> on the mail server, not your password for this app.
      You type it once each time you sign in. It is held encrypted only while you are signed in, and your mail is read
      straight from the mail server: nothing is copied into this app.
    </p>

    <form method="POST" action="{{ route('staff.mail.unlock.store') }}" class="mt-6 space-y-5">
      @csrf
      <div>
        <label for="mail-password" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400">Mailbox password</label>
        <input id="mail-password" name="password" type="password" required autofocus autocomplete="current-password"
               class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-theme-sm text-gray-800 focus:border-brand-300 focus:outline-none focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
        @error('password')<p class="mt-1.5 text-theme-xs text-error-500">{{ $message }}</p>@enderror
      </div>
      <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
        <x-ui.icon name="inbox" class="size-5" /> Open mailbox
      </button>
    </form>
    <p class="mt-4 text-theme-xs text-gray-400">It locks again when you sign out, close the browser, or leave mail unused for {{ config('staffmail.idle_minutes') }} minutes.</p>
  </div>
@endsection
