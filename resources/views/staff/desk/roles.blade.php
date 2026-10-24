{{-- Admin · users & roles: roles x permissions from the Spatie tables (read-only), users per role, and one
     guarded change (give / remove a permission) for holders of "manage roles". super-admin is never edited here. --}}
@extends('layouts.app')

@php
  $card = 'rounded-2xl border border-gray-200 bg-white px-4 py-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6';
  $has = $roles->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('name')->flip()]);
  $immutable = \App\Http\Controllers\Staff\SetupController::IMMUTABLE_ROLES;
  $editable = $roles->reject(fn ($r) => in_array($r->name, $immutable, true));
@endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Roles & permissions', 'sub' => $roles->count() . ' roles · ' . $permissions->count() . ' permissions. Assign a role to a person on their user page.'])

  <div class="mb-6">
    <x-staff.table title="People per role" caption="People per role" :headers="['Role', 'People', ['Permissions', 'num']]">
      @foreach ($roles as $role)
        @php $people = $members[$role->id] ?? collect(); @endphp
        <tr>
          <td data-sort-value="{{ $role->name }}">
            <div class="flex items-center gap-3">
              <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400"><x-ui.icon name="shield" class="size-5" /></span>
              <div><span class="block font-medium text-gray-800 dark:text-white/90">{{ \Illuminate\Support\Str::headline($role->name) }}</span>
                <span class="block font-mono text-theme-xs text-gray-500 dark:text-gray-400">{{ $role->name }}@if (in_array($role->name, $immutable, true)) · locked @endif</span></div>
            </div>
          </td>
          <td data-sort-value="{{ $people->count() }}" class="!whitespace-normal">
            @if ($people->isEmpty())
              <span class="text-theme-xs text-gray-400">Nobody</span>
            @else
              <div class="flex items-center gap-3">
                <div class="flex -space-x-2">
                  @foreach ($people->take(4) as $u)
                    @php $ini = collect(preg_split('/\s+/', trim((string) $u->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''); @endphp
                    <span class="flex size-8 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-brand-50 text-[10px] font-semibold text-brand-600 dark:border-gray-900 dark:bg-brand-500/15 dark:text-brand-400" title="{{ $u->name }}" data-user-peek="{{ $u->id }}">{{ $ini }}</span>
                  @endforeach
                </div>
                <span class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $people->count() }} · {{ $people->take(2)->pluck('name')->implode(', ') }}{{ $people->count() > 2 ? ' and ' . ($people->count() - 2) . ' more' : '' }}</span>
              </div>
            @endif
          </td>
          <td class="num" data-sort-value="{{ $role->permissions->count() }}"><x-staff.pill tone="brand">{{ $role->permissions->count() }} of {{ $permissions->count() }}</x-staff.pill></td>
        </tr>
      @endforeach
    </x-staff.table>
  </div>

  @can('manage roles')
    <section class="{{ $card }} mb-6">
      <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Change a permission</h2>
      <p class="mb-4 text-theme-sm text-gray-500">Takes effect at once for everyone in the role and is written to the audit log. The super-admin role cannot be changed here.</p>
      <form method="POST" id="role-perm-form" action="{{ route('staff.roles.permissions', $editable->first() ?? 0) }}"
        data-base="{{ url('staff/roles') }}" class="grid grid-cols-1 items-end gap-4 md:grid-cols-4"
        data-confirm="Change this permission for everyone in the role?">
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

  <x-staff.table title="Permissions by role" caption="Roles by permission" class="!min-w-[900px]" :list="false">
    <x-slot:head>
      <tr>
        <th scope="col" class="sticky left-0 z-10 bg-white py-3 pr-4 text-left text-theme-xs font-medium text-gray-500 dark:bg-gray-900 dark:text-gray-400">Permission</th>
        @foreach ($roles as $role)
          <th scope="col" class="whitespace-nowrap px-2 py-3 text-center text-theme-xs font-medium text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($role->name) }}</th>
        @endforeach
      </tr>
    </x-slot:head>
    @foreach ($groups as $subject => $perms)
      <tr><td colspan="{{ $roles->count() + 1 }}" class="!py-2 text-theme-xs font-semibold text-gray-800 dark:text-white/90">{{ ucfirst($subject) }}</td></tr>
      @foreach ($perms as $perm)
        <tr>
          <th scope="row" class="sticky left-0 z-10 whitespace-nowrap bg-white py-2 pl-3 pr-4 text-left text-theme-sm font-normal text-gray-600 dark:bg-gray-900 dark:text-gray-400">{{ $perm->name }}</th>
          @foreach ($roles as $role)
            <td class="!px-2 !py-2 text-center">
              @if (isset($has[$role->id][$perm->name]))
                <span class="inline-flex size-5 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500" title="{{ $role->name }}: {{ $perm->name }}"><x-ui.icon name="check" class="size-3" /></span><span class="sr-only">yes</span>
              @else
                <span class="text-gray-300 dark:text-gray-700" aria-hidden="true">·</span><span class="sr-only">no</span>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    @endforeach
  </x-staff.table>

  @php $limits = \Illuminate\Support\Facades\DB::table('approval_limits')->orderBy('role')->get(); @endphp
  <x-staff.alert type="info" class="mt-6" title="Approval limits">
    @if ($limits->isEmpty())
      No limits set: anyone with "approve loans" approves any amount alone.
    @else
      Approving alone up to:
      {{ $limits->map(fn ($l) => $l->role . ' KES ' . number_format((float) $l->max_amount))->implode(' · ') }}.
      Above that a second person approves{{ config('lending.approvals.enabled') ? '' : ' once second sign-offs are switched on' }}.
    @endif
  </x-staff.alert>
@endsection
