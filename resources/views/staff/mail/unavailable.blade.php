{{-- The mail server could not be reached or answered with an error. --}}
@extends('layouts.app')

@section('content')
  @include('staff.desk._header', ['title' => 'Mail', 'sub' => auth()->user()->email, 'metrics' => false])

  <div class="mx-auto max-w-lg rounded-2xl border border-gray-200 bg-white p-6 text-center dark:border-gray-800 dark:bg-white/[0.03] sm:p-8">
    <span class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400"><x-ui.icon name="alert" class="size-6" /></span>
    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">The mail server is not answering</h2>
    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Your mail is safe on the server. Try again in a minute.</p>
    <a href="{{ route('staff.mail.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">
      <x-ui.icon name="refresh" class="size-4" /> Try again
    </a>
  </div>
@endsection
