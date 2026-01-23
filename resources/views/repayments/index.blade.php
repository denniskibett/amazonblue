@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Repayments</h2>
        @if(in_array(auth()->user()->role, ['admin', 'broker', 'teller']))
            <a href="{{ route('repayments.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Add Repayment
            </a>
        @endif
    </div>

    <div class="overflow-x-auto bg-white shadow-md rounded-lg p-4">
        <table class="min-w-full text-left">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">#</th>
                    <th class="py-2 px-4 border-b">Borrower</th>
                    <th class="py-2 px-4 border-b">Loan ID</th>
                    <th class="py-2 px-4 border-b">Amount</th>
                    <th class="py-2 px-4 border-b">Transaction</th>
                    <th class="py-2 px-4 border-b">Date</th>
                    <th class="py-2 px-4 border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($repayments as $repayment)
                <tr>
                    <td class="py-2 px-4 border-b">{{ $loop->iteration }}</td>
                    <td class="py-2 px-4 border-b">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                                <span class="text-indigo-600 text-sm font-medium">
                                    {{ strtoupper(substr($repayment->loan->borrower->name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <p class="font-medium">{{ $repayment->loan->borrower->name }}</p>
                                <p class="text-sm text-gray-500">{{ $repayment->loan->borrower->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-2 px-4 border-b">#{{ $repayment->loan_id }}</td>
                    <td class="py-2 px-4 border-b">KES {{ number_format($repayment->amount) }}</td>
                    <td class="py-2 px-4 border-b">{{ $repayment->transaction }}</td>
                    <td class="py-2 px-4 border-b">{{ $repayment->repayment_date->toFormattedDateString() }}</td>
                    <td class="py-2 px-4 border-b flex space-x-2">
                        <a href="{{ route('repayments.show', $repayment->id) }}" class="text-blue-600 hover:underline">View</a>
                        
                        @if(in_array(auth()->user()->role, ['admin', 'teller']))
                            <a href="{{ route('repayments.edit', $repayment->id) }}" class="text-yellow-600 hover:underline">Edit</a>
                        @endif
                        
                        @if(auth()->user()->role === 'admin')
                            <form action="{{ route('repayments.destroy', $repayment->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach

                @if ($repayments->isEmpty())
                <tr>
                    <td colspan="7" class="py-4 text-center text-gray-500">No repayments found.</td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Pagination Links -->
        <div class="mt-4">
            {{ $repayments->links() }}
        </div>
    </div>
</div>
@endsection