@extends('layouts.app')

@section('content')
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: 'Profile' }">
        @include('partials.breadcrumb')
    </div>
    <!-- Breadcrumb End -->

    <!-- Profile Header -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6 mb-6">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex w-full flex-col items-center gap-6 xl:flex-row">
                <div class="h-20 w-20 overflow-hidden rounded-full border border-gray-200 dark:border-gray-700">
                    @if($user->avatar)
                        <img src="{{ Storage::url($user->avatar) }}" alt="Profile Photo" class="h-full w-full object-cover">
                    @else
                        <div class="h-full w-full flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                            <i class="fas fa-user text-gray-400 text-2xl"></i>
                        </div>
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

    <!-- Biodata Completion Alert with Sections -->
    @if($user->role === 'borrower' && !$user->hasCompleteBiodata())
    @php
        $completionData = $user->getBiodataCompletionBySections();
        $overallPercentage = $user->getBiodataCompletionPercentage();
    @endphp
    <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 p-5 dark:border-yellow-800 dark:bg-yellow-900/20 lg:p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-500 mr-3 text-lg"></i>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Profile Incomplete ({{ $overallPercentage }}%)
                    </h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        Complete your profile to get better loan terms
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('editProfileModal').classList.remove('hidden')"
                class="text-sm text-yellow-800 dark:text-yellow-200 hover:text-yellow-900 dark:hover:text-yellow-100 font-medium">
                Complete Now <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>
        
        <!-- Progress Bars by Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            @foreach($completionData['sections'] as $sectionName => $section)
            <div>
                <div class="flex justify-between text-xs text-yellow-700 dark:text-yellow-300 mb-1">
                    <span>{{ $section['name'] }}</span>
                    <span>{{ $section['percentage'] }}%</span>
                </div>
                <div class="h-2 bg-yellow-100 dark:bg-yellow-800 rounded-full overflow-hidden">
                    <div class="h-full bg-yellow-500 transition-all duration-300" 
                         style="width: {{ $section['percentage'] }}%"></div>
                </div>
                @if(count($section['missing']) > 0)
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 truncate" title="{{ implode(', ', $section['missing']) }}">
                    Missing: {{ count($section['missing']) }} field(s)
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @include('partials.card.users-card', ['user' => $user])

    <!-- User Details Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6 mb-6">
        <h3 class="mb-5 text-lg font-semibold lg:mb-7">Personal Information</h3>
        
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-user text-blue-600 dark:text-blue-400 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                    <p class="font-medium">{{ $user->name }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-envelope text-green-600 dark:text-green-400 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                    <p class="font-medium">{{ $user->email }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <i class="fas fa-phone text-purple-600 dark:text-purple-400 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                    <p class="font-medium">{{ $user->phone ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <div class="p-2 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                    <i class="fas fa-user-tag text-pink-600 dark:text-pink-400 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Role</p>
                    <p class="font-medium">{{ ucfirst($user->role) }}</p>
                </div>
            </div>
        </div>

        <!-- Additional Personal Information -->
        @if($user->gender || $user->dob || $user->nationality || $user->id_number)
        <div class="grid grid-cols-1 gap-6 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 md:grid-cols-2 lg:grid-cols-4">
            @if($user->gender)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Gender</p>
                <p class="font-medium">{{ ucfirst($user->gender) }}</p>
            </div>
            @endif
            
            @if($user->dob)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Date of Birth</p>
                <p class="font-medium">
                    {{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('Y-m-d') : 'N/A' }} 
                    (Age: {{ $user->age ?? 'N/A' }})
                </p>
            </div>
            @endif
            
            @if($user->nationality)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nationality</p>
                <p class="font-medium">{{ $user->nationality }}</p>
            </div>
            @endif

            @if($user->id_number)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->id_type ?? 'ID' }} Number</p>
                <p class="font-medium">{{ $user->id_number }}</p>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Identity Documents Section -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Passport Photo -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <h3 class="mb-4 text-lg font-semibold">Passport Photo</h3>
            <div class="flex justify-center">
                @if($user->avatar)
                    <div class="relative group">
                        <img src="{{ Storage::url($user->avatar) }}" 
                             alt="Passport Photo" 
                             class="w-48 h-48 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        <a href="{{ Storage::url($user->avatar) }}" 
                           target="_blank" 
                           class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-all rounded-lg">
                            <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                        </a>
                    </div>
                @else
                    <div class="w-48 h-48 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                        <div class="text-center">
                            <i class="fas fa-camera text-gray-400 dark:text-gray-500 text-3xl mx-auto"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No Photo</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- ID Front -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <h3 class="mb-4 text-lg font-semibold">
                {{ $user->id_type ?? 'ID' }} Front
                @if($user->id_type)
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $user->id_type }})</span>
                @endif
            </h3>
            <div class="flex justify-center">
                @if($user->id_front_path)
                    <div class="relative group">
                        <img src="{{ Storage::url($user->id_front_path) }}" 
                             alt="ID Front" 
                             class="w-48 h-32 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        <a href="{{ Storage::url($user->id_front_path) }}" 
                           target="_blank" 
                           class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-all rounded-lg">
                            <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                        </a>
                    </div>
                @else
                    <div class="w-48 h-32 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                        <div class="text-center">
                            <i class="fas fa-id-card text-gray-400 dark:text-gray-500 text-3xl mx-auto"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No ID Front</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- ID Back -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <h3 class="mb-4 text-lg font-semibold">
                {{ $user->id_type ?? 'ID' }} Back
                @if($user->id_type)
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $user->id_type }})</span>
                @endif
            </h3>
            <div class="flex justify-center">
                @if($user->id_back_path)
                    <div class="relative group">
                        <img src="{{ Storage::url($user->id_back_path) }}" 
                             alt="ID Back" 
                             class="w-48 h-32 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        <a href="{{ Storage::url($user->id_back_path) }}" 
                           target="_blank" 
                           class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-all rounded-lg">
                            <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-xl"></i>
                        </a>
                    </div>
                @else
                    <div class="w-48 h-32 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center bg-gray-50 dark:bg-gray-700">
                        <div class="text-center">
                            <i class="fas fa-id-card text-gray-400 dark:text-gray-500 text-3xl mx-auto"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No ID Back</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Signature Section -->
    @if($user->signature)
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6 mb-6">
        <h3 class="mb-4 text-lg font-semibold">Digital Signature</h3>
        <div class="flex justify-center">
            <div class="relative group">
                <img src="{{ Storage::url($user->signature) }}" 
                     alt="Digital Signature" 
                     class="h-20 rounded-lg border border-gray-200 dark:border-gray-700">
                <a href="{{ Storage::url($user->signature) }}" 
                   target="_blank" 
                   class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 flex items-center justify-center transition-all rounded-lg">
                    <i class="fas fa-search text-white opacity-0 group-hover:opacity-100 transition-opacity text-lg"></i>
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Borrower Employment Information -->
    @if($user->role === 'borrower' && $user->borrower && $user->borrower->income_type)
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6 mb-6">
        <h3 class="mb-5 text-lg font-semibold lg:mb-7">Employment Information</h3>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Income Type</p>
                <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $user->borrower->income_type)) }}</p>
            </div>
            
            @if($user->borrower->job_title)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Job Title</p>
                <p class="font-medium">{{ $user->borrower->job_title }}</p>
            </div>
            @endif
            
            @if($user->borrower->net_salary)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Net Salary</p>
                <p class="font-medium">KES {{ number_format($user->borrower->net_salary, 2) }}</p>
            </div>
            @endif
            
            @if($user->borrower->workplace)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Workplace</p>
                <p class="font-medium">{{ $user->borrower->workplace }}</p>
            </div>
            @endif
            
            @if($user->borrower->employer_name)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Employer Name</p>
                <p class="font-medium">{{ $user->borrower->employer_name }}</p>
            </div>
            @endif
            
            @if($user->borrower->department)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Department</p>
                <p class="font-medium">{{ $user->borrower->department }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Next of Kin Information -->
    @if($user->kin_name)
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6 mb-6">
        <h3 class="mb-5 text-lg font-semibold lg:mb-7">Next of Kin</h3>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                <p class="font-medium">{{ $user->kin_name }}</p>
            </div>
            
            @if($user->kin_relation)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Relationship</p>
                <p class="font-medium">{{ $user->kin_relation }}</p>
            </div>
            @endif
            
            @if($user->kin_phone)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Phone</p>
                <p class="font-medium">{{ $user->kin_phone }}</p>
            </div>
            @endif
            
            @if($user->kin_email)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                <p class="font-medium">{{ $user->kin_email }}</p>
            </div>
            @endif
            
            @if($user->kin_occupation)
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Occupation</p>
                <p class="font-medium">{{ $user->kin_occupation }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Loans Table -->
    @include('partials.table.table-loans', ['loans' => $user->loans, 'userView' => true])

    <!-- Edit Profile Modal - SIMPLIFIED VERSION -->
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
                                    {{-- required --}}
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
                                    {{-- required --}}
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

                            <!-- Organization Information (for employed, self-employed, business owners) -->
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

@push('scripts')
<script>
    // Simple JavaScript for conditional employment fields
    document.addEventListener('DOMContentLoaded', function() {
        const incomeTypeSelect = document.querySelector('select[name="income_type"]');
        const employmentFields = document.querySelectorAll('.col-span-2');
        
        function toggleEmploymentFields() {
            const value = incomeTypeSelect?.value;
            const isUnemployedOrStudent = value === 'unemployed' || value === 'student';
            
            employmentFields.forEach(field => {
                if (field.classList.contains('col-span-2')) {
                    field.style.display = isUnemployedOrStudent ? 'none' : 'block';
                }
            });
        }
        
        if (incomeTypeSelect) {
            incomeTypeSelect.addEventListener('change', toggleEmploymentFields);
            toggleEmploymentFields(); // Initial check
        }
    });
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editProfileForm');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('Form submitting...');
                console.log('Form data:', new FormData(form));
                
                // Optional: Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    submitBtn.disabled = true;
                }
            });
        }
        
        // Your existing code...
        const incomeTypeSelect = document.querySelector('select[name="income_type"]');
        const employmentFields = document.querySelectorAll('.col-span-2');
        
        function toggleEmploymentFields() {
            const value = incomeTypeSelect?.value;
            const isUnemployedOrStudent = value === 'unemployed' || value === 'student';
            
            employmentFields.forEach(field => {
                if (field.classList.contains('col-span-2')) {
                    field.style.display = isUnemployedOrStudent ? 'none' : 'block';
                }
            });
        }
        
        if (incomeTypeSelect) {
            incomeTypeSelect.addEventListener('change', toggleEmploymentFields);
            toggleEmploymentFields(); // Initial check
        }
    });
</script>
@endpush