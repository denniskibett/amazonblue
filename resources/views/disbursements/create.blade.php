@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Create Disbursement for {{$loan->user->name ?? ''}} - amount: KES {{number_format($loan->amount,2)}} ,{{$loan->borrow_date->format('d-m-Y')}}</h1>

    <form action="{{ route('disbursements.store',['loan'=>$loan->id]) }}" method="POST">
        @csrf
        <input type="text" name="loan_id" hidden value="{{$loan->id}}" id="">
        <div class="mb-4">
            <label for="amount" class="block text-gray-700 font-semibold mb-2">Amount</label>
            <input type="number" name="amount" id="amount" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
            @error('amount')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-4">
            <label for="transaction" class="block text-gray-700 font-semibold mb-2">Transaction</label>
            <input type="text" name="transaction" id="transaction" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
            @error('transaction')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="disburse_date" class="block text-gray-700 font-semibold mb-2">Disbursement Date</label>
            <input type="date" name="disburse_date" id="disburse_date" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
            @error('disburse_date')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div class="mb-4">
            <label for="payment_date" class="block text-gray-700 font-semibold mb-2">Payment Date</label>
            <input type="date" name="payment_date" id="payment_date" class="form-input block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
            @error('payment_date')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-4">
            <label for="mode" class="block text-gray-700 font-semibold mb-2">Mode</label>
            <select name="mode" id="mode" class="form-select block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-200" required>
                <option value="">-- Select Mode --</option>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <!-- Add other modes as necessary -->
            </select>
            @error('mode')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Create Disbursement</button>
    </form>
</div>
@endsection