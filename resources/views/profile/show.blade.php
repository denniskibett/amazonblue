@extends('layouts.app')

@section('content')
<!-- Breadcrumb Start -->
<div x-data="{ pageName: 'Profile' }">
    @include('partials.breadcrumb', ['pageName' => 'Profile'])
</div>
<!-- Breadcrumb End -->

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200" 
     x-data="profileData()" 
     x-init="initProfile()">
    
    <!-- Success Message -->
    <div id="success-message" 
         class="hidden fixed top-20 right-5 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-300"
         x-data="{ show: false, message: '' }"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         @profile-updated.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span x-text="message"></span>
        </div>
    </div>

    <!-- Profile Lock Notice -->
    @if($isProfileLocked)
    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-800 dark:bg-red-900/20">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                    🔒 Profile Locked
                </p>
                <p class="text-xs text-red-700 dark:text-red-400">
                    {{ $loanStatusMessage ?? 'You have an active loan. Profile changes are restricted.' }}
                </p>
                @if(!empty($lockedFields))
                <div class="mt-2 flex flex-wrap gap-1">
                    <span class="text-xs text-red-600 dark:text-red-400">Locked fields:</span>
                    @foreach($lockedFields as $field => $label)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-800/30 dark:text-red-300">
                        {{ $label }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Profile Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Profile</h3>
            @if(!$isProfileLocked)
            <a href="{{ route('profile.edit') }}" 
               class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Edit Profile
            </a>
            @else
            <span class="inline-flex items-center gap-2 text-sm text-red-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Profile Locked
            </span>
            @endif
        </div>

        <!-- User Info Section -->
        <div class="mb-6 rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex w-full flex-col items-center gap-6 xl:flex-row">
                    <div class="relative group">
                        <div class="h-20 w-20 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700">
                            @php
                                $avatarUrl = $user->getAvatarUrl();
                            @endphp
                            <img id="avatar-preview" 
                                 src="{{ $avatarUrl }}" 
                                 alt="Profile Picture" 
                                 class="h-full w-full object-cover"
                                 onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=7F9CF5&background=EBF4FF&bold=true'">
                        </div>
                        @if(!$isProfileLocked)
                        <button @click="openModal('avatarModal')"
                                class="absolute bottom-0 right-0 bg-blue-600 text-white p-1.5 rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                        @endif
                    </div>
                    <div>
                        <h4 class="mb-1 text-center text-lg font-semibold xl:text-left">{{ $user->name }}</h4>
                        <div class="flex flex-col items-center gap-1 text-center xl:flex-row xl:gap-3 xl:text-left">
                            <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ $user->role ?? 'User' }}</p>
                            <div class="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block"></div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if($user->nationality)
                                    @php
                                        $country = \PragmaRX\Countries\Package\Countries::where('cca2', $user->nationality)->first();
                                    @endphp
                                    @if($country)
                                        <span class="flex items-center gap-1">
                                            <span>{{ $country->flag->emoji ?? '🏳️' }}</span>
                                            <span>{{ $country->name->common ?? $user->nationality }}</span>
                                        </span>
                                    @else
                                        <span>{{ $user->nationality }}</span>
                                    @endif
                                @else
                                    <span>No nationality set</span>
                                @endif
                            </p>
                            @if($user->dob)
                            <div class="hidden h-3.5 w-px bg-gray-300 dark:bg-gray-700 xl:block"></div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($user->dob)->age }} years old
                            </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex grow items-center gap-2 xl:justify-end" id="social-links">
                        @php
                            $social = json_decode($user->social, true) ?? [];
                        @endphp
                        
                        @if(!empty($social['facebook']))
                        <a href="{{ $social['facebook'] }}" target="_blank" 
                           class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 bg-white shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        @endif
                        
                        @if(!empty($social['twitter']))
                        <a href="{{ $social['twitter'] }}" target="_blank" 
                           class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 bg-white shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5 text-blue-400 dark:text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.213c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        @endif
                        
                        @if(!empty($social['linkedin']))
                        <a href="{{ $social['linkedin'] }}" target="_blank" 
                           class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 bg-white shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5 text-blue-700 dark:text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        @endif
                        
                        @if(!empty($social['instagram']))
                        <a href="{{ $social['instagram'] }}" target="_blank" 
                           class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-300 bg-white shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5 text-pink-600 dark:text-pink-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        @endif
                        
                        @if(empty($social))
                        <span class="text-sm text-gray-400">No social links</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Sections Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Personal Information -->
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">👤</span>
                        <h4 class="text-lg font-semibold">Personal Information</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#basic" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Full Name</p>
                        <p class="text-sm font-medium">{{ $user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Email Address</p>
                        <p class="text-sm font-medium">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Phone Number</p>
                        <p class="text-sm font-medium">{{ $user->phone ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Gender</p>
                        <p class="text-sm font-medium capitalize">{{ $user->gender ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Date of Birth</p>
                        <p class="text-sm font-medium">{{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d/m/Y') : 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Age</p>
                        <p class="text-sm font-medium">{{ $user->dob ? \Carbon\Carbon::parse($user->dob)->age . ' years' : 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Nationality</p>
                        <p class="text-sm font-medium">
                            @if($user->nationality)
                                @php
                                    $country = \PragmaRX\Countries\Package\Countries::where('cca2', $user->nationality)->first();
                                @endphp
                                @if($country)
                                    <span class="flex items-center gap-1">
                                        <span>{{ $country->flag->emoji ?? '🏳️' }}</span>
                                        <span>{{ $country->name->common ?? $user->nationality }}</span>
                                    </span>
                                @else
                                    <span>{{ $user->nationality }}</span>
                                @endif
                            @else
                                <span>Not set</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Marital Status</p>
                        <p class="text-sm font-medium capitalize">{{ $user->marital_status ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Religion</p>
                        <p class="text-sm font-medium">{{ $user->religion ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Education Level</p>
                        <p class="text-sm font-medium">{{ $user->education ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Disability</p>
                        <p class="text-sm font-medium">{{ $user->disability ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            </div>

            <!-- Identification -->
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🪪</span>
                        <h4 class="text-lg font-semibold">Identification</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#identification" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ID Type</p>
                        <p class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $user->id_type ?? 'Not set') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ID Number</p>
                        <p class="text-sm font-medium">{{ $user->id_number ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ID Front</p>
                        @if($user->id_front_path)
                            <a href="{{ asset('storage/' . $user->id_front_path) }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                View Document
                            </a>
                        @else
                            <p class="text-sm text-gray-400">Not uploaded</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ID Back</p>
                        @if($user->id_back_path)
                            <a href="{{ asset('storage/' . $user->id_back_path) }}" target="_blank" class="text-sm text-blue-600 hover:underline">
                                View Document
                            </a>
                        @else
                            <p class="text-sm text-gray-400">Not uploaded</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Next of Kin -->
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">👨‍👩‍👧‍👦</span>
                        <h4 class="text-lg font-semibold">Next of Kin</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#next-of-kin" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Full Name</p>
                        <p class="text-sm font-medium">{{ $user->kin_name ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Email</p>
                        <p class="text-sm font-medium">{{ $user->kin_email ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Phone Number</p>
                        <p class="text-sm font-medium">{{ $user->kin_phone ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Occupation</p>
                        <p class="text-sm font-medium">{{ $user->kin_occupation ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Relationship</p>
                        <p class="text-sm font-medium">{{ $user->kin_relation ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kin's ID Type</p>
                        <p class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $user->kin_id_type ?? 'Not set') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kin's ID Number</p>
                        <p class="text-sm font-medium">{{ $user->kin_id_number ?? 'Not set' }}</p>
                    </div>
                </div>
            </div>

            <!-- Employment (Borrowers only) -->
            @if($user->role === 'borrower' && $user->borrower)
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">💼</span>
                        <h4 class="text-lg font-semibold">Employment Information</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#employment" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Income Type</p>
                        <p class="text-sm font-medium">{{ $user->borrower->income_type ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Net Salary</p>
                        <p class="text-sm font-medium">{{ $user->borrower->net_salary ? 'KES ' . number_format($user->borrower->net_salary, 2) : 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Gross Salary</p>
                        <p class="text-sm font-medium">{{ $user->borrower->gross_salary ? 'KES ' . number_format($user->borrower->gross_salary, 2) : 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Job Title</p>
                        <p class="text-sm font-medium">{{ $user->borrower->job_title ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Employer Name</p>
                        <p class="text-sm font-medium">{{ $user->borrower->employer_name ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Workplace</p>
                        <p class="text-sm font-medium">{{ $user->borrower->workplace ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Employer Email</p>
                        <p class="text-sm font-medium">{{ $user->borrower->employer_email ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Department</p>
                        <p class="text-sm font-medium">{{ $user->borrower->department ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Client Type</p>
                        <p class="text-sm font-medium">{{ $user->borrower->client_type == 0 ? 'Individual' : 'Business' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Account Status</p>
                        <p class="text-sm font-medium">{{ $user->borrower->status == 1 ? 'Active' : 'Inactive' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Broker Info (Brokers only) -->
            @if($user->role === 'broker' && $user->broker)
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🤝</span>
                        <h4 class="text-lg font-semibold">Broker Information</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#broker-info" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Certificate Number</p>
                        <p class="text-sm font-medium">{{ $user->broker->cert_no ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Client Interest Rate</p>
                        <p class="text-sm font-medium">{{ $user->broker->interest_client ?? 'Not set' }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Broker Interest Rate</p>
                        <p class="text-sm font-medium">{{ $user->broker->interest_broker ?? 'Not set' }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Client Penalty Rate</p>
                        <p class="text-sm font-medium">{{ $user->broker->penalty_client ?? 'Not set' }}%</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Broker Penalty Rate</p>
                        <p class="text-sm font-medium">{{ $user->broker->penalty_broker ?? 'Not set' }}%</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Teller Info (Tellers only) -->
            @if($user->role === 'teller' && $user->teller)
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🏧</span>
                        <h4 class="text-lg font-semibold">Teller Information</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#teller-info" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Branch</p>
                        <p class="text-sm font-medium">{{ $user->teller->branch ?? 'Not set' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Signature -->
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">✍️</span>
                        <h4 class="text-lg font-semibold">Digital Signature</h4>
                    </div>
                    @if(!$isProfileLocked)
                    <a href="{{ route('profile.edit') }}#signature-section" 
                       class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Edit
                    </a>
                    @endif
                </div>
                
                <div class="space-y-3">
                    @if($user->signature)
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                            <div class="flex flex-col sm:flex-row items-center gap-4">
                                <div class="bg-white dark:bg-gray-800 p-2 rounded-lg border border-green-200 dark:border-green-700">
                                    <div class="w-24 h-24 flex items-center justify-center bg-gray-50 dark:bg-gray-900 rounded">
                                        <img src="{{ asset('storage/' . $user->signature) }}?v={{ time() }}"
                                             alt="Signature of {{ $user->name }}"
                                             class="max-w-full max-h-full object-contain"
                                             onerror="this.style.display='none';">
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-green-800 dark:text-green-300">
                                        {{ $user->name }}
                                    </p>
                                    <p class="text-xs text-green-600 dark:text-green-400">
                                        ✅ Signature on file
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 text-center">
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                No signature has been uploaded yet.
                            </p>
                            @if(!$isProfileLocked)
                            <a href="{{ route('profile.edit') }}#signature-section" 
                               class="mt-2 inline-block text-sm text-blue-600 hover:underline">
                                Add Signature →
                            </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700 lg:p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📊</span>
                        <h4 class="text-lg font-semibold">Account Statistics</h4>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $user->getBiodataCompletionPercentage() }}%
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Profile Complete</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ $user->loans()->count() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Loans</p>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ $user->loans()->where('status', 'active')->count() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Active Loans</p>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $user->loans()->where('status', 'completed')->count() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Completed Loans</p>
                    </div>
                </div>
                
                @if($user->role === 'borrower' && $user->borrower)
                <div class="mt-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Monthly Debt Capacity</span>
                        <span class="font-medium">
                            KES {{ number_format($user->borrower->getMonthlyDebtCapacityAttribute() ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm mt-1">
                        <span class="text-gray-500 dark:text-gray-400">Risk Category</span>
                        <span class="font-medium">
                            <span class="px-2 py-0.5 rounded-full text-xs 
                                @if($user->borrower->getRiskCategoryAttribute() === 'Low Risk') bg-green-100 text-green-800
                                @elseif($user->borrower->getRiskCategoryAttribute() === 'Medium Risk') bg-yellow-100 text-yellow-800
                                @elseif($user->borrower->getRiskCategoryAttribute() === 'High Risk') bg-orange-100 text-orange-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ $user->borrower->getRiskCategoryAttribute() }}
                            </span>
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-sm mt-1">
                        <span class="text-gray-500 dark:text-gray-400">Credit Score</span>
                        <span class="font-medium">
                            <span class="px-2 py-0.5 rounded-full text-xs 
                                @if($user->borrower->getCreditScore() >= 750) bg-green-100 text-green-800
                                @elseif($user->borrower->getCreditScore() >= 700) bg-green-100 text-green-700
                                @elseif($user->borrower->getCreditScore() >= 650) bg-yellow-100 text-yellow-800
                                @elseif($user->borrower->getCreditScore() >= 600) bg-orange-100 text-orange-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ $user->borrower->getCreditScore() }} ({{ $user->borrower->getCreditRating() }})
                            </span>
                        </span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Avatar Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity duration-300"
         x-show="modalOpen && activeModal === 'avatarModal'"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="closeModal"
         @keydown.escape.window="closeModal">
        
        <div class="w-full max-w-md transform rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-2xl transition-all duration-300"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-xl font-semibold">Update Profile Picture</h3>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form @submit.prevent="updateAvatar" class="space-y-6">
                @csrf
                <div class="text-center">
                    <div class="mx-auto mb-4 h-32 w-32 overflow-hidden rounded-full border-4 border-gray-200 dark:border-gray-700">
                        @php
                            $avatarModalUrl = $user->getAvatarUrl();
                        @endphp
                        <img id="avatar-modal-preview" 
                             src="{{ $avatarModalUrl }}" 
                             alt="Profile Picture" 
                             class="h-full w-full object-cover"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=7F9CF5&background=EBF4FF&bold=true'">
                    </div>
                    
                    <input type="file" name="avatar" id="avatarInput" accept="image/*" @change="previewAvatar" class="hidden">
                    <label for="avatarInput" class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-700">
                        Choose File
                    </label>
                    <p class="mt-2 text-xs text-gray-500" id="fileName">No file chosen</p>
                    <p class="mt-1 text-xs text-gray-500">Max file size: 2MB • Supported: JPG, PNG, GIF, WEBP</p>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeModal" 
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                            :disabled="loading">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loading">
                        <span x-show="!loading">Upload</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function profileData() {
    return {
        modalOpen: false,
        activeModal: null,
        loading: false,

        initProfile() {
            console.log('Profile page initialized');
        },

        openModal(modalName) {
            this.activeModal = modalName;
            this.modalOpen = true;
            document.body.classList.add('overflow-hidden');
        },

        closeModal() {
            this.modalOpen = false;
            this.activeModal = null;
            setTimeout(() => {
                document.body.classList.remove('overflow-hidden');
            }, 300);
        },

        previewAvatar(event) {
            const file = event.target.files[0];
            const fileNameElement = document.getElementById('fileName');
            
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    event.target.value = '';
                    fileNameElement.textContent = 'No file chosen';
                    return;
                }
                
                fileNameElement.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('avatar-modal-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        async updateAvatar() {
            this.loading = true;
            
            const formData = new FormData();
            const fileInput = document.getElementById('avatarInput');
            
            if (!fileInput.files[0]) {
                alert('Please select a file');
                this.loading = false;
                return;
            }
            
            formData.append('_method', 'PUT');
            formData.append('avatar', fileInput.files[0]);

            try {
                const response = await fetch('{{ route("profile.update") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (data.avatar_url) {
                        document.getElementById('avatar-preview').src = data.avatar_url;
                        document.getElementById('avatar-modal-preview').src = data.avatar_url;
                    }
                    
                    this.closeModal();
                    this.showSuccess('Profile picture updated successfully!');
                    
                    fileInput.value = '';
                    document.getElementById('fileName').textContent = 'No file chosen';
                } else {
                    throw new Error(data.message || data.error || 'Upload failed');
                }
            } catch (error) {
                console.error('Avatar update error:', error);
                alert('Error updating avatar: ' + error.message);
            } finally {
                this.loading = false;
            }
        },

        showSuccess(message) {
            window.dispatchEvent(new CustomEvent('profile-updated', {
                detail: { message }
            }));
        }
    }
}
</script>
@endsection