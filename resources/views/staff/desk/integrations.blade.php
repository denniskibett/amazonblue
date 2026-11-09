{{-- Integrations (owner, 3 Nov 2026): the services the app talks to, TailAdmin cards. Secrets are masked and never
     sent to the browser; a blank secret field keeps the saved one. Saved values override .env (Support\Integrations). --}}
@extends('layouts.app')

@php
  $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
  $btn = 'inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/5';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Integrations', 'sub' => 'The services the system talks to. Values saved here override the server .env; secrets are stored encrypted and never shown again.'])

  @unless ($imported)
    <div class="mb-6 rounded-xl border border-warning-500/30 bg-warning-50 p-4 text-sm text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
      Nothing is saved yet: the values below come from .env. Run <code class="font-mono">php artisan integrations:import-env</code> on the server to copy them in.
    </div>
  @endunless

  <div x-data="{ settings: null, details: null }">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
      @foreach ($cards as $c)
        <article class="flex flex-col rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
          <div class="relative flex-1 p-5 pb-7">
            <div class="flex h-10 items-center"><img src="{{ $c['logo'] }}" alt="{{ $c['name'] }} logo" class="max-h-10 max-w-[140px] object-contain"></div>
            <span class="absolute right-5 top-5 rounded-full bg-gray-100 px-2.5 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-400">{{ $c['category'] }}</span>
            <h3 class="mb-2 mt-5 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $c['name'] }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $c['description'] }}</p>
            <p class="mt-3 text-theme-xs text-gray-500 dark:text-gray-400">
              {{ $c['configured'] }} of {{ $c['fields']->count() }} set
              @if ($c['updated']) · saved {{ $c['updated'] }} @endif
            </p>
          </div>
          <div class="flex items-center justify-between gap-3 border-t border-gray-200 p-5 dark:border-gray-800">
            <div class="flex items-center gap-3">
              @if ($c['managed'])
                <a href="{{ $c['managed'] }}" class="{{ $btn }}">Change on System</a>
              @else
                <button type="button" @click="settings = @js($c['slug'])" class="{{ $btn }} size-11 !px-0" aria-label="Settings for {{ $c['name'] }}">
                  <x-ui.icon name="settings" class="size-5" />
                </button>
              @endif
              <button type="button" @click="details = @js($c['slug'])" class="{{ $btn }}">Details</button>
            </div>
            @unless ($c['managed'])
              <form method="POST" action="{{ route('staff.integrations.toggle', $c['slug']) }}">
                @csrf
                <button type="submit" role="switch" aria-checked="{{ $c['enabled'] ? 'true' : 'false' }}" aria-label="Switch {{ $c['name'] }} {{ $c['enabled'] ? 'off' : 'on' }}"
                  data-confirm="{{ $c['enabled'] ? 'Switch ' . $c['name'] . ' off? Its saved values stop applying' . ($c['slug'] === 'mpesa' ? ' and M-Pesa payments stop.' : ' and .env is used.') : 'Switch ' . $c['name'] . ' on?' }}"
                  class="relative inline-flex h-6 w-[52px] shrink-0 items-center rounded-full p-0.5 transition-colors {{ $c['enabled'] ? 'bg-brand-500 hover:bg-brand-600' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700' }}">
                  <span class="block h-5 w-[30px] rounded-full bg-white shadow-theme-sm transition-transform {{ $c['enabled'] ? 'translate-x-[18px]' : '' }}"></span>
                </button>
              </form>
            @endunless
          </div>
        </article>
      @endforeach
    </div>

    @foreach ($cards as $c)
      {{-- details --}}
      <div x-cloak x-show="details === @js($c['slug'])" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="details = null">
        <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="details = null"></div>
        <div class="relative w-full max-w-[600px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
          <h4 class="mb-1 text-title-xs font-semibold text-gray-800 dark:text-white/90">{{ $c['name'] }} details</h4>
          <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">What is in force now. Secrets show their last four characters only.</p>
          <ul>
            @foreach ($c['fields'] as $f)
              <li class="flex justify-between gap-4 border-b border-gray-100 py-2.5 dark:border-gray-800">
                <span class="w-1/2 text-sm text-gray-500 dark:text-gray-400">{{ $f['label'] }}<span class="block font-mono text-theme-xs text-gray-400">{{ $f['env'] }}</span></span>
                <span class="w-1/2 break-words text-sm text-gray-700 dark:text-gray-300">
                  {{ $f['type'] === 'bool' ? ($f['value'] === '1' ? 'On' : 'Off') : $f['shown'] }}
                  @if ($f['set'] && ! $c['managed'])<span class="block text-theme-xs text-gray-400">from {{ $f['source'] }}</span>@endif
                </span>
              </li>
            @endforeach
          </ul>
          <div class="mt-8 flex justify-end"><button type="button" @click="details = null" class="{{ $btn }}">Close</button></div>
        </div>
      </div>

      {{-- settings --}}
      @unless ($c['managed'])
        <div x-cloak x-show="settings === @js($c['slug'])" class="fixed inset-0 z-99999 flex items-center justify-center overflow-y-auto p-5" @keydown.escape.window="settings = null">
          <div class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]" @click="settings = null"></div>
          <div class="relative my-auto w-full max-w-[600px] rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10">
            <h4 class="mb-1 text-title-xs font-semibold text-gray-800 dark:text-white/90">{{ $c['name'] }} settings</h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Leave a secret blank to keep the saved one. Changes apply on the next page load and are recorded in the audit log (names only, never values).</p>
            <form method="POST" action="{{ route('staff.integrations.update', $c['slug']) }}" class="space-y-4">
              @csrf
              @foreach ($c['fields'] as $f)
                <div>
                  <label for="f-{{ $c['slug'] }}-{{ $f['env'] }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $f['label'] }} <span class="font-mono text-theme-xs font-normal text-gray-400">{{ $f['env'] }}</span></label>
                  @if ($f['type'] === 'bool')
                    <select id="f-{{ $c['slug'] }}-{{ $f['env'] }}" name="fields[{{ $f['env'] }}]" class="{{ $input }}">
                      <option value="1" @selected($f['value'] === '1')>On</option>
                      <option value="0" @selected($f['value'] !== '1')>Off</option>
                    </select>
                  @elseif ($f['type'] === 'select')
                    <select id="f-{{ $c['slug'] }}-{{ $f['env'] }}" name="fields[{{ $f['env'] }}]" class="{{ $input }}">
                      @foreach ($f['options'] as $o)<option value="{{ $o }}" @selected($f['value'] === $o)>{{ $o }}</option>@endforeach
                    </select>
                  @elseif ($f['secret'])
                    <input id="f-{{ $c['slug'] }}-{{ $f['env'] }}" type="password" name="fields[{{ $f['env'] }}]" autocomplete="new-password" placeholder="{{ $f['set'] ? 'Saved (' . $f['shown'] . '). Type to replace' : 'Not set' }}" class="{{ $input }}">
                  @else
                    <input id="f-{{ $c['slug'] }}-{{ $f['env'] }}" type="text" name="fields[{{ $f['env'] }}]" value="{{ $f['value'] }}" class="{{ $input }}">
                  @endif
                </div>
              @endforeach
              <div class="mt-8 flex items-center justify-between gap-3">
                <button type="button" @click="settings = null" class="{{ $btn }} w-full">Cancel</button>
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Save changes</button>
              </div>
            </form>
          </div>
        </div>
      @endunless
    @endforeach
  </div>
@endsection
