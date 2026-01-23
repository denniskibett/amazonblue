@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Loan Details</h1>
        <div class="loan-status-badge px-4 py-2 rounded-full 
            @if($loan->status === 'approved') bg-green-100 text-green-800
            @elseif($loan->status === 'disbursed') bg-blue-100 text-blue-800
            @elseif($loan->status === 'rejected') bg-red-100 text-red-800
            @else bg-gray-100 text-gray-800 @endif">
            {{ ucfirst($loan->status) }}
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="container mx-auto p-6 space-y-6">
        @if(auth()->user()->role === 'admin')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-blue-100">
                    <p class="text-sm text-gray-500 mb-1">Principal</p>
                    <p class="text-xl font-bold text-blue-600">KES {{ number_format($principal, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-green-100">
                    <p class="text-sm text-gray-500 mb-1">Interest</p>
                    <p class="text-xl font-bold text-green-600">KES {{ number_format($interest, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-red-100">
                    <p class="text-sm text-gray-500 mb-1">Penalties</p>
                    <p class="text-xl font-bold text-red-600">KES {{ number_format($penaltyAmount, 2) }}</p>
                </div>
                @if($loan->broker_status == 1)
                <div class="bg-white p-4 rounded-xl shadow-sm border border-purple-100">
                    <p class="text-sm text-gray-500 mb-1">Broker Fees</p>
                    <p class="text-xl font-bold text-purple-600">KES {{ number_format($brokerFees, 2) }}</p>
                </div>
                @endif
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Net Earnings</p>
                    <p class="text-xl font-bold @if($netEarnings >= 0) text-green-600 @else text-red-600 @endif">
                        KES {{ number_format($netEarnings, 2) }}
                    </p>
                </div>
            </div>
        @elseif(auth()->user()->role === 'broker')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-blue-100">
                    <p class="text-sm text-gray-500 mb-1">Principal</p>
                    <p class="text-xl font-bold text-blue-600">KES {{ number_format($principal, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-green-100">
                    <p class="text-sm text-gray-500 mb-1">Interest</p>
                    <p class="text-xl font-bold text-green-600">KES {{ number_format($interest, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-red-100">
                    <p class="text-sm text-gray-500 mb-1">Penalties</p>
                    <p class="text-xl font-bold text-red-600">KES {{ number_format($penaltyAmount, 2) }}</p>
                </div>
                @if($loan->broker_status == 1)
                <div class="bg-white p-4 rounded-xl shadow-sm border border-purple-100">
                    <p class="text-sm text-gray-500 mb-1">Broker Fees</p>
                    <p class="text-xl font-bold text-purple-600">KES {{ number_format($brokerFees, 2) }}</p>
                </div>
                @endif
            </div>
        @elseif(auth()->user()->role === 'borrower')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-blue-100">
                    <p class="text-sm text-gray-500 mb-1">Principal</p>
                    <p class="text-xl font-bold text-blue-600">KES {{ number_format($principal, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-green-100">
                    <p class="text-sm text-gray-500 mb-1">Interest</p>
                    <p class="text-xl font-bold text-green-600">KES {{ number_format($interest, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-red-100">
                    <p class="text-sm text-gray-500 mb-1">Penalties</p>
                    <p class="text-xl font-bold text-red-600">KES {{ number_format($penaltyAmount, 2) }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-yellow-100">
                    <p class="text-sm text-gray-500 mb-1">Days Until Due</p>
                    <p class="text-xl font-bold text-yellow-600" id="countdown-timer">
                        {{ $diff->format('%d days, %h hours, %i minutes') }}
                    </p>
                </div>
            </div>
        @endif

        <!-- Loan Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Loan Info Card -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Loan Information</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-600 text-sm">Loan ID</p>
                        <p class="font-medium">#{{ str_pad($loan->id, 5, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Borrower</p>
                        <p class="font-medium">{{ $loan->user->name ?? 'N/A' }} (ID: {{ $loan->user_id }})</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Loan Type</p>
                        <p class="font-medium">{{ $loan->loanType->name ?? 'N/A' }} - {{ $interestRate }}% for {{ $period }} {{ $periodUnit }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Loan Description</p>
                        <p class="font-medium">{{ $loan->loanType->description ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Client Type</p>
                        {{ ($clientType == 0) ? 'Our Client' : 'Broker Client' }}
                            <span class="text-xs ml-2 px-2 py-1 rounded 
                                {{ $clientType == 0 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $clientType == 0 ? 'Direct' : 'Brokered' }}
                            </span>                
                    </div>
                    <div class="mb-4">
                        <a href="{{ route('loans.generatePdf', ['id' => $loan->user_id, 'loanId' => $loan->id]) }}" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Download PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Financial Card -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Financial Details</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-600 text-sm">Principal</p>
                        <p class="font-medium">KES {{ number_format($principal, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Interest ({{ $interestRate }}%)</p>
                        <p class="font-medium">KES {{ number_format($interest, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Penalties ({{ $basePenaltyRate }}%)</p>
                        <p class="font-medium">KES {{ number_format($basePenaltyRate*$totalRepaymentsBeforeDue, 2) }}</p>
                    </div>
                    @if($broker && $broker->broker_status == 0)
                        <div>
                            <p class="text-gray-600 text-sm">Broker Fees Interest 
                                ({{ ($clientType == 0) ? round($broker->interest_client, 2) : round($broker->interest_broker, 2) }}%)
                                -<span class="text-xs ml-2">{{ $clientType == 0 ? 'Client Rate' : 'Broker Rate' }}</span>
                            </p>
                            <p class="font-medium">
                                KES {{ number_format($brokerFees, 2) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Broker Penalty Fees 
                                ({{ ($clientType == 0) ? round($broker->penalty_client, 2) : round($broker->penalty_broker, 2) }}%)
                                -<span class="text-xs ml-2">{{ $clientType == 0 ? 'Client Rate' : 'Broker Rate' }}</span>
                            </p>
                            <p class="font-medium">
                                KES {{ number_format($brokerPenaltyFees, 2) }}
                            </p>
                        </div>
                    @endif
                    <div>
                        <p class="text-gray-600 text-sm">Repayments Before</p>
                        <p class="font-medium">KES {{ number_format($totalRepaymentsBeforeDue, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Repayments After</p>
                        <p class="font-medium">KES {{ number_format($totalRepaymentsAfterDue, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Total (P+I+Pe)-R</p>
                        <p class="font-medium">KES {{ number_format($principalPlusInterest,2) }} +
                            {{ number_format($basePenaltyRate*$totalRepaymentsBeforeDue, 2) }} -
                            {{ number_format($totalRepaymentsBeforeDue+$totalRepaymentsAfterDue, 2) }} =
                            KES {{ number_format( $totalCombined, 2) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Total Net Earnings</p>
                        <p class="font-medium"><strong>KES {{ number_format($netEarnings, 2) }}</strong></p>
                    </div>
                </div>
            </div>

            <!-- Dates Card -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Timeline</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-600 text-sm">Borrow Date</p>
                        <p class="font-medium">{{ \Carbon\Carbon::parse($loan->borrow_date)->format('D, M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Due Date</p>
                        <p class="font-medium">{{ \Carbon\Carbon::parse($dueDate)->format('D, M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Latest Repayment Date</p>
                        <p class="font-medium">{{ \Carbon\Carbon::parse($lastRepaymentDate)->format('D, M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Period</p>
                        <p class="font-medium">{{ $period }} {{ $periodUnit}}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Days Late</p>
                        <p class="font-medium">{{ $daysLate }} days</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Penalties Card -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Penalties</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-600 text-sm">Daily Rate</p>
                        <p class="font-medium">{{ $basePenaltyRate }}%</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Days Late</p>
                        <p class="font-medium">{{ $daysLate }} {{ $periodUnit }} </p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Penalty per day</p>
                        <p class="font-medium">KES {{ number_format($outstandingAtDueDate*$basePenaltyRate/100, 2) }} </p>
                    </div>
                    <div class="pt-2 border-t">
                        <p class="text-gray-600 text-sm">Total Penalty</p>
                        <p class="font-medium text-red-600">{{ number_format($penaltyAmount, 2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Broker Fees Card -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Broker Fees</h2>
                <div class="space-y-3">
                    <!-- Client Type -->
                    <div>
                        <p class="text-gray-600 text-sm">Client Type</p>
                        <p class="font-medium">
                            {{ ($clientType == 0) ? 'Our Client' : 'Broker Client' }}
                            <span class="text-xs ml-2 px-2 py-1 rounded 
                                {{ $clientType == 0 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $clientType == 0 ? 'Direct' : 'Brokered' }}
                            </span>
                        </p>
                    </div>

                    <!-- Broker Information -->
                    
                    @if($brokers->isNotEmpty())
                        @foreach($brokers as $broker)
                        <div>
                            <p class="text-gray-600 text-sm">Broker</p>
                            <p class="font-medium">
                                {{ $broker->user ? $broker->user->name : 'Unassigned' }}
                                @if($broker->user && $broker->user->phone)
                                    <span class="text-xs text-gray-500 ml-2">{{ $broker->user->phone }}</span>
                                @endif
                            </p>
                        </div>
                        @endforeach
                    @else
                        <div>
                            <p class="text-gray-600 text-sm">Broker</p>
                            <p class="font-medium text-yellow-600">No broker assigned</p>
                        </div>
                    @endif

                    <!-- Fee Rates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm">Client Fee Rate</p>
                            <p class="font-medium">{{ round($broker->interest_client, 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm">Broker Fee Rate</p>
                            <p class="font-medium">{{ round($broker->interest_broker, 2) }}%</p>
                        </div>
                    </div>

                    <!-- Applied Rate -->
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="text-gray-600 text-sm">Applied Rate</p>
                        <p class="font-medium text-blue-700">
                            {{ ($clientType == 0) ? round($broker->interest_client, 2) : round($broker->interest_broker, 2) }}%
                            <span class="text-xs ml-2">
                                ({{ $clientType == 0 ? 'Client Rate' : 'Broker Rate' }})
                            </span>
                        </p>
                    </div>

                    <!-- Dynamic Date -->
                    <div class="flex justify-between items-center pt-2 border-t">
                        <p class="text-gray-600 text-sm">As of</p>
                        <p class="font-medium">
                            {{ now()->format('M j, Y') }} 
                            <span class="text-xs text-gray-500 ml-1">
                                ({{ now()->diffForHumans() }})
                            </span>
                        </p>
                    </div>

                    <!-- Total Fees -->
                    <div class="pt-2 border-t">
                        <div class="flex justify-between items-center">
                            <p class="text-gray-600 text-sm">Total Fees</p>
                            <p class="font-medium text-blue-600">
                                KES {{ number_format($brokerFees, 2) }}
                                @if($brokerFees > 0)
                                    <span class="text-xs ml-2 px-2 py-0.5 rounded-full bg-green-100 text-green-800">
                                        Payment Due
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- P&L Card -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Profit & Loss</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-gray-600 text-sm">Principal + Interest</p>
                        <p class="font-medium">{{ number_format($principalPlusInterest, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">+ Penalties</p>
                        <p class="font-medium">{{ number_format($penaltyAmount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">- Broker Fees</p>
                        <p class="font-medium">{{ number_format($brokerFees, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">- Repayments</p>
                        <p class="font-medium">{{ number_format($totalRepaymentsBeforeDue+$totalRepaymentsAfterDue, 2) }}</p>
                    </div>
                    <div class="pt-2 border-t">
                        <p class="text-gray-600 text-sm font-medium">Net P&L</p>
                        <p class="text-xl font-bold @if($pl >= 0) text-green-600 @else text-red-600 @endif">
                            {{ number_format($pl, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Disbursements -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold mb-4">Disbursements</h2>
                    
                    @if(auth()->user()->role !== 'borrower')
                        <div class="mb-4">
                            <a href="{{ route('disbursements.create', ['loan_id' => $loan->id]) }}" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Add Disbursement
                            </a>
                        </div>
                    @endif
                    </div>
                
                @if($loan->disbursements->isEmpty())
                    <p class="text-gray-600">No disbursements found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-2">Amount</th>
                                    <th class="text-left px-4 py-2">Date</th>
                                    <th class="text-left px-4 py-2">Transaction Code</th>
                                    @if(auth()->user()->role !== 'borrower')
                                        <th class="text-left px-4 py-2">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loan->disbursements as $disbursement)
                                    <tr class="border-t">
                                        <td class="px-4 py-3">{{ number_format($disbursement->amount, 2) }}</td>
                                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($disbursement->disburse_date)->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">{{ $disbursement->transaction ?? 'N/A' }}</td>
                                        @if(auth()->user()->role !== 'borrower')
                                        <td class="px-4 py-3">
                                            <a href="{{ route('disbursements.edit', $disbursement->id) }}" class="text-blue-600 hover:underline">Edit</a>
                                            <form action="{{ route('disbursements.destroy', $disbursement->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline ml-2" onclick="return confirm('Are you sure you want to delete this disbursement?');">Delete</button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Repayments -->
            <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold mb-4">Repayments</h2>
                    
                    @if(auth()->user()->role !== 'borrower')
                        <div class="mb-4">
                            <a href="{{ route('repayments.create', ['loan_id' => $loan->id]) }}" 
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Add Repayment
                            </a>
                        </div>
                    @endif
                </div>
                
                @if($loan->repayments->isEmpty())
                    <p class="text-gray-600">No repayments found.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-4 py-2">Amount</th>
                                    <th class="text-left px-4 py-2">Date</th>
                                    <th class="text-left px-4 py-2">Transaction Code</th>
                                    @if(auth()->user()->role !== 'borrower')
                                        <th class="text-left px-4 py-2">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($loan->repayments as $repayment)
                                    <tr class="border-t">
                                        <td class="px-4 py-3">{{ number_format($repayment->amount, 2) }}</td>
                                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($repayment->repayment_date)->format('M d, Y') }}</td>
                                        <td class="px-4 py-3">{{ $repayment->transaction ?? 'N/A' }}</td>
                                        @if(auth()->user()->role !== 'borrower')
                                        <td class="px-4 py-3">
                                            <a href="{{ route('repayments.edit', $repayment->id) }}" class="text-blue-600 hover:underline">Edit</a>
                                            <form action="{{ route('repayments.destroy', $repayment->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline ml-2" onclick="return confirm('Are you sure you want to delete this repayment?');">Delete</button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                @endforeach
                                <tr class="border-t font-semibold bg-gray-50">
                                    <td class="px-4 py-3">Total: {{ number_format($totalRepaymentsBeforeDue+$totalRepaymentsAfterDue, 2) }}</td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3"></td>
                                    @if(auth()->user()->role !== 'borrower')
                                        <td class="px-4 py-3"></td>
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div> 
        </div>

        <!-- Detailed Financial Breakdown -->
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200 mb-8">
            <h2 class="text-xl font-semibold mb-4">Financial Breakdown</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Interest + Penalties - Broker Fees -->
                <div>
                    <h3 class="text-lg font-medium mb-3">Interest + Penalties - Broker Fees</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Interest Earned:</span>
                            <span class="font-medium">{{ number_format($interest, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Penalties Earned:</span>
                            <span class="font-medium">{{ number_format($penaltyAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Broker Fees Paid:</span>
                            <span class="font-medium text-red-600">-{{ number_format($brokerFees+($penaltyAmount*$penaltyRate/100), 2) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t">
                            <span class="text-gray-600 font-medium">Net Earnings:</span>
                            <span class="font-bold @if(($interest + $penaltyAmount - $brokerFees) >= 0) text-green-600 @else text-red-600 @endif">
                                {{ number_format($netEarnings, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cash Flow -->
                <div>
                    <h3 class="text-lg font-medium mb-3">Cash Flow</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Disbursed:</span>
                            <span class="font-medium">{{ number_format($loan->disbursements->sum('amount'), 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Repaid:</span>
                            <span class="font-medium">{{ number_format($totalRepaymentsBeforeDue+$totalRepaymentsAfterDue, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Outstanding Balance:</span>
                            <span class="font-medium">{{ number_format($totalCombined, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Calculate due date from PHP variable
    const dueDate = new Date("{{ \Carbon\Carbon::parse($dueDate)->format('M d, Y H:i:s') }}");
    
    function updateCountdown() {
        const now = new Date();
        const diff = dueDate - now;
        
        if (diff <= 0) {
            document.getElementById('countdown-timer').textContent = 'Loan is overdue!';
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        document.getElementById('countdown-timer').textContent = 
            `${days} days, ${hours} hours, ${minutes} minutes`;
    }
    
    // Initial update
    updateCountdown();
    // Update every minute
    setInterval(updateCountdown, 60000);
</script>
@endpush
@endsection