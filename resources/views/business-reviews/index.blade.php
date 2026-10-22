@extends('layouts.app')

@section('content')
<div>
    <x-ui.page-header title="Business Reviews" sub="Verified borrowers add their businesses; check the documents, directors and shareholders, then approve or send back." />
    <x-ui.page-metrics page="business-reviews" />

    @php
        $tabCls = '-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-theme-sm font-medium';
        $tabOn = 'border-brand-500 text-brand-500 dark:border-brand-400 dark:text-brand-400';
        $tabOff = 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200';
        $whenLabel = $status === 'submitted' ? 'Submitted' : ($status === 'draft' ? 'Last saved' : 'Reviewed');
        $tone = ['submitted' => 'blue', 'approved' => 'green', 'rejected' => 'red', 'returned' => 'amber', 'draft' => 'gray'];
    @endphp
    {{-- the status tabs sit on the table card (owner, 22 Oct 2026) --}}
    <x-staff.table :title="'Businesses ' . strtolower($statuses[$status])" caption="Businesses {{ strtolower($statuses[$status]) }}"
        :headers="['Business', 'Registration · KRA PIN', 'Applicant', ['People', 'num'], $whenLabel, '']">
        <x-slot:tabs>
            @foreach ($statuses as $key => $label)
                <a href="{{ route('business-reviews.index', ['status' => $key]) }}" role="tab" aria-selected="{{ $status === $key ? 'true' : 'false' }}" class="{{ $tabCls }} {{ $status === $key ? $tabOn : $tabOff }}">
                    {{ $label }}
                    <span class="rounded-full px-2 text-theme-xs {{ $status === $key ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-gray-100 dark:bg-gray-800' }}">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </x-slot:tabs>
        @forelse ($businesses as $b)
            @php $when = match ($status) { 'submitted' => $b->submitted_at, 'draft' => $b->updated_at, default => $b->reviewed_at }; @endphp
            <tr>
                <td data-sort-value="{{ $b->legal_name }}">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500"><x-ui.icon name="building" class="size-5" /></span>
                        <div class="min-w-0">
                            <a href="{{ route('business-reviews.show', $b) }}" class="block truncate font-medium text-gray-800 hover:text-brand-500 dark:text-white/90">{{ $b->legal_name }}</a>
                            <span class="block text-theme-xs text-gray-500 dark:text-gray-400">{{ $b->reference() }} · {{ \App\Models\Business::REGISTRATION_TYPES[$b->registration_type] ?? '—' }}</span>
                        </div>
                    </div>
                </td>
                <td><span class="font-outfit text-gray-800 dark:text-white/90">{{ $b->registration_number ?? '—' }}</span><span class="block font-mono text-theme-xs text-gray-500 dark:text-gray-400">{{ $b->kra_pin ?: '—' }}</span></td>
                <td data-sort-value="{{ $b->owner?->name }}"><x-staff.person :user="$b->owner" :sub="$b->owner?->phone" /></td>
                <td class="num">{{ $b->members_count }}</td>
                <td data-sort-value="{{ optional($when)->timestamp }}">
                    <x-staff.pill :tone="$tone[$status] ?? 'gray'" dot>{{ $statuses[$status] }}</x-staff.pill>
                    <span class="mt-1 block text-theme-xs text-gray-500 dark:text-gray-400">{{ optional($when)->diffForHumans() ?? '—' }}</span>
                </td>
                <td class="text-right" data-actions>
                    <a href="{{ route('business-reviews.show', $b) }}" class="inline-flex items-center rounded-lg bg-brand-500 px-3 py-2 text-theme-xs font-medium text-white hover:bg-brand-600">{{ $status === 'submitted' ? 'Review' : 'View' }}</a>
                </td>
            </tr>
        @empty
            <tr data-table-skip><td colspan="6"><x-staff.empty title="Nothing {{ strtolower($statuses[$status]) }}" message="Businesses appear here when borrowers submit them from their account." /></td></tr>
        @endforelse
    </x-staff.table>
</div>

@include('partials.modal.flash-alert')
@endsection
