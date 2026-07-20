@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Debt Recovery Cases</h2>
        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
        <button onclick="window.dispatchEvent(new CustomEvent('open-case-create'))" 
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-blue-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            New Case
        </button>
        @endif
    </div>
    
    {{-- Include the Cases Table --}}
    @include('partials.table.table-recovery-cases', ['recoveryCases' => $cases])
</div>

{{-- Include the Case Create/Edit Modal --}}
@include('partials.modal.cases-create-modal', [
    'borrowers' => $borrowers ?? [],
    'nplBorrowers' => $nplBorrowers ?? [],
    'statuses' => $statuses ?? [],
    'priorities' => $priorities ?? [],
    'officers' => $officers ?? [],
    'actionTypes' => $actionTypes ?? []
])

{{-- Include Alert Modal --}}
@include('partials.modal.alert-modal')

@endsection

@push('scripts')
<script>
    // Listen for refresh events
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('refresh-cases', function() {
            location.reload();
        });
    });
</script>
@endpush