{{-- resources/views/investments/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div>
    <x-ui.page-header title="Investment Portfolio" sub="Where partner capital is invested and how it is performing.">
            <button @click="window.dispatchEvent(new CustomEvent('refresh-investments'))" 
                    class="inline-flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </x-ui.page-header>
    <x-ui.page-metrics page="investments" />


    <!-- Investment Table -->
    @include('partials.table.table-investments', ['investments' => $investments, 'partners' => $partners ?? [], 'users' => $users ?? []])
</div>
@endsection