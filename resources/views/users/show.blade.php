@extends('layouts.app')

@section('content')
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: 'User Profile' }">
        @include('partials.breadcrumb')
    </div>
    <!-- Breadcrumb End -->

    <!-- Main Content -->
    <div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
        <!-- Profile Header -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6 mb-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex w-full flex-col items-center gap-6 xl:flex-row">
                    <div class="h-20 w-20 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                        @php
                            $avatarUrl = null;
                            if ($user->avatar) {
                                $avatarUrl = Storage::url($user->avatar);
                            } elseif ($user->profile_photo_path) {
                                $avatarUrl = asset('storage/' . $user->profile_photo_path);
                            }
                        @endphp
                        @if($avatarUrl)
                            <img 
                                src="{{ $avatarUrl }}" 
                                alt="Profile Photo" 
                                class="h-full w-full object-cover"
                            >
                        @else
                            @php
                                $names = explode(' ', trim($user->name));
                                $initials = strtoupper(
                                    collect($names)
                                        ->take(2)
                                        ->map(fn($n) => substr($n, 0, 1))
                                        ->implode('')
                                );
                            @endphp
                    
                            <span class="text-xl font-semibold text-gray-600 dark:text-gray-300">
                                {{ $initials }}
                            </span>
                        @endif
                    </div>

                    <div class="order-3 xl:order-2">
                        <h4 class="mb-2 text-center text-lg font-semibold xl:text-left">{{ $user->name }}</h4>
                        <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($user->role) }} Profile</p>
                            @if($user->role === 'borrower' && $user->borrower)
                                <div class="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block"></div>
                                <div class="flex items-center">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($user->borrower->risk_category === 'Low Risk') bg-green-100 text-green-800
                                        @elseif($user->borrower->risk_category === 'Medium Risk') bg-yellow-100 text-yellow-800
                                        @elseif($user->borrower->risk_category === 'High Risk') bg-orange-100 text-orange-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $user->borrower->risk_category }}
                                    </span>
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                        4Cs Score: {{ $user->borrower->calculate4csScore()['overall'] }}/100
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="order-2 flex grow items-center gap-2 xl:order-3 xl:justify-end">
                        @if($user->role === 'borrower')
                            <a href="{{ route('loans.create', $user->id) }}" 
                               class="flex items-center gap-2 rounded-full border border-blue-600 bg-blue-600 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-blue-700 hover:border-blue-700 transition-colors">
                                <i class="fas fa-plus"></i> New Loan
                            </a>
                        @endif
                        
                        <!-- Edit Profile Button -->
                        <button onclick="document.getElementById('editProfileModal').classList.remove('hidden')"
                            class="flex items-center gap-2 rounded-full border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        @include('partials.card.users-card', ['user' => $user])

        <!-- Main Tabbed Content -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <!-- Tab Header -->
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">User Profile Details</h3>
            </div>

            <!-- Tab Body -->
            <div class="p-6">
                <div x-data="{ activeTab: 'profile' }" class="space-y-6">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 dark:border-gray-800">
                        <nav class="-mb-px flex space-x-2 overflow-x-auto [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 dark:[&::-webkit-scrollbar-thumb]:bg-gray-600 dark:[&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar]:h-1.5">
                            <!-- Profile Tab -->
                            <button
                                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-200 ease-in-out whitespace-nowrap"
                                x-bind:class="activeTab === 'profile' ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                x-on:click="activeTab = 'profile'"
                            >
                                <i class="fas fa-user-circle text-lg"></i>
                                Profile
                                @if($user->role === 'borrower' && !$user->hasCompleteBiodata())
                                    @php $overallPercentage = $user->getBiodataCompletionPercentage(); @endphp
                                    <span class="inline-flex items-center justify-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-500">
                                        {{ $overallPercentage }}%
                                    </span>
                                @endif
                            </button>

                            <!-- Documents Tab -->
                            <button
                                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-200 ease-in-out whitespace-nowrap"
                                x-bind:class="activeTab === 'documents' ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                x-on:click="activeTab = 'documents'"
                            >
                                <i class="fas fa-file-alt text-lg"></i>
                                Documents
                                @php
                                    $docCount = 0;
                                    if($user->avatar) $docCount++;
                                    if($user->id_front_path) $docCount++;
                                    if($user->id_back_path) $docCount++;
                                    if($user->signature) $docCount++;
                                @endphp
                                <span class="inline-flex items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                                    {{ $docCount }}/4
                                </span>
                            </button>

                            <!-- Employment Tab (Only for borrowers) -->
                            @if($user->role === 'borrower')
                            <button
                                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-200 ease-in-out whitespace-nowrap"
                                x-bind:class="activeTab === 'employment' ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                x-on:click="activeTab = 'employment'"
                            >
                                <i class="fas fa-briefcase text-lg"></i>
                                Employment
                            </button>
                            @endif

                            <!-- Next of Kin Tab -->
                            @if($user->kin_name)
                            <button
                                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-200 ease-in-out whitespace-nowrap"
                                x-bind:class="activeTab === 'kin' ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                x-on:click="activeTab = 'kin'"
                            >
                                <i class="fas fa-users text-lg"></i>
                                Next of Kin
                            </button>
                            @endif

                            <!-- Loan History Tab -->
                            <button
                                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-200 ease-in-out whitespace-nowrap"
                                x-bind:class="activeTab === 'loans' ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                x-on:click="activeTab = 'loans'"
                            >
                                <i class="fas fa-history text-lg"></i>
                                Loan History
                                <span class="inline-flex items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">
                                    {{ $user->loans->count() }}
                                </span>
                            </button>

                            <!-- Recovery Tab -->
                            @if(in_array($user->role, ['admin', 'teller', 'borrower']) && $user->debtRecoveryCases->count() > 0)
                            <button
                                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-200 ease-in-out whitespace-nowrap"
                                x-bind:class="activeTab === 'recovery' ? 'text-brand-500 border-brand-500 dark:text-brand-400 dark:border-brand-400' : 'bg-transparent text-gray-500 border-transparent hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                                x-on:click="activeTab = 'recovery'"
                            >
                                <i class="fas fa-gavel text-lg"></i>
                                Recovery
                                <span class="inline-flex items-center justify-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-600 dark:bg-red-500/15 dark:text-red-400">
                                    {{ $user->debtRecoveryCases->count() }}
                                </span>
                            </button>
                            @endif
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="pt-4">
                        <!-- Profile Tab Content -->
                        <div x-show="activeTab === 'profile'" x-cloak>
                            <!-- Profile Completion Alert -->
                            @if($user->role === 'borrower' && !$user->hasCompleteBiodata())
                                @php
                                    $completionData = $user->getBiodataCompletionBySections();
                                    $overallPercentage = $user->getBiodataCompletionPercentage();
                                @endphp
                                <div class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 p-5 dark:border-yellow-800 dark:bg-yellow-900/20">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-500"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200">
                                                    Profile Incomplete ({{ $overallPercentage }}%)
                                                </h4>
                                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                                    Complete your profile to get better loan terms
                                                </p>
                                            </div>
                                        </div>
                                        <button onclick="document.getElementById('editProfileModal').classList.remove('hidden')"
                                            class="inline-flex items-center gap-2 rounded-lg border border-yellow-500 bg-yellow-500 px-4 py-2 text-sm font-medium text-yellow-900 transition-colors hover:bg-yellow-600">
                                            Complete Now <i class="fas fa-arrow-right ml-1"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Progress Grid -->
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        @foreach($completionData['sections'] as $sectionName => $section)
                                            <div class="rounded-lg border border-yellow-100 bg-white/50 p-3 dark:border-yellow-800/50 dark:bg-gray-800/30">
                                                <div class="mb-2 flex items-center justify-between">
                                                    <span class="text-xs font-medium text-yellow-800 dark:text-yellow-300">{{ $section['name'] }}</span>
                                                    <span class="text-xs font-bold text-yellow-700 dark:text-yellow-400">{{ $section['percentage'] }}%</span>
                                                </div>
                                                <div class="h-1.5 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                                    <div class="h-full rounded-full bg-yellow-500 transition-all duration-300" 
                                                         style="width: {{ $section['percentage'] }}%"></div>
                                                </div>
                                                @if(count($section['missing']) > 0)
                                                    <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400 truncate" 
                                                       title="{{ implode(', ', $section['missing']) }}">
                                                        Missing {{ count($section['missing']) }} field(s)
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Personal Information Grid -->
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <!-- Basic Information -->
                                <div class="space-y-6">
                                    <div>
                                        <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Basic Information</h4>
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Full Name</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Email Address</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->email }}</span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Phone Number</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->phone ?? 'N/A' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Gender</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">{{ ucfirst($user->gender) ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Personal Details -->
                                    <div>
                                        <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Personal Details</h4>
                                        <div class="space-y-4">
                                            @if($user->dob)
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Date of Birth</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">
                                                    {{ \Carbon\Carbon::parse($user->dob)->format('M d, Y') }} 
                                                    <span class="text-xs text-gray-500">({{ $user->age ?? 'N/A' }} years)</span>
                                                </span>
                                            </div>
                                            @endif
                                            
                                            @if($user->nationality)
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Nationality</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->nationality }}</span>
                                            </div>
                                            @endif
                                            
                                            @if($user->id_number)
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $user->id_type ?? 'ID' }} Number</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->id_number }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Information -->
                                <div class="space-y-6">
                                    <!-- Status Information -->
                                    <div>
                                        <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Status Information</h4>
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">User Status</span>
                                                <span class="rounded-full px-3 py-1 text-xs font-medium
                                                    @if($user->status === 0)
                                                        bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-500
                                                    @elseif($user->status === 1)
                                                        bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-500
                                                    @endif
                                                ">
                                                    {{ $user->status === 0 ? 'Active' : 'Inactive' }}
                                                </span>

                                            </div>
                                            
                                            @if($user->role === 'borrower' && $user->borrower)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Borrower Status</span>
                                            
                                                    <span class="rounded-full px-3 py-1 text-xs font-medium
                                                        @if((int) $user->borrower->status === 1)
                                                            bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-500
                                                        @else
                                                            bg-gray-100 text-gray-800 dark:bg-gray-500/15 dark:text-gray-400
                                                        @endif
                                                    ">
                                                        {{ (int) $user->borrower->status === 1 ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>
                                            @endif
                                            
                                                                                        
                                            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Account Created</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">
                                                    {{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y') }}
                                                </span>
                                            </div>
                                            
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Last Updated</span>
                                                <span class="font-medium text-gray-800 dark:text-white/90">
                                                    {{ \Carbon\Carbon::parse($user->updated_at)->format('M d, Y') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Details -->
                                    <div>
                                        <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Additional Details</h4>
                                        <div class="grid grid-cols-2 gap-4">
                                            @if($user->marital_status)
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Marital Status</p>
                                                <p class="font-medium text-gray-800 dark:text-white/90">{{ ucfirst($user->marital_status) }}</p>
                                            </div>
                                            @endif
                                            
                                            @if($user->education)
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Education</p>
                                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $user->education }}</p>
                                            </div>
                                            @endif
                                            
                                            @if($user->religion)
                                            <div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">Religion</p>
                                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $user->religion }}</p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab Content -->
                        <div x-show="activeTab === 'documents'" x-cloak>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                                <!-- Passport Photo -->
                                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Passport Photo</h4>
                                        @if($user->avatar)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-500">
                                                Uploaded
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">
                                                Missing
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex justify-center">
                                        @if($user->avatar)
                                            <div class="relative group w-full">
                                                <img 
                                                    src="{{ Storage::url($user->avatar) }}" 
                                                    alt="Passport Photo"
                                                    class="h-48 w-full rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                                >
                                                <a href="{{ Storage::url($user->avatar) }}" target="_blank"
                                                   class="absolute inset-0 rounded-lg flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition-all">
                                                    <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="h-48 w-full rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                                                <div class="text-center">
                                                    <i class="fas fa-camera text-gray-400 dark:text-gray-500 text-3xl mx-auto mb-3"></i>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No Photo</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @if($user->avatar)
                                        <div class="mt-4 flex justify-center">
                                            <a href="{{ Storage::url($user->avatar) }}" target="_blank"
                                               class="inline-flex items-center gap-2 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <!-- ID Front -->
                                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                                    <div class="mb-4 flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">ID Front</h4>
                                            @if($user->id_type)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->id_type }}</p>
                                            @endif
                                        </div>
                                        @if($user->id_front_path)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-500">
                                                Uploaded
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">
                                                Missing
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex justify-center">
                                        @if($user->id_front_path)
                                            <div class="relative group w-full">
                                                <img
                                                    src="{{ Storage::url($user->id_front_path) }}"
                                                    alt="ID Front"
                                                    class="h-48 w-full rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                                >
                                                <a href="{{ Storage::url($user->id_front_path) }}" target="_blank"
                                                   class="absolute inset-0 rounded-lg flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition-all">
                                                    <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="h-48 w-full rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                                                <div class="text-center">
                                                    <i class="fas fa-id-card text-gray-400 dark:text-gray-500 text-3xl mx-auto mb-3"></i>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No ID Front</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @if($user->id_front_path)
                                        <div class="mt-4 flex justify-center">
                                            <a href="{{ Storage::url($user->id_front_path) }}" target="_blank"
                                               class="inline-flex items-center gap-2 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <!-- ID Back -->
                                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                                    <div class="mb-4 flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">ID Back</h4>
                                            @if($user->id_type)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->id_type }}</p>
                                            @endif
                                        </div>
                                        @if($user->id_back_path)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-500">
                                                Uploaded
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">
                                                Missing
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex justify-center">
                                        @if($user->id_back_path)
                                            <div class="relative group w-full">
                                                <img
                                                    src="{{ Storage::url($user->id_back_path) }}"
                                                    alt="ID Back"
                                                    class="h-48 w-full rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                                >
                                                <a href="{{ Storage::url($user->id_back_path) }}" target="_blank"
                                                   class="absolute inset-0 rounded-lg flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition-all">
                                                    <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="h-48 w-full rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                                                <div class="text-center">
                                                    <i class="fas fa-id-card text-gray-400 dark:text-gray-500 text-3xl mx-auto mb-3"></i>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No ID Back</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @if($user->id_back_path)
                                        <div class="mt-4 flex justify-center">
                                            <a href="{{ Storage::url($user->id_back_path) }}" target="_blank"
                                               class="inline-flex items-center gap-2 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <!-- Signature -->
                                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-white/[0.03]">
                                    <div class="mb-4 flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Digital Signature</h4>
                                        @if($user->signature)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-500/15 dark:text-green-500">
                                                Uploaded
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">
                                                Missing
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex justify-center">
                                        @if($user->signature)
                                            <div class="relative group w-full">
                                                <img
                                                    src="{{ Storage::url($user->signature) }}"
                                                    alt="Digital Signature"
                                                    class="h-48 w-full rounded-lg object-contain border border-gray-200 dark:border-gray-700 bg-white p-4"
                                                >
                                                <a href="{{ Storage::url($user->signature) }}" target="_blank"
                                                   class="absolute inset-0 rounded-lg flex items-center justify-center bg-black/0 group-hover:bg-black/30 transition-all">
                                                    <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="h-48 w-full rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                                                <div class="text-center">
                                                    <i class="fas fa-signature text-gray-400 dark:text-gray-500 text-3xl mx-auto mb-3"></i>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No Signature</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @if($user->signature)
                                        <div class="mt-4 flex justify-center">
                                            <a href="{{ Storage::url($user->signature) }}" target="_blank"
                                               class="inline-flex items-center gap-2 text-sm font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300">
                                                <i class="fas fa-external-link-alt"></i> View Full Size
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Employment Tab Content -->
                        @if($user->role === 'borrower')
                        <div x-show="activeTab === 'employment'" x-cloak>
                            @if($user->borrower && $user->borrower->income_type)
                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <!-- Employment Details -->
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Employment Details</h4>
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Client Type</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">
                                                        {{ $user->borrower->client_type === 0 ? 'Individual' : 'Non-Individual/Corporate' }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Employment Status</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">
                                                        {{ ucfirst(str_replace('_', ' ', $user->borrower->income_type)) }}
                                                    </span>
                                                </div>
                                                @if($user->borrower->job_title)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Job Title</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->borrower->job_title }}</span>
                                                </div>
                                                @endif
                                                @if($user->borrower->department)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Department</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->borrower->department }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Salary Information -->
                                        <div>
                                            <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Salary Information</h4>
                                            <div class="space-y-4">
                                                @if($user->borrower->gross_salary)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Gross Salary</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">KES {{ number_format($user->borrower->gross_salary, 2) }}</span>
                                                </div>
                                                @endif
                                                @if($user->borrower->net_salary)
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Net Salary</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">KES {{ number_format($user->borrower->net_salary, 2) }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Organization Details -->
                                    <div class="space-y-6">
                                        <div>
                                            <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Organization Details</h4>
                                            <div class="space-y-4">
                                                @if($user->borrower->employer_name)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Employer Name</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->borrower->employer_name }}</span>
                                                </div>
                                                @endif
                                                @if($user->borrower->workplace)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Workplace</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->borrower->workplace }}</span>
                                                </div>
                                                @endif
                                                @if($user->borrower->employer_email)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Organization Email</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->borrower->employer_email }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <i class="fas fa-briefcase text-gray-400 text-4xl mb-4"></i>
                                    <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">No Employment Information</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Employment information has not been added yet</p>
                                    <button onclick="document.getElementById('editProfileModal').classList.remove('hidden')"
                                        class="inline-flex items-center gap-2 rounded-lg border border-brand-500 bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs transition-colors hover:bg-brand-600">
                                        <i class="fas fa-plus"></i> Add Employment Details
                                    </button>
                                </div>
                            @endif
                        </div>
                        @endif

                        <!-- Next of Kin Tab Content -->
                        <div x-show="activeTab === 'kin'" x-cloak>
                            @if($user->kin_name)
                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <div class="space-y-6">
                                        <!-- Kin Details -->
                                        <div>
                                            <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Next of Kin Details</h4>
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Full Name</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->kin_name }}</span>
                                                </div>
                                                @if($user->kin_relation)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Relationship</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->kin_relation }}</span>
                                                </div>
                                                @endif
                                                @if($user->kin_phone)
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Phone Number</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->kin_phone }}</span>
                                                </div>
                                                @endif
                                                @if($user->kin_email)
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Email Address</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->kin_email }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Kin Information -->
                                    <div class="space-y-6">
                                        @if($user->kin_occupation)
                                        <div>
                                            <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Additional Information</h4>
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">Occupation</span>
                                                    <span class="font-medium text-gray-800 dark:text-white/90">{{ $user->kin_occupation }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                                    <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">No Next of Kin Information</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Next of kin information has not been added yet</p>
                                    <button onclick="document.getElementById('editProfileModal').classList.remove('hidden')"
                                        class="inline-flex items-center gap-2 rounded-lg border border-brand-500 bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs transition-colors hover:bg-brand-600">
                                        <i class="fas fa-plus"></i> Add Next of Kin
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Loan History Tab Content -->
                        <div x-show="activeTab === 'loans'" x-cloak>
                            @if($user->loans->count() > 0)
                                @include('partials.table.table-loans', ['loans' => $user->loans, 'userView' => true])
                            @else
                                <div class="text-center py-8">
                                    <i class="fas fa-history text-gray-400 text-4xl mb-4"></i>
                                    <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">No Loan History</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">This user has no loan history yet</p>
                                    @if($user->role === 'borrower')
                                        <a href="{{ route('loans.create', $user->id) }}" 
                                           class="inline-flex items-center gap-2 rounded-lg border border-brand-500 bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs transition-colors hover:bg-brand-600">
                                            <i class="fas fa-plus"></i> Create First Loan
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Recovery Tab Content -->
                        <div x-show="activeTab === 'recovery'" x-cloak>
                            @if($user->debtRecoveryCases->count() > 0)
                                @foreach($user->debtRecoveryCases as $case)
                                <div class="mb-6 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                                                Case #{{ $case->case_number }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Started: {{ $case->created_at->format('M d, Y') }}
                                                @if($case->loan)
                                                <span class="ml-4">Loan: #{{ $case->loan->loan_reference ?? $case->loan->id }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @include('partials.recovery.status-badge', ['status' => $case->status])
                                            @include('partials.recovery.priority-badge', ['priority' => $case->priority])
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Total Debt</p>
                                            <p class="text-lg font-bold text-red-600">KES {{ number_format($case->total_debt_amount, 2) }}</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Recovered</p>
                                            <p class="text-lg font-bold text-green-600">KES {{ number_format($case->getTotalRecovered(), 2) }}</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Remaining</p>
                                            <p class="text-lg font-bold text-orange-600">KES {{ number_format($case->getRemainingBalance(), 2) }}</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Days in Default</p>
                                            <p class="text-lg font-bold {{ $case->days_in_default > 90 ? 'text-red-600' : ($case->days_in_default > 30 ? 'text-orange-600' : 'text-gray-600') }}">
                                                {{ $case->days_in_default }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    @if($case->actions->count() > 0)
                                    <div class="mt-4">
                                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Actions</h5>
                                        @include('partials.recovery.action-timeline', ['actions' => $case->actions->take(3)])
                                        <a href="{{ route('recovery.cases.show', $case) }}" class="text-sm text-brand-600 hover:text-brand-700">
                                            View All Actions →
                                        </a>
                                    </div>
                                    @endif

                                    @if($case->paymentPlans->count() > 0)
                                    @php $activePlan = $case->getActivePaymentPlan(); @endphp
                                    @if($activePlan)
                                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                                    Active Payment Plan
                                                </p>
                                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                                    {{ $activePlan->installment_frequency }} installments of KES {{ number_format($activePlan->installment_amount, 2) }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                                    {{ $activePlan->progress_percentage }}% Complete
                                                </p>
                                                <div class="w-32 bg-blue-200 rounded-full h-2 dark:bg-blue-800">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $activePlan->progress_percentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h4 class="mt-2 text-lg font-medium text-gray-700 dark:text-gray-300">No Recovery Cases</h4>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This user has no debt recovery cases</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 z-99999 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen p-5">
            <!-- Backdrop -->
            <div onclick="document.getElementById('editProfileModal').classList.add('hidden')" 
                 class="fixed inset-0 bg-gray-400/50 backdrop-blur-[32px]"></div>
            
            <!-- Modal Content -->
            <div class="relative w-full max-w-4xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 z-50 max-h-[90vh] overflow-y-auto">
                <!-- close btn -->
                <button onclick="document.getElementById('editProfileModal').classList.add('hidden')" 
                        class="group absolute right-3 top-3 z-999 flex h-9.5 w-9.5 items-center justify-center rounded-full bg-gray-200 text-gray-500 transition-colors hover:bg-gray-300 hover:text-gray-500 dark:bg-gray-800 dark:hover:bg-gray-700 sm:right-6 sm:top-6 sm:h-11 sm:w-11">
                    <svg class="transition-colors fill-current group-hover:text-gray-600 dark:group-hover:text-gray-200" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5413C5.65237 16.9318 5.65237 17.565 6.04289 17.9555C6.43342 18.346 7.06658 18.346 7.45711 17.9555L11.9987 13.4139L16.5408 17.956C16.9313 18.3466 17.5645 18.3466 17.955 17.956C18.3455 17.5655 18.3455 16.9323 17.955 16.5418L13.4129 11.9997L17.955 7.4576C18.3455 7.06707 18.3455 6.43391 17.955 6.04338C17.5645 5.65286 16.9313 5.65286 16.5408 6.04338L11.9987 10.5855L7.45711 6.0439C7.06658 5.65338 6.43342 5.65338 6.04289 6.0439C5.65237 6.43442 5.65237 7.06759 6.04289 7.45811L10.5845 11.9997L6.04289 16.5413Z" fill=""/>
                    </svg>
                </button>

                <form method="POST" action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data" id="editProfileForm" class="space-y-8">
                    @csrf
                    @method('PUT')
            
                    
                    <h4 class="text-lg font-medium text-gray-800 dark:text-white/90 mb-6">
                        Edit User Profile
                    </h4>
                    
                    <input type="hidden" name="status" value="{{ $user->status }}">
                    @if($user->role === 'borrower' && $user->borrower)
                        <input type="hidden" name="borrower_status" value="{{ $user->borrower->status }}">
                    @endif

                    <!-- Section 1: Basic Information -->
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h5 class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Basic Information</h5>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Full Name *
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Email Address *
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Phone Number *
                                </label>
                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone', $user->phone) }}"
                                    required
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Gender
                                </label>
                                <select name="gender" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Date of Birth
                                </label>
                                <input
                                    type="date"
                                    name="dob"
                                    value="{{ old('dob', $user->dob ? \Carbon\Carbon::parse($user->dob)->format('Y-m-d') : '') }}"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                                @if($user->dob)
                                    <p class="text-xs text-gray-500 mt-1">
                                        Current: {{ \Carbon\Carbon::parse($user->dob)->format('M d, Y') }}
                                        ({{ $user->age ?? 'N/A' }} years old)
                                    </p>
                                @endif
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Nationality
                                </label>
                                <select name="nationality" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Nationality</option>
                                    @if(class_exists('PragmaRX\Countries\Package\Countries'))
                                        @foreach(\PragmaRX\Countries\Package\Countries::all()->pluck('name.common')->sort() as $country)
                                            <option value="{{ $country }}" {{ old('nationality', $user->nationality) == $country ? 'selected' : '' }}>{{ $country }}</option>
                                        @endforeach
                                    @else
                                        <!-- Fallback to common countries -->
                                        <option value="Kenyan" {{ old('nationality', $user->nationality) == 'Kenyan' ? 'selected' : '' }}>Kenyan</option>
                                        <option value="Ugandan" {{ old('nationality', $user->nationality) == 'Ugandan' ? 'selected' : '' }}>Ugandan</option>
                                        <option value="Tanzanian" {{ old('nationality', $user->nationality) == 'Tanzanian' ? 'selected' : '' }}>Tanzanian</option>
                                        <option value="Rwandan" {{ old('nationality', $user->nationality) == 'Rwandan' ? 'selected' : '' }}>Rwandan</option>
                                        <option value="Burundian" {{ old('nationality', $user->nationality) == 'Burundian' ? 'selected' : '' }}>Burundian</option>
                                    @endif
                                </select>
                            </div>

                            @if(auth()->user()->role === 'admin')
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Role *
                                </label>
                                <select name="role" required class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="borrower" {{ old('role', $user->role) == 'borrower' ? 'selected' : '' }}>Borrower</option>
                                    <option value="broker" {{ old('role', $user->role) == 'broker' ? 'selected' : '' }}>Broker</option>
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="teller" {{ old('role', $user->role) == 'teller' ? 'selected' : '' }}>Teller</option>
                                </select>
                            </div>
                            @else
                            <input type="hidden" name="role" value="{{ $user->role }}">
                            @endif

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Password (Leave blank to keep current)
                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    placeholder="Enter new password"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Personal Details -->
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h5 class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Personal Details</h5>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    ID Type
                                </label>
                                <select name="id_type" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select ID Type</option>
                                    <option value="National ID" {{ old('id_type', $user->id_type) == 'National ID' ? 'selected' : '' }}>National ID</option>
                                    <option value="Passport" {{ old('id_type', $user->id_type) == 'Passport' ? 'selected' : '' }}>Passport</option>
                                    <option value="Driving License" {{ old('id_type', $user->id_type) == 'Driving License' ? 'selected' : '' }}>Driving License</option>
                                    <option value="Alien ID" {{ old('id_type', $user->id_type) == 'Alien ID' ? 'selected' : '' }}>Alien ID</option>
                                    <option value="Military ID" {{ old('id_type', $user->id_type) == 'Military ID' ? 'selected' : '' }}>Military ID</option>
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    ID Number
                                </label>
                                <input
                                    type="text"
                                    name="id_number"
                                    value="{{ old('id_number', $user->id_number) }}"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Marital Status
                                </label>
                                <select name="marital_status" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Status</option>
                                    <option value="single" {{ old('marital_status', $user->marital_status) == 'single' ? 'selected' : '' }}>Single</option>
                                    <option value="married" {{ old('marital_status', $user->marital_status) == 'married' ? 'selected' : '' }}>Married</option>
                                    <option value="divorced" {{ old('marital_status', $user->marital_status) == 'divorced' ? 'selected' : '' }}>Divorced</option>
                                    <option value="separated" {{ old('marital_status', $user->marital_status) == 'separated' ? 'selected' : '' }}>Separated</option>
                                    <option value="widowed" {{ old('marital_status', $user->marital_status) == 'widowed' ? 'selected' : '' }}>Widowed</option>
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Education Level
                                </label>
                                <select name="education" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Education Level</option>
                                    <option value="No Formal Education" {{ old('education', $user->education) == 'No Formal Education' ? 'selected' : '' }}>No Formal Education</option>
                                    <option value="Primary School" {{ old('education', $user->education) == 'Primary School' ? 'selected' : '' }}>Primary School</option>
                                    <option value="Secondary School" {{ old('education', $user->education) == 'Secondary School' ? 'selected' : '' }}>Secondary School</option>
                                    <option value="High School" {{ old('education', $user->education) == 'High School' ? 'selected' : '' }}>High School</option>
                                    <option value="Certificate" {{ old('education', $user->education) == 'Certificate' ? 'selected' : '' }}>Certificate</option>
                                    <option value="Diploma" {{ old('education', $user->education) == 'Diploma' ? 'selected' : '' }}>Diploma</option>
                                    <option value="Bachelor's Degree" {{ old('education', $user->education) == 'Bachelor\'s Degree' ? 'selected' : '' }}>Bachelor's Degree</option>
                                    <option value="Master's Degree" {{ old('education', $user->education) == 'Master\'s Degree' ? 'selected' : '' }}>Master's Degree</option>
                                    <option value="Doctorate" {{ old('education', $user->education) == 'Doctorate' ? 'selected' : '' }}>Doctorate</option>
                                    <option value="Other" {{ old('education', $user->education) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Religion
                                </label>
                                <select name="religion" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Religion</option>
                                    <option value="Christianity" {{ old('religion', $user->religion) == 'Christianity' ? 'selected' : '' }}>Christianity</option>
                                    <option value="Islam" {{ old('religion', $user->religion) == 'Islam' ? 'selected' : '' }}>Islam</option>
                                    <option value="Hinduism" {{ old('religion', $user->religion) == 'Hinduism' ? 'selected' : '' }}>Hinduism</option>
                                    <option value="Buddhism" {{ old('religion', $user->religion) == 'Buddhism' ? 'selected' : '' }}>Buddhism</option>
                                    <option value="Traditional African" {{ old('religion', $user->religion) == 'Traditional African' ? 'selected' : '' }}>Traditional African</option>
                                    <option value="Atheist" {{ old('religion', $user->religion) == 'Atheist' ? 'selected' : '' }}>Atheist</option>
                                    <option value="Agnostic" {{ old('religion', $user->religion) == 'Agnostic' ? 'selected' : '' }}>Agnostic</option>
                                    <option value="Other" {{ old('religion', $user->religion) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Next of Kin -->
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h5 class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Next of Kin</h5>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Name *
                                </label>
                                <input
                                    type="text"
                                    name="kin_name"
                                    value="{{ old('kin_name', $user->kin_name) }}"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Relationship *
                                </label>
                                <input
                                    type="text"
                                    name="kin_relation"
                                    value="{{ old('kin_relation', $user->kin_relation) }}"
                                    placeholder="e.g., Spouse, Parent, Sibling"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Phone Number
                                </label>
                                <input
                                    type="text"
                                    name="kin_phone"
                                    value="{{ old('kin_phone', $user->kin_phone) }}"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    name="kin_email"
                                    value="{{ old('kin_email', $user->kin_email) }}"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Occupation
                                </label>
                                <input
                                    type="text"
                                    name="kin_occupation"
                                    value="{{ old('kin_occupation', $user->kin_occupation) }}"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Employment Information (Only for Borrowers) -->
                    @if($user->role === 'borrower')
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h5 class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Employment Information</h5>
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Client Type *
                                </label>
                                <select name="client_type" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Client Type</option>
                                    <option value="individual" {{ old('client_type', $user->borrower->client_type ?? '') == 'individual' || (isset($user->borrower) && $user->borrower->client_type == 0) ? 'selected' : '' }}>Individual</option>
                                    <option value="non_individual" {{ old('client_type', $user->borrower->client_type ?? '') == 'non_individual' || (isset($user->borrower) && $user->borrower->client_type == 1) ? 'selected' : '' }}>Non-Individual/Corporate</option>
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Employment Status *
                                </label>
                                <select name="income_type" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                    <option value="">Select Employment Status</option>
                                    <option value="employed" {{ old('income_type', $user->borrower->income_type ?? '') == 'employed' ? 'selected' : '' }}>Employed</option>
                                    <option value="self_employed" {{ old('income_type', $user->borrower->income_type ?? '') == 'self_employed' ? 'selected' : '' }}>Self Employed</option>
                                    <option value="business" {{ old('income_type', $user->borrower->income_type ?? '') == 'business' ? 'selected' : '' }}>Business Owner</option>
                                    <option value="contract" {{ old('income_type', $user->borrower->income_type ?? '') == 'contract' ? 'selected' : '' }}>Contract Worker</option>
                                    <option value="freelance" {{ old('income_type', $user->borrower->income_type ?? '') == 'freelance' ? 'selected' : '' }}>Freelancer</option>
                                    <option value="casual" {{ old('income_type', $user->borrower->income_type ?? '') == 'casual' ? 'selected' : '' }}>Casual Worker</option>
                                    <option value="student" {{ old('income_type', $user->borrower->income_type ?? '') == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="unemployed" {{ old('income_type', $user->borrower->income_type ?? '') == 'unemployed' ? 'selected' : '' }}>Unemployed</option>
                                </select>
                            </div>

                            <!-- Organization Information -->
                            <div class="col-span-2">
                                <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Organization Information</h6>
                                <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Organization/Employer Name
                                        </label>
                                        <input
                                            type="text"
                                            name="employer_name"
                                            value="{{ old('employer_name', $user->borrower->employer_name ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>

                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Workplace/Organization Location
                                        </label>
                                        <input
                                            type="text"
                                            name="workplace"
                                            value="{{ old('workplace', $user->borrower->workplace ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>

                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Organization Email
                                        </label>
                                        <input
                                            type="email"
                                            name="employer_email"
                                            value="{{ old('employer_email', $user->borrower->employer_email ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Position/Job Information -->
                            <div class="col-span-2">
                                <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Position Information</h6>
                                <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Job Title/Position
                                        </label>
                                        <input
                                            type="text"
                                            name="job_title"
                                            value="{{ old('job_title', $user->borrower->job_title ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>

                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Department
                                        </label>
                                        <input
                                            type="text"
                                            name="department"
                                            value="{{ old('department', $user->borrower->department ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>

                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Gross Salary (KES)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            name="gross_salary"
                                            value="{{ old('gross_salary', $user->borrower->gross_salary ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>

                                    <div class="col-span-1">
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                            Net Salary (KES)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            name="net_salary"
                                            value="{{ old('net_salary', $user->borrower->net_salary ?? '') }}"
                                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Section 5: Document Uploads -->
                    <div class="pb-6">
                        <h5 class="mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Document Uploads</h5>
                        
                        <!-- Passport Photo & Signature Row -->
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 mb-6 sm:grid-cols-2">
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Passport Photo
                                </label>
                                <input
                                    type="file"
                                    name="avatar"
                                    accept="image/*"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                                @if($user->avatar)
                                <p class="text-xs text-gray-500 mt-2">
                                    Current: <a href="{{ Storage::url($user->avatar) }}" target="_blank" class="text-blue-600 hover:underline">View</a>
                                </p>
                                @endif
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    Digital Signature
                                </label>
                                <input
                                    type="file"
                                    name="signature"
                                    accept="image/*"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                                @if($user->signature)
                                <p class="text-xs text-gray-500 mt-2">
                                    Current: <a href="{{ Storage::url($user->signature) }}" target="_blank" class="text-blue-600 hover:underline">View</a>
                                </p>
                                @endif
                            </div>
                        </div>

                        <!-- ID Front & Back Row -->
                        <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    ID Front
                                </label>
                                <input
                                    type="file"
                                    name="id_front_path"
                                    accept="image/*"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                                @if($user->id_front_path)
                                <p class="text-xs text-gray-500 mt-2">
                                    Current: <a href="{{ Storage::url($user->id_front_path) }}" target="_blank" class="text-blue-600 hover:underline">View</a>
                                </p>
                                @endif
                            </div>

                            <div class="col-span-1">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    ID Back
                                </label>
                                <input
                                    type="file"
                                    name="id_back_path"
                                    accept="image/*"
                                    class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                />
                                @if($user->id_back_path)
                                <p class="text-xs text-gray-500 mt-2">
                                    Current: <a href="{{ Storage::url($user->id_back_path) }}" target="_blank" class="text-blue-600 hover:underline">View</a>
                                </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end w-full gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            onclick="document.getElementById('editProfileModal').classList.add('hidden')"
                            class="flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
                        >
                            Close
                        </button>
                        
                        <button
                            type="submit"
                            class="flex items-center justify-center rounded-lg border border-brand-500 bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs transition-colors hover:bg-brand-600"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    [x-cloak] {
        display: none !important;
    }
    
    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .document-hover {
        transition: transform 0.2s ease-in-out;
    }
    
    .document-hover:hover {
        transform: translateY(-2px);
    }
    
    .group-hover\:scale-110:hover {
        transform: scale(1.1);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default tab based on URL hash or first available
        const hash = window.location.hash.substring(1);
        const validTabs = ['profile', 'documents', 'employment', 'kin', 'loans', 'recovery'];
        
        if (hash && validTabs.includes(hash)) {
            const el = document.querySelector(`[x-data]`);
            if (el && el.__x) {
                el.__x.$data.activeTab = hash;
            }
        }
        
        // Update URL when tab changes - use a safer approach
        const tabContainer = document.querySelector('[x-data]');
        if (tabContainer) {
            // Use Alpine's watch if available
            if (tabContainer.__x) {
                tabContainer.__x.$watch('activeTab', (value) => {
                    window.history.replaceState(null, null, `#${value}`);
                });
            }
        }
    });
</script>
@endpush