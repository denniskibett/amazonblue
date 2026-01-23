@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Edit Disbursement</h1>

    <form action="{{ route('disbursements.update', $disbursement->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Loan ID</label>
            <div class="mt-1 p-2 bg-gray-100 rounded">
                {{ $disbursement->loan_id }}
            </div>
        </div>

        <div class="mb-4">
            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
            <input type="number" id="amount" name="amount" step="0.01" 
                   value="{{ old('amount', $disbursement->amount) }}" 
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500" required>
            @error('amount')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="disburse_date" class="block text-sm font-medium text-gray-700">Disburse Date</label>
            <input type="date" id="disburse_date" name="disburse_date" 
                   value="{{ old('disburse_date', $disbursement->disburse_date) }}" 
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500" required>
            @error('disburse_date')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date</label>
            <input type="date" id="payment_date" name="payment_date" 
                   value="{{ old('payment_date', $disbursement->payment_date) }}" 
                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500" required>
            @error('payment_date')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="mode" class="block text-sm font-medium text-gray-700">Payment Mode</label>
            <select id="mode" name="mode" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500" required>
                <option value="Mpesa" {{ $disbursement->mode == 'Mpesa' ? 'selected' : '' }}>Mpesa</option>
                <option value="Bank" {{ $disbursement->mode == 'Bank' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="Cash" {{ $disbursement->mode == 'Cash' ? 'selected' : '' }}>Cash</option>
            </select>
            @error('mode')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update Disbursement</button>
        </div>
    </form>
</div>
@endsection