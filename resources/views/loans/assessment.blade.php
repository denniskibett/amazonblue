@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Loan Risk Assessment</h1>
            <span class="px-3 py-1 rounded-full text-sm font-medium 
                @if($latestAssessment->getRiskCategory() === 'Low Risk') bg-green-100 text-green-800
                @elseif($latestAssessment->getRiskCategory() === 'Medium Risk') bg-yellow-100 text-yellow-800
                @elseif($latestAssessment->getRiskCategory() === 'High Risk') bg-orange-100 text-orange-800
                @else bg-red-100 text-red-800 @endif">
                {{ $latestAssessment->getRiskCategory() }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-3">Loan Details</h3>
                <p><strong>Borrower:</strong> {{ $loan->user->name }}</p>
                <p><strong>Amount:</strong> KES {{ number_format($loan->amount, 2) }}</p>
                <p><strong>Purpose:</strong> {{ $loan->reason }}</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-3">4Cs Assessment Scores</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>Character:</span>
                        <span class="font-semibold">{{ $latestAssessment->character_score }}/100</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Capacity:</span>
                        <span class="font-semibold">{{ $latestAssessment->capacity_score }}/100</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Capital:</span>
                        <span class="font-semibold">{{ $latestAssessment->capital_score }}/100</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Conditions:</span>
                        <span class="font-semibold">{{ $latestAssessment->conditions_score }}/100</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="font-semibold">Overall Score:</span>
                        <span class="font-semibold text-lg">{{ $latestAssessment->overall_score }}/100</span>
                    </div>
                </div>
            </div>
        </div>

        @if($latestAssessment->assessment_notes)
        <div class="bg-blue-50 p-4 rounded-lg mb-6">
            <h3 class="font-semibold mb-2">Assessment Notes</h3>
            <p class="text-gray-700">{{ $latestAssessment->assessment_notes }}</p>
        </div>
        @endif

        <div class="flex justify-between items-center pt-6 border-t">
            <a href="{{ route('loans.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                Back to Loans
            </a>
            <div class="space-x-3">
                <a href="{{ route('loans.agreement.show', $loan->id) }}" 
                   class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    View Agreement
                </a>
                <a href="{{ route('loans.agreement.download', $loan->id) }}" 
                   class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                    Download Agreement
                </a>
                @if(in_array(auth()->user()->role, ['admin', 'teller']))
                <a href="{{ route('loans.edit', $loan->id) }}" 
                   class="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600">
                    Process Loan
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection