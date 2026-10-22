@extends('layouts.app')

@section('content')
<div>
    <x-ui.page-header title="Debt Recovery" sub="Recovery cases on late loans: who owns each one, the last action and what was promised." />
    <x-ui.page-metrics page="cases" />

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