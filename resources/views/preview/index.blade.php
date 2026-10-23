{{-- Sysadmin "view as" (owner, 23 Oct 2026): pick a staff role, a new-borrower stand-in, or a customer's account. --}}
@extends('layouts.app')

@php
  $notes = \App\Support\Preview::ROLE_NOTES;
  $card = 'rounded-2xl border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6';
  $btn = 'inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600';
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'View as', 'sub' => 'See the app the way each role and customer sees it. While previewing, everything works but nothing is saved, emailed, texted or paid.', 'metrics' => false])

  @if ($active)
    <x-staff.alert type="info" class="mb-6" title="A preview is on">
      You are previewing as {{ \App\Support\Preview::label($active) }}.
      <form method="POST" action="{{ route('preview.stop') }}" class="mt-2" data-no-swap>@csrf<button class="{{ $btn }}">Exit preview</button></form>
    </x-staff.alert>
  @endif

  <section class="{{ $card }} mb-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Staff roles</h3>
    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">A stand-in staff member with exactly that role's permissions, on the real loan book. The stand-in is made for each page and never saved.</p>
    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      @foreach ($roles as $role)
        <form method="POST" action="{{ route('preview.start') }}" class="flex flex-col rounded-xl border border-gray-200 p-4 dark:border-gray-800" data-no-swap>
          @csrf
          <input type="hidden" name="kind" value="role"><input type="hidden" name="role" value="{{ $role }}">
          <p class="flex items-center gap-2 font-medium text-gray-800 dark:text-white/90"><x-ui.icon name="shield" class="size-4 text-purple-500" /> {{ \Illuminate\Support\Str::headline($role) }}</p>
          <p class="mt-1 flex-1 text-theme-xs text-gray-500 dark:text-gray-400">{{ $notes[$role] ?? '' }}</p>
          <button class="{{ $btn }} mt-3 self-start">View as {{ \Illuminate\Support\Str::headline($role) }}</button>
        </form>
      @endforeach
    </div>
  </section>

  <section class="{{ $card }} mb-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Customers</h3>
    <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">A brand-new borrower (stand-in), or a real borrower's, partner's or broker's own account exactly as they see it. Their data is shown but cannot be changed: every action is undone at the end of each page.</p>
    <form method="POST" action="{{ route('preview.start') }}" class="mt-4" data-no-swap>
      @csrf <input type="hidden" name="kind" value="new">
      <button class="{{ $btn }}">View as a new borrower (stand-in, no KYC, no loans)</button>
    </form>
  </section>

  <div id="pv-tools" class="hidden">
    <form method="GET" action="{{ route('preview.index') }}" class="flex items-center gap-2">
      <input name="q" value="{{ $q }}" placeholder="Find by name, email or phone" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" aria-label="Find a customer">
      <button class="h-10 rounded-lg border border-gray-300 px-3 text-theme-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Find</button>
    </form>
  </div>
  <x-staff.table title="Customer accounts" caption="Customer accounts to preview" data-table-buttons="#pv-tools > *" :headers="['Customer', 'Role', ['Loans', 'num', 'mobile'], '']">
    @forelse ($customers as $c)
      <tr>
        <td data-sort-value="{{ $c->name }}"><x-staff.person :name="$c->name" :id="$c->id" :sub="$c->email ?: $c->phone" /></td>
        <td data-filter-value="{{ ucfirst($c->roles->first()->name ?? '') }}"><x-staff.pill tone="gray">{{ ucfirst($c->roles->pluck('name')->implode(', ')) }}</x-staff.pill></td>
        <td class="num">{{ $c->loans_count }}</td>
        <td class="text-right">
          <form method="POST" action="{{ route('preview.start') }}" data-no-swap>@csrf<input type="hidden" name="kind" value="user"><input type="hidden" name="user_id" value="{{ $c->id }}">
            <button class="{{ $btn }}">View their account</button></form>
        </td>
      </tr>
    @empty
      <tr data-table-skip><td colspan="4"><x-staff.empty title="No customers found" /></td></tr>
    @endforelse
  </x-staff.table>
@endsection
