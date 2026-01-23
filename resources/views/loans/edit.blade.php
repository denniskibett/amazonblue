@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-6">Admin Loan Management</h2>

    <form method="POST" action="{{ route('loans.update', $loan->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Loan Information Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Read-only Fields -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Loan ID</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded-md">{{ $loan->id }}</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Borrower</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded-md">
                        {{ $loan->user->name }} (ID: {{ $loan->user_id }})
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Created At</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded-md">
                        {{ $loan->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>

            <!-- Editable Fields -->
            <div class="space-y-4">
                <!-- Loan Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loan Type *</label>
                    <select name="loan_type_id" class="w-full p-2 border rounded-md" required>
                        @foreach($loanTypes as $type)
                            <option value="{{ $type->id }}" 
                                {{ $loan->loan_type_id == $type->id ? 'selected' : '' }}>
                                {{ $type->name }} 
                                ({{ $type->interest_rate }}% interest)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Loan Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Loan Amount *</label>
                    <input type="number" name="amount" value="{{ old('amount', $loan->amount) }}"
                           class="w-full p-2 border rounded-md" step="0.01" required>
                </div>

                <!-- Borrow Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Borrow Date *</label>
                    <input type="date" name="borrow_date" 
                           value="{{ old('borrow_date', $loan->borrow_date->format('Y-m-d')) }}"
                           class="w-full p-2 border rounded-md" required>
                </div>
            </div>
        </div>

        <!-- Status Management Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Loan Status -->
            <div>
                <label for="status" class="block mb-1 font-semibold">Loan Status</label>
                <select name="status" id="status" class="w-full p-2 border rounded-md" required>
                    @php
                        $statuses = ['pending' => 'Pending', 'approved' => 'Approved', 'disbursed' => 'Disbursed', 'repaid' => 'Repaid'];
                    @endphp
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" {{ $loan->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-sm text-gray-500 mt-1">Current status: <strong>{{ ucfirst($loan->status) }}</strong></p>
            </div>

            <!-- Broker Status -->
            <div>
                <label for="broker_status" class="block mb-1 font-semibold">Broker Status</label>
                <select name="broker_status" id="broker_status" class="w-full p-2 border rounded-md" required>
                    <option value="0" {{ $loan->broker_status == 0 ? 'selected' : '' }}>Direct Transaction</option>
                    <option value="1" {{ $loan->broker_status == 1 ? 'selected' : '' }}>Broker Related</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">Currently: 
                    <strong>{{ $loan->broker_status == 1 ? 'Broker Related' : 'Direct Transaction' }}</strong>
                </p>
            </div>
        </div>


        <!-- Financial Overview -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Financial Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm text-gray-600">Total Due</label>
                    <p class="font-medium">KES {{ number_format($loan->calculateTotalDue(), 2) }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Total Repaid</label>
                    <p class="font-medium">KES {{ number_format($loan->totalRepaid(), 2) }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Outstanding Balance</label>
                    <p class="font-medium">KES {{ number_format($loan->getOutstandingBalanceAttribute(), 2) }}</p>
                </div>
                <div>
                    <label class="text-sm text-gray-600">Penalties</label>
                    <p class="font-medium">KES {{ number_format($loan->calculatePenalties(), 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Admin Notes -->
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Admin Notes</label>
            <textarea name="admin_notes" class="w-full p-2 border rounded-md" rows="4"
                      placeholder="Add internal notes or comments...">{{ old('admin_notes', $loan->admin_notes) }}</textarea>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-4 mt-8">
            <a href="{{ route('loans.show', $loan->id) }}" 
               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                Cancel
            </a>
            <button type="submit" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Update Loan
            </button>
        </div>
    </form>

    <!-- Repayment History -->
    <div class="mt-12">
        <h3 class="text-lg font-semibold mb-4">Repayment History</h3>
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction ID</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($loan->repayments as $repayment)
                        <tr>
                            <td class="px-6 py-4">{{ $repayment->repayment_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4">KES {{ number_format($repayment->amount, 2) }}</td>
                            <td class="px-6 py-4 font-mono">{{ $repayment->transaction }}</td>
                            <td class="px-6 py-4"><a href="{{ route('repayments.edit', $loan->id) }}">Edit</a>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection