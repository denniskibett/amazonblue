@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">Business reviews</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Verified borrowers add their businesses; check the documents, directors and shareholders, then approve or send back.
        </p>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($statuses as $key => $label)
            <a href="{{ route('business-reviews.index', ['status' => $key]) }}"
               class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors
                      {{ $status === $key ? 'bg-brand-500 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                {{ $label }}
                <span class="rounded-full px-2 py-0.5 text-xs {{ $status === $key ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700' }}">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    @if ($businesses->isEmpty())
        <x-staff.empty title="Nothing {{ strtolower($statuses[$status]) }}" message="Businesses appear here when borrowers submit them from their account." />
    @else
        <x-staff.table :headers="['Business', 'Registration', 'Applicant', 'People', $status === 'submitted' ? 'Submitted' : ($status === 'draft' ? 'Last saved' : 'Reviewed'), '']" caption="Businesses {{ strtolower($statuses[$status]) }}">
            @foreach ($businesses as $b)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <td>
                        <p class="font-medium text-gray-800 dark:text-white/90">{{ $b->legal_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $b->reference() }} · {{ \App\Models\Business::REGISTRATION_TYPES[$b->registration_type] ?? '—' }}</p>
                    </td>
                    <td class="font-outfit">{{ $b->registration_number ?? '—' }}<br><span class="text-xs text-gray-500">{{ $b->kra_pin }}</span></td>
                    <td>{{ $b->owner?->name ?? '—' }}<br><span class="text-xs text-gray-500">{{ $b->owner?->phone }}</span></td>
                    <td class="num">{{ $b->members_count }}</td>
                    <td>{{ optional(match ($status) { 'submitted' => $b->submitted_at, 'draft' => $b->updated_at, default => $b->reviewed_at })->diffForHumans() ?? '—' }}</td>
                    <td class="text-right">
                        <a href="{{ route('business-reviews.show', $b) }}"
                           class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600">
                            {{ $status === 'submitted' ? 'Review' : 'View' }}
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-staff.table>
        <div class="mt-4">{{ $businesses->links() }}</div>
    @endif
</div>

@include('partials.modal.flash-alert')
@endsection
