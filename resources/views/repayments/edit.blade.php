@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded">
    <h2 class="text-2xl font-bold mb-6">Edit Repayment</h2>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('repayments.update', $repayment->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="loan_id" class="block font-semibold">Loan</label>
            <select name="loan_id" id="loan_id" class="w-full border rounded p-2" disabled>
                <option value="{{ $repayment->loan->id }}">
                    Loan #{{ $repayment->loan->id }} - KES {{ number_format($repayment->loan->amount) }}
                </option>
            </select>
        </div>

        <div class="mb-4">
            <label for="amount" class="block font-semibold">Amount</label>
            <input type="number" name="amount" id="amount" step="0.01" value="{{ $repayment->amount }}" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label for="transaction" class="block font-semibold">Transaction Reference</label>
            <input type="text" name="transaction" id="transaction" value="{{ $repayment->transaction }}" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label for="repayment_date" class="block font-semibold">Repayment Date</label>
            <input type="date" name="repayment_date" id="repayment_date" value="{{ $repayment->repayment_date->format('Y-m-d') }}" class="w-full border rounded p-2" required>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Update Repayment
            </button>
        </div>
    </form>
</div>
@endsection
