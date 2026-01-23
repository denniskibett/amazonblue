@extends('layouts.app')

@section('content')

@if(auth()->user()->role === 'borrower' && !auth()->user()->hasCompleteBiodata())
<div class="col-span-12">
    <div class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-red-800 dark:text-red-300">
                    Profile Incomplete
                </h3>
                <p class="text-red-700 dark:text-red-400 mt-1">
                    You need to complete your profile before applying for loans. 
                    <a href="{{ route('profile.edit') }}" class="underline font-medium">Complete your profile now</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="container mx-auto p-6">
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">

            <h1 class="text-xl font-medium text-gray-800 dark:text-white/90">
                @if(auth()->user()->role === 'broker')
                    Create New Loan for {{ $user->name ?? 'Borrower' }}
                @elseif(isset($user) && auth()->user()->role === 'admin')
                    Create Loan for {{ $user->name }}
                @else
                    Create New Loan
                @endif
            </h1>
        </div>

        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
            <form method="POST" action="{{ route('loans.store') }}" class="space-y-6">
                @csrf

                {{-- User/Borrower Selection --}}
                @if(auth()->user()->role === 'admin' && !isset($user))
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Select User
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select
                                name="user_id"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                @change="isOptionSelected = true"
                                required
                            >
                                <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    -- Select User --
                                </option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        {{ $user->name }} ({{ $user->email }} - {{ ucfirst($user->role) }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @elseif(auth()->user()->role === 'broker')
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Select Your Borrower
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select
                                name="user_id"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                @change="isOptionSelected = true"
                                required
                            >
                                <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    -- Select Borrower --
                                </option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <input type="hidden" name="broker_status" value="1">
                @else
                    <input type="hidden" name="user_id" value="{{ $user->id ?? auth()->id() }}">
                    <input type="hidden" name="broker_status" value="0">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Borrower Name
                        </label>
                        <p class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            {{ $user->name ?? auth()->user()->name }}
                        </p>
                    </div>
                @endif

                {{-- Loan Details --}}
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Loan Amount ({{ config('app.currency', '$') }})
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="1"
                            name="amount"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            value="{{ old('amount') }}"
                            required
                        >
                        @error('amount')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Borrow Date
                        </label>
                        <div class="relative">
                            <input
                                type="date"
                                name="borrow_date"
                                id="borrow_date"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                value="{{ old('borrow_date', date('Y-m-d')) }}"
                                onclick="this.showPicker()"
                                required
                            >
                            <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
                                </svg>
                            </span>
                        </div>
                        @error('borrow_date')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Due Date
                        </label>
                        <p class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" id="due_date_display">
                            {{-- Will be calculated by JavaScript --}}
                        </p>
                        <input type="hidden" name="due_date" id="due_date">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Loan Type
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select
                                name="loan_type_id"
                                id="loan_type_id"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                @change="isOptionSelected = true; calculateDueDate();"
                                required
                            >
                                <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    -- Select Loan Type --
                                </option>
                                @foreach($loanTypes as $loanType)
                                    <option value="{{ $loanType->id }}" data-period="{{ $loanType->period }}" data-unit="{{ $loanType->unit }}" {{ old('loan_type_id') == $loanType->id ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        {{ $loanType->name }} 
                                        @if($loanType->interest_rate)
                                            ({{ $loanType->interest_rate }}% interest)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        @error('loan_type_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Status Field --}}
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Loan Status
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select
                                name="status"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                @change="isOptionSelected = true"
                                required
                            >
                                <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Pending
                                </option>
                                <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Approved
                                </option>
                                <option value="disbursed" {{ old('status') == 'disbursed' ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Disbursed
                                </option>
                                <option value="repaid" {{ old('status') == 'repaid' ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Repaid
                                </option>
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        @error('status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <input type="hidden" name="status" value="pending">
                @endif

                {{-- Broker Status Field (only visible to admin/teller) --}}
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Transaction Type
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select
                                name="broker_status"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                @change="isOptionSelected = true"
                                required
                            >
                                <option value="0" {{ old('broker_status', '0') == '0' ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Direct Transaction
                                </option>
                                <option value="1" {{ old('broker_status') == '1' ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Broker Transaction
                                </option>
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        @error('broker_status')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Reason Field (Mandatory) --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Reason for Loan <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        name="reason"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        rows="3"
                        required
                    >{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Add these fields after the existing form fields in create.blade.php --}}

                {{-- Guarantor Information --}}
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller' || auth()->user()->role === 'borrower')
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Guarantor
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select
                                name="guarantor_id"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                @change="isOptionSelected = true"
                            >
                                <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    -- Select Guarantor (Optional) --
                                </option>
                                @foreach($guarantors as $guarantor)
                                    <option value="{{ $guarantor->id }}" {{ old('guarantor_id') == $guarantor->id ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        {{ $guarantor->name }} ({{ $guarantor->email }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                        @error('guarantor_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Relationship to Guarantor
                        </label>
                        <input
                            type="text"
                            name="guarantor_relationship"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            value="{{ old('guarantor_relationship') }}"
                            placeholder="e.g., Friend, Relative, Colleague"
                        >
                        @error('guarantor_relationship')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endif

                {{-- Loan Officer (Admin/Teller only) --}}
                @if(auth()->user()->role === 'admin' || auth()->user()->role === 'teller')
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Loan Officer
                    </label>
                    <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                        <select
                            name="loan_officer_id"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                            @change="isOptionSelected = true"
                        >
                            <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                -- Select Loan Officer (Optional) --
                            </option>
                            @foreach($loanOfficers as $officer)
                                <option value="{{ $officer->id }}" {{ old('loan_officer_id') == $officer->id ? 'selected' : '' }} class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    {{ $officer->name }} ({{ ucfirst($officer->role) }})
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                    @error('loan_officer_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                {{-- Signature Section --}}
                <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                    <h3 class="text-lg font-medium mb-4">Digital Signature</h3>
                    
                    @php
                        // Use the data passed from controller
                        $signatureUser = $signatureUser ?? null;
                        $hasExistingSignature = $hasExistingSignature ?? false;
                        $existingSignatureUrl = $existingSignatureUrl ?? null;
                    @endphp

                    {{-- Existing Signature Display --}}
                    @if($hasExistingSignature && $signatureUser && $existingSignatureUrl)
                    <div id="existing-signature-display" class="mb-6">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                <div class="flex-shrink-0">
                                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-200 dark:border-green-700 shadow-sm">
                                        <div class="w-32 h-32 flex items-center justify-center bg-transparent">
                                            <img src="{{ $existingSignatureUrl }}" 
                                                alt="Existing signature of {{ $signatureUser->name }}"
                                                class="max-w-full max-h-full object-contain"
                                                onerror="this.style.display='none'; document.getElementById('existing-signature-display').style.display='none';">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-green-800 dark:text-green-300 text-lg">
                                        {{ $signatureUser->name }}
                                    </h4>
                                    <p class="text-green-700 dark:text-green-400 text-sm mb-2">
                                        ✅ Existing Signature Found
                                    </p>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                                        This user already has a signature on file at:<br>
                                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $existingSignatureUrl }}</code>
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Signature Verified
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-500">
                                            File: {{ $signatureUser->signature }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Use Existing Signature Option --}}
                        <div class="mt-4 flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div>
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                    Use existing signature?
                                </p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    The existing signature will be used for the loan agreement unless you create a new one below.
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="use-existing-signature" checked class="rounded border-blue-300 text-blue-600 focus:ring-blue-500 dark:border-blue-600 dark:bg-blue-900">
                                <label for="use-existing-signature" class="text-sm text-blue-800 dark:text-blue-300">Use Existing Signature</label>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Signature Creation Section --}}
                    <div id="signature-creation-section" class="@if($hasExistingSignature) border-t border-gray-200 dark:border-gray-700 pt-6 @endif">
                        <h4 class="text-md font-medium mb-4 text-gray-700 dark:text-gray-300">
                            @if($hasExistingSignature)
                                Create New Signature (Optional - will replace existing)
                            @else
                                Create Digital Signature
                            @endif
                        </h4>
                        
                        <div id="signature-section">
                            <div class="mb-4">
                                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    @if($hasExistingSignature)
                                        Draw a new signature below to replace the existing one
                                    @else
                                        Draw Your Signature in the Square Below
                                    @endif
                                </label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-white dark:bg-gray-900">
                                    {{-- Square Canvas Container --}}
                                    <div class="flex justify-center">
                                        <div id="signature-pad" class="signature-pad relative">
                                            <canvas class="border border-gray-300 rounded-lg bg-white" 
                                                    style="touch-action: none; width: 400px; height: 400px; max-width: 100%;"></canvas>
                                            {{-- Canvas Guidelines --}}
                                            <div class="absolute inset-0 pointer-events-none border-2 border-dashed border-blue-200 rounded-lg m-2"></div>
                                            <div class="absolute top-1/2 left-0 right-0 h-px bg-blue-100 pointer-events-none"></div>
                                            <div class="absolute left-1/2 top-0 bottom-0 w-px bg-blue-100 pointer-events-none"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex flex-col sm:flex-row gap-2 justify-center items-center">
                                        <button type="button" id="clear-signature" class="px-4 py-2 text-sm bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                            Clear Signature
                                        </button>
                                        <button type="button" id="save-signature" class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                            @if($hasExistingSignature)
                                                Save New Signature
                                            @else
                                                Save Signature
                                            @endif
                                        </button>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 text-center sm:text-left">
                                            @if($hasExistingSignature)
                                                This will replace your existing signature
                                            @else
                                                Draw your signature to fill the square area
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div id="signature-status" class="mt-2 text-sm text-center"></div>
                                <input type="hidden" name="signature_data" id="signature-data">
                                <input type="hidden" name="use_existing_signature" id="use-existing-signature-input" value="{{ $hasExistingSignature ? '1' : '0' }}">
                                
                                {{-- Signature Preview --}}
                                <div id="signature-preview-container" class="mt-6 hidden">
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center">
                                            @if($hasExistingSignature)
                                                New Signature Preview
                                            @else
                                                Signature Preview
                                            @endif
                                        </h4>
                                        <div class="flex flex-col items-center gap-4">
                                            <div class="flex-shrink-0">
                                                <div class="bg-white dark:bg-gray-900 p-4 rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm">
                                                    <div id="signature-preview" class="w-64 h-64 flex items-center justify-center bg-transparent">
                                                        {{-- Preview will be inserted here --}}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    <strong>File name:</strong> <span id="signature-filename">signature_{{ $signatureUser ? preg_replace('/[^a-zA-Z0-9]/', '_', $signatureUser->name) : 'user' }}.png</span>
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 max-w-md">
                                                    @if($hasExistingSignature)
                                                        This new signature will replace the existing one.
                                                    @else
                                                        Your signature will be saved as a square transparent PNG.
                                                    @endif
                                                </p>
                                                <div class="mt-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Ready to save
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Consent Agreement --}}
                <div class="border-t border-gray-200 pt-6 dark:border-gray-800">
                    <div class="flex items-start space-x-3">
                        <input
                            type="checkbox"
                            name="consent"
                            id="consent"
                            value="1"
                            class="mt-1 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800"
                            {{ old('consent') ? 'checked' : '' }}
                        >
                        <div>
                            <label for="consent" class="text-sm font-medium text-gray-700 dark:text-gray-400">
                                I agree to the terms and conditions of the loan agreement
                            </label>
                            <p class="text-xs text-gray-500 mt-1">
                                By checking this box, you acknowledge that you have read, understood, and agree to be bound by all terms and conditions of the loan agreement.
                            </p>
                        </div>
                    </div>
                    @error('consent')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <a href="{{ route('loans.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-transparent px-6 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-gray-500/30 dark:border-gray-700 dark:text-white/90 dark:hover:bg-gray-800">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg border border-brand-500 bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs transition-colors hover:border-brand-600 hover:bg-brand-600 focus:outline-hidden focus:ring-2 focus:ring-brand-500/30">
                        @if(auth()->user()->role === 'broker')
                            Submit Loan for Borrower
                        @else
                            Create Loan
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Loan Calculation Functions
    const borrowDateInput = document.getElementById('borrow_date');
    const loanTypeSelect = document.getElementById('loan_type_id');
    const dueDateDisplay = document.getElementById('due_date_display');
    const dueDateInput = document.getElementById('due_date');
    
    // Calculate and display due date when page loads
    calculateDueDate();
    
    // Recalculate when borrow date or loan type changes
    borrowDateInput.addEventListener('change', calculateDueDate);
    if (loanTypeSelect) {
        loanTypeSelect.addEventListener('change', calculateDueDate);
    }
    
    function calculateDueDate() {
        const borrowDate = new Date(borrowDateInput.value);
        if (isNaN(borrowDate.getTime())) return;
        
        let period = 10;
        let unit = 'days';
        
        if (loanTypeSelect && loanTypeSelect.selectedIndex > 0) {
            const selectedOption = loanTypeSelect.options[loanTypeSelect.selectedIndex];
            period = parseInt(selectedOption.dataset.period);
            unit = selectedOption.dataset.unit;
        }
        
        const dueDate = new Date(borrowDate);
        
        if (unit === 'days') {
            dueDate.setDate(dueDate.getDate() + period);
        } else if (unit === 'weeks') {
            dueDate.setDate(dueDate.getDate() + (period * 7));
        } else if (unit === 'months') {
            dueDate.setMonth(dueDate.getMonth() + period);
        } else if (unit === 'years') {
            dueDate.setFullYear(dueDate.getFullYear() + period);
        }
        
        const formattedDate = dueDate.toISOString().split('T')[0];
        dueDateDisplay.textContent = formattedDate;
        dueDateInput.value = formattedDate;
    }

    // Signature Pad Functionality
    let signaturePad = null;
    const CANVAS_SIZE = 400;
    
    // Check if user has existing signature (from PHP)
    const hasExistingSignature = {{ $hasExistingSignature ? 'true' : 'false' }};
    const existingSignatureUser = {!! $signatureUser ? json_encode(['id' => $signatureUser->id, 'name' => $signatureUser->name]) : 'null' !!};

    function initializeSignaturePad() {
        const canvas = document.querySelector('#signature-pad canvas');
        if (!canvas) return;
        
        // Set explicit square dimensions
        canvas.width = CANVAS_SIZE;
        canvas.height = CANVAS_SIZE;
        canvas.style.width = CANVAS_SIZE + 'px';
        canvas.style.height = CANVAS_SIZE + 'px';
        
        // Initialize with transparent background
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)',
            minWidth: 2,
            maxWidth: 4,
            throttle: 16,
            velocityFilterWeight: 0.7
        });

        // Handle existing signature checkbox
        const useExistingCheckbox = document.getElementById('use-existing-signature');
        const useExistingInput = document.getElementById('use-existing-signature-input');
        
        if (useExistingCheckbox && useExistingInput) {
            useExistingCheckbox.addEventListener('change', function() {
                useExistingInput.value = this.checked ? '1' : '0';
                updateSignatureStatus(this.checked ? 
                    'Using existing signature' : 
                    'Creating new signature', 
                    'text-blue-500'
                );
                
                // Clear signature pad if switching to existing signature
                if (this.checked && signaturePad) {
                    signaturePad.clear();
                    document.getElementById('signature-data').value = '';
                    hideSignaturePreview();
                }
            });
        }

        // Clear signature
        const clearButton = document.getElementById('clear-signature');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                signaturePad.clear();
                document.getElementById('signature-data').value = '';
                updateSignatureStatus('Signature cleared', 'text-gray-600');
                hideSignaturePreview();
                
                // If user has existing signature and they clear the new one, revert to existing
                if (hasExistingSignature && useExistingCheckbox) {
                    useExistingCheckbox.checked = true;
                    useExistingInput.value = '1';
                }
            });
        }

        // Save signature
        const saveButton = document.getElementById('save-signature');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
                if (signaturePad.isEmpty()) {
                    updateSignatureStatus('Please provide a signature first', 'text-red-500');
                    return;
                }

                // Create full square signature
                const signatureData = getFullSquareSignature();
                document.getElementById('signature-data').value = signatureData;
                
                // Update to use new signature
                if (useExistingCheckbox && useExistingInput) {
                    useExistingCheckbox.checked = false;
                    useExistingInput.value = '0';
                }
                
                updateSignatureStatus(
                    hasExistingSignature ? 
                    'New signature captured! This will replace the existing one.' : 
                    'Signature captured successfully!', 
                    'text-green-500'
                );
                showSignaturePreview(signatureData);
            });
        }

        // Handle touch/mouse events
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
        });
        
        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
        });

        // Auto-save on draw end
        canvas.addEventListener('mouseup', function() {
            if (!signaturePad.isEmpty()) {
                const signatureData = getFullSquareSignature();
                document.getElementById('signature-data').value = signatureData;
                
                // Auto-switch to new signature when user draws
                if (useExistingCheckbox && useExistingInput) {
                    useExistingCheckbox.checked = false;
                    useExistingInput.value = '0';
                }
                
                const statusEl = document.getElementById('signature-status');
                if (statusEl && !statusEl.textContent.includes('captured')) {
                    statusEl.textContent = hasExistingSignature ? 
                        'New signature auto-saved' : 
                        'Signature auto-saved';
                    statusEl.className = 'mt-2 text-sm text-blue-500';
                    
                    if (!document.getElementById('signature-preview-container').classList.contains('hidden')) {
                        showSignaturePreview(signatureData);
                    }
                }
            }
        });
    }

    function getFullSquareSignature() {
        return signaturePad.toDataURL('image/png');
    }

    function updateSignatureStatus(message, className) {
        const statusEl = document.getElementById('signature-status');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = 'mt-2 text-sm ' + className;
        }
    }

    function showSignaturePreview(dataUrl) {
        const container = document.getElementById('signature-preview-container');
        const preview = document.getElementById('signature-preview');
        const filename = document.getElementById('signature-filename');
        
        // Update filename with current user name
        const currentUserName = getCurrentUserName();
        filename.textContent = `signature_${currentUserName}.png`;
        
        // Clear previous preview
        preview.innerHTML = '';
        
        // Create and append the preview image
        const img = document.createElement('img');
        img.src = dataUrl;
        img.alt = 'Signature Preview';
        img.className = 'w-full h-full object-contain';
        preview.appendChild(img);
        
        // Show the container
        container.classList.remove('hidden');
    }

    function hideSignaturePreview() {
        const container = document.getElementById('signature-preview-container');
        container.classList.add('hidden');
    }

    function getCurrentUserName() {
        // Get current selected user name
        const userSelect = document.querySelector('select[name="user_id"]');
        if (userSelect && userSelect.selectedIndex > 0) {
            const selectedOption = userSelect.options[userSelect.selectedIndex];
            return selectedOption.text.split(' (')[0].replace(/[^a-zA-Z0-9]/g, '_');
        }
        
        // If we have a specific user from PHP
        if (existingSignatureUser) {
            return existingSignatureUser.name.replace(/[^a-zA-Z0-9]/g, '_');
        }
        
        // Fallback to current authenticated user
        const borrowerNameField = document.querySelector('p.dark\\:bg-dark-900');
        if (borrowerNameField) {
            return borrowerNameField.textContent.trim().replace(/[^a-zA-Z0-9]/g, '_');
        }
        
        return 'user';
    }

    // Handle user selection change (for admin/broker)
    const userSelect = document.querySelector('select[name="user_id"]');
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            const selectedUserId = this.value;
            const selectedOption = this.options[this.selectedIndex];
            const userName = selectedOption.text.split(' (')[0];
            
            // Reset signature state for new user selection
            if (signaturePad) {
                signaturePad.clear();
                document.getElementById('signature-data').value = '';
                hideSignaturePreview();
            }
            
            // Update the use existing signature option
            const useExistingCheckbox = document.getElementById('use-existing-signature');
            const useExistingInput = document.getElementById('use-existing-signature-input');
            const existingSignatureDisplay = document.getElementById('existing-signature-display');
            
            if (useExistingCheckbox && useExistingInput) {
                // For now, assume new user selection has no existing signature
                // In a real application, you might want to pre-load known signatures
                // or make an AJAX call to check
                useExistingCheckbox.checked = false;
                useExistingInput.value = '0';
                
                // Hide existing signature display if it exists
                if (existingSignatureDisplay) {
                    existingSignatureDisplay.style.display = 'none';
                }
                
                updateSignatureStatus(
                    `Creating new signature for ${userName}. If this user has an existing signature, it will be replaced.`, 
                    'text-blue-500'
                );
                
                // Update filename preview
                const filename = document.getElementById('signature-filename');
                if (filename) {
                    filename.textContent = `signature_${userName.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
                }
            }
        });
    }

    // Form Submission Handling
    const loanForm = document.querySelector('form');
    if (loanForm) {
        loanForm.addEventListener('submit', function(e) {
            const signatureData = document.getElementById('signature-data')?.value;
            const useExistingSignature = document.getElementById('use-existing-signature-input')?.value === '1';
            const consentChecked = document.getElementById('consent')?.checked;
            
            console.log('Form submission data:', {
                useExistingSignature,
                hasSignatureData: !!signatureData,
                signatureDataLength: signatureData?.length,
                consentChecked
            });
            
            // If using existing signature, no need for new signature data
            if (useExistingSignature) {
                document.getElementById('signature-data').value = '';
                console.log('Using existing signature - clearing signature data field');
            }
            
            // Validate signature if consent is given and not using existing signature
            if (consentChecked && !useExistingSignature && (!signatureData || signatureData.trim() === '') && 
                (!signaturePad || signaturePad.isEmpty())) {
                if (!confirm('You have given consent but no signature is provided. Do you want to continue without a signature?')) {
                    e.preventDefault();
                    updateSignatureStatus('Signature is required when giving consent', 'text-red-500');
                    document.getElementById('signature-section').scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return;
                }
            }
            
            // Auto-save full square signature if drawn but not saved and not using existing
            if (!useExistingSignature && signaturePad && !signaturePad.isEmpty() && (!signatureData || signatureData.trim() === '')) {
                const autoSignatureData = getFullSquareSignature();
                document.getElementById('signature-data').value = autoSignatureData;
                console.log('Full square signature auto-saved on form submission');
            }
            
            // Show loading state
            const submitButton = loanForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...</span>';
            }
            
            // Final validation message
            if (useExistingSignature) {
                console.log('Form will use existing signature for loan agreement');
                updateSignatureStatus('Using existing signature for loan agreement', 'text-green-500');
            } else if (signatureData) {
                console.log('Form will use new signature for loan agreement');
                updateSignatureStatus('New signature will be used for loan agreement', 'text-green-500');
            }
        });
    }

    // Initialize signature pad
    initializeSignaturePad();

    // Log initial state
    console.log('Loan form initialized with signature state:', {
        hasExistingSignature,
        existingSignatureUser,
        currentUser: getCurrentUserName()
    });
});
</script>
@endsection