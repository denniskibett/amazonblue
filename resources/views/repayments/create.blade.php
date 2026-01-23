@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded">
    <h2 class="text-2xl font-bold mb-6">Add a New Repayment</h2>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 bg-gray-50 p-4 rounded">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="font-semibold">Loan ID:</label>
                <p>#{{ $loan->user->id ?? '' }}</p>
            </div>
            <div>
                <label class="font-semibold">Loan Status:</label>
                <p class="capitalize">{{ $loan->status }}</p>
            </div>
            <div>
                <label class="font-semibold">Borrower's Name:</label>
                <p>{{ $loan->user->name ?? '' }}</p>
            </div>
            <div>
                <label class="font-semibold">Loan Amount:</label>
                <p>KES {{ number_format($loan->amount, 2) }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('repayments.store') }}" method="POST">
        @csrf
        <input  name="loan_id" hidden value="{{ request()->get('loan_id') }}">

        <div class="mb-4">
            <label for="amount" class="block font-semibold">Amount</label>
            <input type="number" name="amount" step="0.01" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label for="transaction" class="block font-semibold">Transaction Reference</label>
            <input type="text" name="transaction" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label for="repayment_date" class="block font-semibold">Repayment Date</label>
            <input type="date" name="repayment_date" class="w-full border rounded p-2" required>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Submit Repayment
            </button>
        </div>
    </form>
</div>
@endsection
