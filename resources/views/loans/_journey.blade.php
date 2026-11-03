{{-- Loan journey from the audit trail: who did what, when (owner, 3 Nov 2026) --}}
@php
    $journey = app(\App\Services\Loans\LoanJourney::class)->for($loan);
    $dot = [
        'loan' => 'bg-brand-500', 'status' => 'bg-blue-500', 'money_out' => 'bg-orange-500', 'money_in' => 'bg-green-500',
        'approval' => 'bg-purple-500', 'recovery' => 'bg-red-500', 'removed' => 'bg-red-700', 'event' => 'bg-gray-500', 'change' => 'bg-gray-400',
    ];
@endphp
<div class="mb-8 rounded-lg border border-gray-100 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800" x-data="{ all: false }">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Loan journey <span class="text-xs font-normal text-gray-500">({{ $journey->count() }} steps, from the audit log)</span></h3>
        @can('view audit log')
            <a href="{{ route('audit-log.index', ['table' => 'loans', 'record' => $loan->id]) }}" class="text-xs text-brand-500 hover:underline">Full audit log</a>
        @endcan
    </div>

    @if ($journey->isEmpty())
        <p class="text-sm text-gray-500">Nothing recorded for this loan yet.</p>
    @else
        <ol class="relative ml-2 border-l border-gray-200 dark:border-gray-700">
            @foreach ($journey->reverse()->values() as $i => $step)
                <li class="mb-3 ml-4" @if ($i >= 15) x-show="all" x-cloak @endif>
                    <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border border-white dark:border-gray-900 {{ $dot[$step['kind']] ?? 'bg-gray-400' }}"></span>
                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $step['text'] }}</p>
                    <p class="text-theme-xs text-gray-500 dark:text-gray-400">{{ $step['who'] }} · {{ $step['at']->format('d M Y H:i') }}</p>
                </li>
            @endforeach
        </ol>
        @if ($journey->count() > 15)
            <button type="button" class="text-xs text-brand-500 hover:underline" @click="all = ! all" x-text="all ? 'Show latest 15' : 'Show all {{ $journey->count() }}'"></button>
        @endif
    @endif
</div>
