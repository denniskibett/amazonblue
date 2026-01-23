@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                @if(auth()->user()->role === 'admin')
                    All Disbursements
                @elseif(auth()->user()->role === 'broker')
                    Your Brokerage Disbursements
                @else
                    Your Loan Disbursements
                @endif
            </h1>
            <p class="text-gray-600 mt-1">
                @if(auth()->user()->role === 'admin')
                    All disbursement transactions across the system
                @elseif(auth()->user()->role === 'broker')
                    Disbursements for your brokered loans
                @else
                    Your personal loan disbursements
                @endif
            </p>
        </div>
        
        @can('create', App\Models\Disbursement::class)
        <a href="{{ route('disbursements.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg shadow hover:from-blue-700 hover:to-blue-600 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            New Disbursement
        </a>
        @endcan
    </div>

    @if($disbursements->isEmpty())
        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No disbursements found</h3>
            <p class="mt-1 text-gray-500">
                @if(auth()->user()->role === 'admin')
                    No disbursement records exist in the system yet.
                @elseif(auth()->user()->role === 'broker')
                    You have no disbursements for your brokered loans.
                @else
                    You have no loan disbursements yet.
                @endif
            </p>
            @can('create', App\Models\Disbursement::class)
            <div class="mt-6">
                <a href="{{ route('disbursements.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create First Disbursement
                </a>
            </div>
            @endcan
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Loan Details
                            </th>
                            @if(auth()->user()->role === 'admin')
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Borrower
                            </th>
                            @endif
                            @if(in_array(auth()->user()->role, ['admin', 'broker']))
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Broker Status
                            </th>
                            @endif
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Transaction
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Method
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($disbursements as $disbursement)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            KES {{ number_format($disbursement->loan->amount, 2) }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $disbursement->loan->loanType->name ?? 'Standard Loan' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            @if(auth()->user()->role === 'admin')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $disbursement->loan->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $disbursement->loan->user->email }}</div>
                            </td>
                            @endif
                            
                            @if(in_array(auth()->user()->role, ['admin', 'broker']))
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($disbursement->loan->broker_status) bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $disbursement->loan->broker_status ? 'Brokered' : 'Direct' }}
                                </span>
                                @if($disbursement->loan->broker_status && auth()->user()->role === 'admin')
                                <div class="text-xs text-gray-500 mt-1">
                                    Broker: {{ $disbursement->loan->broker->name ?? 'N/A' }}
                                </div>
                                @endif
                            </td>
                            @endif
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-mono">{{ $disbursement->transaction_id }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $disbursement->reference_number ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($disbursement->mode === 'mpesa') bg-green-100 text-green-800
                                    @elseif($disbursement->mode === 'bank') bg-blue-100 text-blue-800
                                    @elseif($disbursement->mode === 'cash') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($disbursement->mode) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($disbursement->disburse_date)->format('d M Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($disbursement->disburse_date)->format('h:i A') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <a href="{{ route('disbursements.show', $disbursement->id) }}" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View
                                    </a>
                                    @can('update', $disbursement)
                                    <a href="{{ route('disbursements.edit', $disbursement->id) }}" class="text-indigo-600 hover:text-indigo-900 inline-flex items-center ml-3">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Edit
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            
        </div>
    @endif
</div>
@endsection