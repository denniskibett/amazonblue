{{-- AML › Sanctions lists (owner, 23 Oct 2026): upload the UN consolidated list (XML) or any CSV with a name column. --}}
@extends('layouts.app')

@php $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6'; @endphp

@section('content')
  @include('staff.desk._header', ['title' => 'Sanctions lists', 'sub' => 'Names a business, director or partner is matched against before approval. Uploading a list replaces the names stored under that list name.', 'metrics' => false])

  @unless ($enabled)
    <x-staff.alert type="info" class="mb-6" title="Screening is switched off">Lists can be uploaded now. Approvals start requiring a clear screening once LENDING_AML_SCREENING=true.</x-staff.alert>
  @endunless

  <div class="grid gap-6 lg:grid-cols-5">
    <section class="{{ $card }} lg:col-span-3">
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

    @can('access system settings')
      <section class="{{ $card }} lg:col-span-2">
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
    @endcan
  </div>
@endsection
