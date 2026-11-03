{{-- AML › Sanctions lists (owner, 23 Oct 2026): upload the UN consolidated list (XML) or any CSV with a name column. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6'; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Sanctions lists', 'sub' => 'Names a business, director or partner is matched against before approval. Uploading a list replaces the names stored under that list name.', 'metrics' => false])

  @unless ($enabled)
    <x-staff.alert type="info" class="mb-6" title="Screening is switched off">Lists can be uploaded now. Approvals start requiring a clear screening once LENDING_AML_SCREENING=true.</x-staff.alert>
  @endunless

  @php
    $input = 'h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $total = (int) $lists->sum('names');
  @endphp
  <x-staff.tabs :tabs="['names' => ['Names', number_format($total)], 'lists' => ['Lists on file', $lists->count()]] + (auth()->user()->can('access system settings') ? ['upload' => 'Upload a list'] : [])">
    <x-staff.tab name="names">
      <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('aml.lists') }}" class="flex flex-col gap-3 border-b border-gray-100 px-4 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Names on file</h3>
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Showing {{ $names->firstItem() ?? 0 }} to {{ $names->lastItem() ?? 0 }} of {{ number_format($names->total()) }}</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="tab" value="names">
            <input name="q" value="{{ $q }}" placeholder="Name, reference or country" aria-label="Search names" class="{{ $input }} w-64">
            <select name="kind" aria-label="Kind" class="{{ $input }}"><option value="">People and entities</option><option value="individual" @selected($kind === 'individual')>People</option><option value="entity" @selected($kind === 'entity')>Entities</option></select>
            @if ($lists->count() > 1)
              <select name="list" aria-label="List" class="{{ $input }}"><option value="">Every list</option>@foreach ($lists as $l)<option value="{{ $l->list_name }}" @selected($list === $l->list_name)>{{ $l->list_name }}</option>@endforeach</select>
            @endif
            <button class="h-10 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white hover:bg-brand-600">Search</button>
          </div>
        </form>
        @if ($names->isEmpty())
          <x-staff.empty :title="$total ? 'No names match' : 'No lists yet'" :message="$total ? 'Try part of the name, or the list reference.' : 'Upload the UN consolidated list to start.'" />
        @else
          <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-theme-sm text-gray-700 dark:text-gray-300">
              <thead class="border-b border-gray-100 text-theme-xs text-gray-500 dark:border-gray-800 dark:text-gray-400"><tr>
                <th class="py-3 pl-6 pr-4 font-medium">Name</th><th class="py-3 pr-4 font-medium">Kind</th><th class="py-3 pr-4 font-medium">Born</th><th class="py-3 pr-4 font-medium">Country</th><th class="py-3 pr-4 font-medium">Reference</th><th class="py-3 pr-6 font-medium">List</th>
              </tr></thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($names as $n)
                  <tr>
                    <td class="py-3 pl-6 pr-4 font-medium text-gray-800 dark:text-white/90">{{ $n->name }}</td>
                    <td class="py-3 pr-4"><x-staff.pill :tone="$n->kind === 'entity' ? 'purple' : 'gray'">{{ $n->kind === 'entity' ? 'Entity' : 'Person' }}</x-staff.pill></td>
                    <td class="py-3 pr-4">{{ $n->dob ?: '—' }}</td>
                    <td class="py-3 pr-4">{{ $n->country ?: '—' }}</td>
                    <td class="py-3 pr-4 font-mono text-theme-xs">{{ $n->reference ?: '—' }}</td>
                    <td class="py-3 pr-6 text-gray-500">{{ $n->list_name }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if ($names->hasPages())<div class="border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-6">{{ $names->links() }}</div>@endif
        @endif
      </section>
    </x-staff.tab>

    <x-staff.tab name="lists">
    <section class="{{ $card }}">
      <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">On file</h2>
      @if ($lists->isEmpty())
        <x-staff.empty title="No lists yet" message="Upload the UN consolidated list to start. Without a list, screening only records the checklist." />
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-left text-theme-sm">
            <thead class="text-theme-xs uppercase tracking-wide text-gray-500"><tr><th class="py-2 pr-4">List</th><th class="py-2 pr-4 text-right">Names</th><th class="py-2 pr-4">Uploaded</th><th></th></tr></thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @foreach ($lists as $l)
                <tr>
                  <td class="py-3 pr-4 font-medium text-gray-900 dark:text-white">{{ $l->list_name }}</td>
                  <td class="py-3 pr-4 text-right tabular-nums">{{ number_format($l->names) }}</td>
                  <td class="py-3 pr-4 text-gray-500">{{ \Illuminate\Support\Carbon::parse($l->uploaded_at)->format('j M Y, H:i') }}</td>
                  <td class="py-3 text-right">
                    @can('access system settings')
                      <form method="POST" action="{{ route('aml.lists.remove') }}" onsubmit="return confirm('Remove {{ e($l->list_name) }}?')">
                        @csrf @method('DELETE')<input type="hidden" name="list_name" value="{{ $l->list_name }}">
                        <button class="text-theme-xs font-medium text-error-600 hover:underline">Remove</button>
                      </form>
                    @endcan
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>

    </x-staff.tab>

    @can('access system settings')
      <x-staff.tab name="upload">
      <section class="{{ $card }} max-w-2xl">
        <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Upload a list</h2>
        <p class="mb-4 text-theme-sm text-gray-500">The UN list: scsanctions.un.org › Consolidated list › XML. Other lists: a CSV with a <code>name</code> column; <code>aliases</code> (separated by ;), <code>dob</code>, <code>country</code>, <code>reference</code> and <code>type</code> are read when present.</p>
        <form method="POST" action="{{ route('aml.lists.upload') }}" enctype="multipart/form-data" class="space-y-4">
          @csrf
          <x-staff.field label="List name" name="list_name" :value="old('list_name', 'UN consolidated')" required />
          <div>
            <label for="aml-file" class="mb-1.5 block text-theme-sm font-medium text-gray-700 dark:text-gray-300">File (XML or CSV, up to 50 MB)</label>
            <input id="aml-file" type="file" name="file" accept=".xml,.csv,.txt" required class="block w-full text-theme-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700 dark:text-gray-300">
            @error('file')<p class="mt-1 text-theme-xs text-error-600">{{ $message }}</p>@enderror
          </div>
          <button class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-brand-600">Upload and replace</button>
        </form>
      </section>
      </x-staff.tab>
    @endcan
  </x-staff.tabs>
@endsection
