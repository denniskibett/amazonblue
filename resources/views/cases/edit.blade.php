@extends('layouts.app')

@section('content')
@php
    $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
    $dateVal = fn ($d) => $d ? \Carbon\Carbon::parse($d)->toDateString() : '';
@endphp
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Edit case {{ $case->case_number }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $case->user->name ?? 'Unknown borrower' }}@if($case->loan) · loan #{{ $case->loan->id }} ({{ str_replace('_', ' ', $case->loan->status) }})@endif</p>
        </div>
        <a href="{{ route('cases.show', $case->id) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Back to case</a>
    </div>

    <form method="POST" action="{{ route('cases.update', $case->id) }}" class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div>
                <label class="{{ $label }}">Status</label>
                <select name="status_id" class="{{ $input }}">
                    @foreach($statuses as $status)
                        {{-- write-off has its own action on the case page --}}
                        @continue($status->slug === 'written_off' && $case->status_id !== $status->id)
                        <option value="{{ $status->id }}" @selected(old('status_id', $case->status_id) == $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Priority</label>
                <select name="priority_id" class="{{ $input }}">
                    @foreach($priorities as $priority)
                        <option value="{{ $priority->id }}" @selected(old('priority_id', $case->priority_id) == $priority->id)>{{ $priority->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Assigned to</label>
                <select name="assigned_to" class="{{ $input }}">
                    <option value="">Unassigned</option>
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" @selected(old('assigned_to', $case->assigned_to) == $officer->id)>{{ $officer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $label }}">Total debt (KES)</label>
                <input type="number" step="0.01" min="0" name="total_debt_amount" value="{{ old('total_debt_amount', $case->total_debt_amount) }}" class="{{ $input }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Collections lower this automatically when recorded as an action.</p>
            </div>
            <div>
                <label class="{{ $label }}">Principal outstanding</label>
                <input type="number" step="0.01" min="0" name="principal_outstanding" value="{{ old('principal_outstanding', $case->principal_outstanding) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Interest outstanding</label>
                <input type="number" step="0.01" min="0" name="interest_outstanding" value="{{ old('interest_outstanding', $case->interest_outstanding) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Penalty outstanding</label>
                <input type="number" step="0.01" min="0" name="penalty_outstanding" value="{{ old('penalty_outstanding', $case->penalty_outstanding) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Fees outstanding</label>
                <input type="number" step="0.01" min="0" name="fees_outstanding" value="{{ old('fees_outstanding', $case->fees_outstanding) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Default date</label>
                <input type="date" name="default_date" value="{{ old('default_date', $dateVal($case->default_date)) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Last contact</label>
                <input type="date" name="last_contact_date" value="{{ old('last_contact_date', $dateVal($case->last_contact_date)) }}" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Next action</label>
                <input type="date" name="next_action_date" value="{{ old('next_action_date', $dateVal($case->next_action_date)) }}" class="{{ $input }}">
            </div>

            <div class="md:col-span-3">
                <label class="{{ $label }}">Recovery strategy</label>
                <textarea name="recovery_strategy" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('recovery_strategy', $case->recovery_strategy) }}</textarea>
            </div>
            <div class="md:col-span-3">
                <label class="{{ $label }}">Notes</label>
                <textarea name="notes" rows="4" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('notes', $case->notes) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('cases.show', $case->id) }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Cancel</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Save changes</button>
        </div>
    </form>
</div>
@endsection
