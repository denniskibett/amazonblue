@extends('layouts.app')

@section('content')
<div>
    <x-ui.page-header title="KYC Reviews" sub="Borrowers verify themselves; approve them after calling their next of kin and guarantor." />
    <x-ui.page-metrics page="kyc-reviews" />

    {{-- Alpine tablines (owner, 16 Nov 2026): all three lists are on the page, so a tab opens at once; ?status= still picks the first one --}}
    @php
      $tabs = collect($statuses)->map(fn ($label, $key) => [$label, $counts[$key] ?? 0])->all();
    @endphp
    <x-staff.tabs :tabs="$tabs" :active="$status">
      @foreach ($statuses as $key => $label)
        @php $when = $key === 'submitted' ? 'Submitted' : 'Reviewed'; @endphp
        <x-staff.tab :name="$key">
          <x-staff.table :title="$label" caption="KYC {{ strtolower($label) }}" :headers="['Borrower', 'Phone', 'ID number', $when, '']">
            @forelse ($lists[$key] as $u)
              @php $at = $key === 'submitted' ? $u->kyc_submitted_at : $u->kyc_reviewed_at; @endphp
              <tr>
                <td data-sort-value="{{ $u->name }}"><x-staff.person :user="$u" :sub="$u->email" /></td>
                <td>{{ $u->phone ?: '—' }}</td>
                <td>{{ $u->id_number ?? '—' }}</td>
                <td data-sort-value="{{ $at?->timestamp }}">
                  <span class="text-theme-sm text-gray-700 dark:text-gray-300">{{ $at?->diffForHumans(short: true) ?? '—' }}</span>
                  @if ($at)<span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $at->format('j M, H:i') }}</span>@endif
                </td>
                <td class="text-right" data-actions>
                  <a href="{{ route('kyc-reviews.show', $u) }}"
                     class="inline-flex items-center rounded-lg {{ $key === 'submitted' ? 'bg-brand-500 text-white hover:bg-brand-600' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }} px-3 py-2 text-theme-xs font-medium">
                    {{ $key === 'submitted' ? 'Review' : 'View' }}
                  </a>
                </td>
              </tr>
            @empty
              <tr data-table-skip><td colspan="5"><x-staff.empty title="Nothing here" message="Nothing {{ strtolower($label) }} right now." /></td></tr>
            @endforelse
          </x-staff.table>
        </x-staff.tab>
      @endforeach
    </x-staff.tabs>
</div>

@include('partials.modal.flash-alert')
@endsection
