{{-- Portal login for the partner (owner, 1 Nov 2026): without one they cannot see investments, payouts or loans.
     Link an existing user (email or phone) or create one; a new login gets the "set your password" email. --}}
@php
  $in = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
  $lb = 'mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-400';
@endphp
<div id="portal-login" class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
  <div class="border-b border-gray-200 p-4 dark:border-gray-700">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Portal login</h3>
    @if ($partner->user)
      <p class="text-sm text-gray-500 dark:text-gray-400">{{ $partner->name }} signs in as <b>{{ $partner->user->email }}</b> and sees their balances, payouts and loans under /account/partner.</p>
    @else
      <p class="text-sm text-amber-700 dark:text-amber-400">No login yet: {{ $partner->name }} cannot see their account.</p>
    @endif
  </div>
  @if (! $partner->user)
    @can('edit partners')
      <form method="POST" action="{{ route('staff.partners.login.store', $partner) }}" class="space-y-4 p-4" x-data="{ mode: @js(old('mode', 'existing')) }">
        @csrf
        <input type="hidden" name="mode" :value="mode">
        <div class="flex gap-2 text-theme-sm">
          <button type="button" @click="mode = 'existing'" :class="mode === 'existing' ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Existing user</button>
          <button type="button" @click="mode = 'new'" :class="mode === 'new' ? 'border-brand-500 bg-brand-50 text-brand-600' : 'border-gray-300 text-gray-700 dark:border-gray-700 dark:text-gray-300'" class="rounded-lg border px-3 py-2 font-medium">Create a login</button>
        </div>
        <div x-show="mode === 'existing'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div><label for="pl-lookup" class="{{ $lb }}">User's email or phone</label><input id="pl-lookup" name="lookup" value="{{ old('lookup', $partner->email) }}" maxlength="120" class="{{ $in }}"></div>
        </div>
        <div x-show="mode === 'new'" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div><label for="pl-name" class="{{ $lb }}">Name</label><input id="pl-name" name="name" value="{{ old('name', $partner->name) }}" maxlength="120" class="{{ $in }}"></div>
          <div><label for="pl-email" class="{{ $lb }}">Email</label><input id="pl-email" name="email" type="email" value="{{ old('email', $partner->email) }}" maxlength="120" class="{{ $in }}"></div>
          <div><label for="pl-phone" class="{{ $lb }}">Phone</label><input id="pl-phone" name="phone" value="{{ old('phone', $partner->phone) }}" maxlength="30" class="{{ $in }}"></div>
          <p class="text-theme-xs text-gray-500 sm:col-span-3">They get an email to set their password, then sign in at /login.</p>
        </div>
        @if ($errors->login->any())<div class="rounded-lg bg-error-50 p-3 text-theme-sm text-error-700 dark:bg-error-500/15 dark:text-error-400">{{ $errors->login->first() }}</div>@endif
        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Link login</button>
      </form>
    @endcan
  @endif
</div>
