@extends('layouts.app')

@section('content')
<div>
    <x-ui.page-header title="KYC Reviews" sub="Borrowers verify themselves; approve them after calling their next of kin and guarantor." />
    <x-ui.page-metrics page="kyc-reviews" />

    {{-- status tabs --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($statuses as $key => $label)
            <a href="{{ route('kyc-reviews.index', ['status' => $key]) }}"
               class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors
                      {{ $status === $key ? 'bg-brand-500 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ $label }}
                <span class="rounded-full px-2 py-0.5 text-xs {{ $status === $key ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700' }}">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        @if ($users->isEmpty())
            <div class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Nothing {{ strtolower($statuses[$status]) }} right now.
            </div>
        @else
            <div class="overflow-x-auto">
                <table data-table data-table-search="Search people" class="min-w-full text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">Borrower</th>
                            <th class="px-5 py-3 font-medium">Phone</th>
                            <th class="px-5 py-3 font-medium">ID number</th>
                            <th class="px-5 py-3 font-medium">{{ $status === 'submitted' ? 'Submitted' : 'Reviewed' }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($users as $u)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-800 dark:text-white/90">{{ $u->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $u->email }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $u->phone }}</td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $u->id_number ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                    {{ optional($status === 'submitted' ? $u->kyc_submitted_at : $u->kyc_reviewed_at)->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('kyc-reviews.show', $u) }}"
                                       class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600">
                                        {{ $status === 'submitted' ? 'Review' : 'View' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@include('partials.modal.flash-alert')
@endsection
