{{-- Admin · users & roles: roles x permissions from the Spatie tables (read-only), users per role, and one
     guarded change (give / remove a permission) for holders of "manage roles". super-admin is never edited here. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6';
  $has = $roles->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('name')->flip()]);
  $immutable = \App\Http\Controllers\Staff\SetupController::IMMUTABLE_ROLES;
  $editable = $roles->reject(fn ($r) => in_array($r->name, $immutable, true));
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Roles & permissions', 'sub' => $roles->count() . ' roles · ' . $permissions->count() . ' permissions. Assign a role to a person on their user page.'])

  <section class="{{ $card }} mb-6">
    <h2 class="mb-3 text-base font-semibold text-gray-800 dark:text-white/90">People per role</h2>
    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-theme-sm sm:grid-cols-2 xl:grid-cols-3">
      @foreach ($roles as $role)
        @php $people = $members[$role->id] ?? collect(); @endphp
        <div class="min-w-0">
          <dt class="font-medium text-gray-800 dark:text-white/90">{{ $role->name }} <span class="font-outfit text-gray-500">· {{ $people->count() }}</span></dt>
          <dd class="truncate text-theme-xs text-gray-500">{{ $people->count() === 0 ? 'Nobody' : ($people->count() <= 4 ? $people->pluck('name')->implode(', ') : $people->take(3)->pluck('name')->implode(', ') . ' and ' . ($people->count() - 3) . ' more') }}</dd>
        </div>
      @endforeach
    </dl>
  </section>

  @can('manage roles')
    <section class="{{ $card }} mb-6">
      <h2 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90">Change a permission</h2>
      <p class="mb-4 text-theme-sm text-gray-500">Takes effect at once for everyone in the role and is written to the audit log. The super-admin role cannot be changed here.</p>
      <form method="POST" id="role-perm-form" action="{{ route('staff.roles.permissions', $editable->first() ?? 0) }}"
        data-base="{{ url('staff/roles') }}" class="grid grid-cols-1 items-end gap-4 md:grid-cols-4"
        onsubmit="return confirm('Change this permission for everyone in the role?')">
        @csrf
        <x-staff.field name="role_id" label="Role" type="select" :options="$editable->pluck('name', 'id')->all()" id="f-role"
          onchange="const f=this.form; f.action=f.dataset.base + '/' + this.value + '/permissions'" />
        <x-staff.field name="permission" label="Permission" type="select" :options="$permissions->pluck('name', 'name')->all()" required />
        <x-staff.field name="change" label="Change" type="select" :options="['give' => 'Give', 'remove' => 'Remove']" />
        <button class="h-11 rounded-lg bg-brand-500 px-5 text-theme-sm font-medium text-white hover:bg-brand-600">Apply</button>
      </form>
    </section>
  @else
    <x-staff.alert type="info" class="mb-6" title="Read-only">Changing role permissions needs “manage roles” (super-admin only today).</x-staff.alert>
  @endcan

  <x-staff.table caption="Roles by permission" class="!min-w-[900px]">
    <x-slot:head>
      <tr>
        <th scope="col" class="sticky left-0 bg-gray-50 px-5 py-3 text-left font-medium dark:bg-gray-900">Permission</th>
        @foreach ($roles as $role)
          <th scope="col" class="whitespace-nowrap px-2 py-3 text-center font-medium">{{ $role->name }}</th>
        @endforeach
      </tr>
    </x-slot:head>
    @foreach ($groups as $subject => $perms)
      <tr><td colspan="{{ $roles->count() + 1 }}" class="bg-gray-50/60 !py-2 text-theme-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/[0.02]">{{ $subject }}</td></tr>
      @foreach ($perms as $perm)
        <tr>
          <th scope="row" class="sticky left-0 whitespace-nowrap border-t border-gray-100 bg-white px-5 py-2 text-left font-normal dark:border-gray-800 dark:bg-gray-900">{{ $perm->name }}</th>
          @foreach ($roles as $role)
            <td class="!px-2 !py-2 text-center">
              @if (isset($has[$role->id][$perm->name]))
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-az-sage-d" title="{{ $role->name }}: {{ $perm->name }}"></span><span class="sr-only">yes</span>
              @else
                <span class="text-gray-300 dark:text-gray-700" aria-hidden="true">·</span><span class="sr-only">no</span>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    @endforeach
  </x-staff.table>

  <x-staff.placeholder class="mt-6" dependency="approval limits per role" title="Approval limits">
    Amount limits per role (who may approve how much) need their own table and checks in the approval path (Stage 4).
  </x-staff.placeholder>
@endsection
