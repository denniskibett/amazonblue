 @extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200" 
     x-data="profileData()"
     x-init="init()">
    
    <!-- Auto Save Notification -->
    <div id="auto-save-notification" class="hidden"></div>

    <!-- Main Content -->
    <main class="mx-auto max-w-screen-2xl p-4 md:p-6">
        <!-- Breadcrumb Start -->
        <div class="mb-6">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 mr-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                            </svg>
                            Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="{{ route('profile.show') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Profile</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Edit</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        <!-- Breadcrumb End -->

        <!-- Quick Navigation with Section Counts -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex flex-wrap gap-4 justify-between items-center">
                <div class="flex flex-wrap gap-2">
                    @foreach($sectionCounts as $key => $section)
                    <button @click="scrollToSection('{{ $key }}')" 
                            :class="activeSection === '{{ $key }}' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                            class="px-4 py-3 rounded-lg text-sm font-medium transition-colors min-w-[140px] text-left">
                        <div class="font-semibold">{{ $section['name'] }}</div>
                        <div class="text-xs mt-1 opacity-75">
                            {{ $section['filled'] }}/{{ $section['total'] }} completed
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2 dark:bg-gray-700">
                            <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" 
                                 style="width: {{ $section['total'] > 0 ? ($section['filled'] / $section['total']) * 100 : 0 }}%"></div>
                        </div>
                    </button>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <button @click="exportProfileData()" 
                            class="px-3 py-2 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors">
                        Export Data
                    </button>
                    <button @click="printProfile()" 
                            class="px-3 py-2 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors">
                        Print
                    </button>
                </div>
            </div>
        </div>

        <!-- Profile Completion Status -->
        @if(auth()->user()->role === 'borrower')
        <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-6 shadow-sm dark:border-blue-800 dark:bg-blue-900/20">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-300">Profile Completion Status</h3>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                        Complete all sections to be eligible for loans
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-800 dark:text-blue-300">
                        {{ $completionPercentage }}%
                    </div>
                    <div class="text-sm text-blue-700 dark:text-blue-400">Complete</div>
                </div>
            </div>
            <div class="mt-4 w-full bg-blue-200 rounded-full h-3 dark:bg-blue-800">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                     style="width: {{ $completionPercentage }}%"></div>
            </div>
            @if(!$user->hasCompleteBiodata())
            <div class="mt-3">
                <p class="text-sm text-blue-700 dark:text-blue-400 mb-2">Missing fields:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($missingFields as $field)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-300">
                        {{ $field }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Profile Edit Form -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold">Edit Profile</h3>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Press <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">Ctrl + S</kbd> to save
                </div>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="profile-form">
                @csrf
                @method('PUT')

                <!-- Section 1: Basic Information -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="basic">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-lg font-medium">Basic Information</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['basic']['filled'] }}/{{ $sectionCounts['basic']['total'] }}</span>
                            @if($sectionCounts['basic']['filled'] === $sectionCounts['basic']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Complete
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Incomplete
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Phone Number *</label>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Gender *</label>
                            <select name="gender" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select Gender</option>
                                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        
                        <!-- Date of Birth Field -->
<div>
    <label class="block mb-2 text-sm font-medium">Date of Birth *</label>
    <div class="relative">
        <input
            type="text"
            name="dob"
            value="{{ old('dob', $user->dob ? $user->dob->format('d/m/Y') : '') }}"
            placeholder="DD/MM/YYYY"
            class="dark:bg-dark-900 datepicker shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            required
            readonly
        />
        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400">
            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
            </svg>
        </span>
    </div>
    @error('dob')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

                        <!-- Nationality Dropdown with Flags -->
                        <div class="col-span-1">
                            <label for="nationality" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nationality <span class="text-red-500">*</span>
                            </label>
                            <select name="nationality" id="nationality" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200 transition-colors">
                                <option value="">Select Nationality</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country['code'] }}" 
                                        {{ (old('nationality', $user->nationality ?? '') == $country['code']) ? 'selected' : '' }}>
                                        {{ $country['name'] }} ({{ $country['nationality'] }}) - ({{ $country['iso3'] }})
                                    </option>
                                @endforeach
                            </select>
                            @error('nationality')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium">Marital Status *</label>
                            <select name="marital_status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select Marital Status</option>
                                <option value="single" {{ old('marital_status', $user->marital_status) == 'single' ? 'selected' : '' }}>Single</option>
                                <option value="married" {{ old('marital_status', $user->marital_status) == 'married' ? 'selected' : '' }}>Married</option>
                                <option value="divorced" {{ old('marital_status', $user->marital_status) == 'divorced' ? 'selected' : '' }}>Divorced</option>
                                <option value="widowed" {{ old('marital_status', $user->marital_status) == 'widowed' ? 'selected' : '' }}>Widowed</option>
                            </select>
                            @error('marital_status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Profile Photo</label>
                            <input type="file" name="profile_photo" accept="image/*" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if($user->profile_photo_path)
                            <p class="mt-1 text-sm text-green-600">Current photo uploaded</p>
                            @endif
                            @error('profile_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: Identification -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="identification">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-lg font-medium">Identification</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['identification']['filled'] }}/{{ $sectionCounts['identification']['total'] }}</span>
                            @if($sectionCounts['identification']['filled'] === $sectionCounts['identification']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Complete
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Incomplete
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Type *</label>
                            <select name="id_type" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select ID Type</option>
                                <option value="national_id" {{ old('id_type', $user->id_type) == 'national_id' ? 'selected' : '' }}>National ID</option>
                                <option value="passport" {{ old('id_type', $user->id_type) == 'passport' ? 'selected' : '' }}>Passport</option>
                                <option value="drivers_license" {{ old('id_type', $user->id_type) == 'drivers_license' ? 'selected' : '' }}>Driver's License</option>
                            </select>
                            @error('id_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Number *</label>
                            <input type="text" name="id_number" value="{{ old('id_number', $user->id_number) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('id_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Front Photo *</label>
                            <input type="file" name="id_front_path" accept="image/*" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if($user->id_front_path)
                            <p class="mt-1 text-sm text-green-600">Current file: {{ basename($user->id_front_path) }}</p>
                            @endif
                            @error('id_front_path')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Back Photo *</label>
                            <input type="file" name="id_back_path" accept="image/*" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if($user->id_back_path)
                            <p class="mt-1 text-sm text-green-600">Current file: {{ basename($user->id_back_path) }}</p>
                            @endif
                            @error('id_back_path')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 3: Next of Kin -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="next-of-kin">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-lg font-medium">Next of Kin Information</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['next-of-kin']['filled'] }}/{{ $sectionCounts['next-of-kin']['total'] }}</span>
                            @if($sectionCounts['next-of-kin']['filled'] === $sectionCounts['next-of-kin']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Complete
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Incomplete
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Full Name *</label>
                            <input type="text" name="kin_name" value="{{ old('kin_name', $user->kin_name) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Email *</label>
                            <input type="email" name="kin_email" value="{{ old('kin_email', $user->kin_email) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Phone Number *</label>
                            <input type="tel" name="kin_phone" value="{{ old('kin_phone', $user->kin_phone) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Occupation *</label>
                            <input type="text" name="kin_occupation" value="{{ old('kin_occupation', $user->kin_occupation) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_occupation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Relationship *</label>
                            <select name="kin_relation" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select Relationship</option>
                                @foreach($relationships as $relationship)
                                <option value="{{ $relationship->name }}" {{ old('kin_relation', $user->kin_relation) == $relationship->name ? 'selected' : '' }}>
                                    {{ $relationship->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('kin_relation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Kin's ID Type *</label>
                            <select name="kin_id_type" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select ID Type</option>
                                <option value="national_id" {{ old('kin_id_type', $user->kin_id_type) == 'national_id' ? 'selected' : '' }}>National ID</option>
                                <option value="passport" {{ old('kin_id_type', $user->kin_id_type) == 'passport' ? 'selected' : '' }}>Passport</option>
                            </select>
                            @error('kin_id_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Kin's ID Number *</label>
                            <input type="text" name="kin_id_number" value="{{ old('kin_id_number', $user->kin_id_number) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_id_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 4: Additional Information -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="additional">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-lg font-medium">Additional Information</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['additional']['filled'] }}/{{ $sectionCounts['additional']['total'] }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Religion</label>
                            <select name="religion" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Religion (Optional)</option>
                                @foreach($religions as $religion)
                                <option value="{{ $religion->name }}" {{ old('religion', $user->religion) == $religion->name ? 'selected' : '' }}>
                                    {{ $religion->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('religion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Education Level</label>
                            <select name="education" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Education Level (Optional)</option>
                                @foreach($educationLevels as $education)
                                <option value="{{ $education->name }}" {{ old('education', $user->education) == $education->name ? 'selected' : '' }}>
                                    {{ $education->name }}
                                </option>
                                @endforeach
                                <!-- Allow free text input as well -->
                                <option value="other" {{ !in_array(old('education', $user->education), $educationLevels->pluck('name')->toArray()) && !empty(old('education', $user->education)) ? 'selected' : '' }}>Other (Specify)</option>
                            </select>
                            <!-- Hidden input for custom education -->
                            <input type="text" name="education_custom" 
                                value="{{ !in_array(old('education', $user->education), $educationLevels->pluck('name')->toArray()) && !empty(old('education', $user->education)) ? old('education', $user->education) : '' }}"
                                placeholder="Specify your education level"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mt-2 hidden"
                                id="education-custom-input">
                            @error('education')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="disability" value="1" {{ old('disability', $user->disability) ? 'checked' : '' }} 
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">I have a disability (Optional)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Employment Information (for borrowers) -->
                @if($user->role === 'borrower')
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="employment">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-lg font-medium">Employment & Income Information</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['employment']['filled'] }}/{{ $sectionCounts['employment']['total'] }}</span>
                            @if($sectionCounts['employment']['filled'] === $sectionCounts['employment']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Complete
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Incomplete
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Income Type *</label>
                            <select name="income_type" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select Income Type</option>
                                @foreach($incomeTypes as $incomeType)
                                <option value="{{ $incomeType->name }}" {{ old('income_type', $user->borrower->income_type ?? '') == $incomeType->name ? 'selected' : '' }}>
                                    {{ $incomeType->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('income_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Gross Salary ({{ config('app.currency', '$') }})</label>
                            <input type="number" step="0.01" name="gross_salary" value="{{ old('gross_salary', $user->borrower->gross_salary ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional">
                            @error('gross_salary')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Net Salary ({{ config('app.currency', '$') }}) *</label>
                            <input type="number" step="0.01" name="net_salary" value="{{ old('net_salary', $user->borrower->net_salary ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('net_salary')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Job Title *</label>
                            <input type="text" name="job_title" value="{{ old('job_title', $user->borrower->job_title ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('job_title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Workplace</label>
                            <input type="text" name="workplace" value="{{ old('workplace', $user->borrower->workplace ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional">
                            @error('workplace')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Employer Name *</label>
                            <input type="text" name="employer_name" value="{{ old('employer_name', $user->borrower->employer_name ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('employer_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Employer Email</label>
                            <input type="email" name="employer_email" value="{{ old('employer_email', $user->borrower->employer_email ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional">
                            @error('employer_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Employer Title</label>
                            <input type="text" name="employer_title" value="{{ old('employer_title', $user->borrower->employer_title ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional">
                            @error('employer_title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium">Department</label>
                            <input type="text" name="department" value="{{ old('department', $user->borrower->department ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional">
                            @error('department')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 6: Borrower Information -->
                <div class="mb-8" id="borrower-info">
                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-lg font-medium">Borrower Account Information</h5>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['borrower-info']['filled'] }}/{{ $sectionCounts['borrower-info']['total'] }}</span>
                            @if($sectionCounts['borrower-info']['filled'] === $sectionCounts['borrower-info']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                Complete
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                Incomplete
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Borrower National ID *</label>
                            <input type="text" name="national_id" value="{{ old('national_id', $user->borrower->national_id ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('national_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Client Type *</label>
                            <select name="client_type" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="0" {{ old('client_type', $user->borrower->client_type ?? '') == '0' ? 'selected' : '' }}>Individual</option>
                                <option value="1" {{ old('client_type', $user->borrower->client_type ?? '') == '1' ? 'selected' : '' }}>Business</option>
                            </select>
                            @error('client_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Account Status *</label>
                            <select name="status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="1" {{ old('status', $user->borrower->status ?? '') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('status', $user->borrower->status ?? '') == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- Role-Specific Fields for Broker -->
                @if($user->role === 'broker')
                    <div class="mb-8">
                        <h5 class="mb-4 text-lg font-medium">Broker Information</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-medium">Certificate Number</label>
                                <input type="text" name="cert_no" value="{{ old('cert_no', $user->broker->cert_no ?? '') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('cert_no')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium">Client Interest Rate (%)</label>
                                <input type="number" step="0.01" name="interest_client" value="{{ old('interest_client', $user->broker->interest_client ?? '') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('interest_client')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium">Broker Interest Rate (%)</label>
                                <input type="number" step="0.01" name="interest_broker" value="{{ old('interest_broker', $user->broker->interest_broker ?? '') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('interest_broker')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium">Client Penalty Rate (%)</label>
                                <input type="number" step="0.01" name="penalty_client" value="{{ old('penalty_client', $user->broker->penalty_client ?? '') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('penalty_client')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium">Broker Penalty Rate (%)</label>
                                <input type="number" step="0.01" name="penalty_broker" value="{{ old('penalty_broker', $user->broker->penalty_broker ?? '') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('penalty_broker')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Role-Specific Fields for Teller -->
                @if($user->role === 'teller')
                    <div class="mb-8">
                        <h5 class="mb-4 text-lg font-medium">Teller Information</h5>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block mb-2 text-sm font-medium">Branch</label>
                                <input type="text" name="branch" value="{{ old('branch', $user->teller->branch ?? '') }}" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('branch')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="ProfileEditor.resetForm()" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Reset Form
                    </button>
                    <a href="{{ route('profile.show') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Signature Section -->
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold">Digital Signature</h3>
                <div class="flex items-center gap-2">
                    @if($user->signature)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Signature Saved
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                            No Signature
                        </span>
                    @endif
                </div>
            </div>

            {{-- Existing Signature Display --}}
            @if($user->signature)
            <div id="existing-signature-display" class="mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-shrink-0">
                            <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-200 dark:border-green-700 shadow-sm">
                                <div class="w-32 h-32 flex items-center justify-center bg-gray-50 dark:bg-gray-900 rounded">
                                    @if($user->signature)
                                        <img src="{{ asset('storage/images/signatures/' . $user->signature) }}?v={{ time() }}"
                                            alt="Signature of {{ $user->name }}"
                                            class="max-w-full max-h-full object-contain"
                                            onerror="this.style.display='none';">
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-green-800 dark:text-green-300 text-lg">
                                {{ $user->name }}
                            </h4>
                            <p class="text-green-700 dark:text-green-400 text-sm mb-2">
                                ✅ Signature Found
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <!--<button type="button" @click="showDeleteSignatureModal = true" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors">-->
                                <!--    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
                                <!--        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>-->
                                <!--    </svg>-->
                                <!--    Delete Signature-->
                                <!--</button>-->
                                <button type="button" @click="showSignaturePad = true" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                    Edit Signature
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Signature Creation/Edit Section --}}
            <div id="signature-creation-section" class="@if($user->signature) border-t border-gray-200 dark:border-gray-700 pt-6 @endif">
                <h4 class="text-md font-medium mb-4 text-gray-700 dark:text-gray-300">
                    @if($user->signature)
                        Update Your Signature
                    @else
                        Create Your Digital Signature
                    @endif
                </h4>
                
                <div id="signature-section" x-show="showSignaturePad || !{{ $user->signature ? 'true' : 'false' }}">
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            @if($user->signature)
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
                                    @if($user->signature)
                                        Save New Signature
                                    @else
                                        Save Signature
                                    @endif
                                </button>
                                <div class="text-xs text-gray-500 dark:text-gray-400 text-center sm:text-left">
                                    @if($user->signature)
                                        This will replace your existing signature
                                    @else
                                        Draw your signature to fill the square area
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div id="signature-status" class="mt-2 text-sm text-center"></div>
                        
                        {{-- Signature Preview --}}
                        <div id="signature-preview-container" class="mt-6 hidden">
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center">
                                    @if($user->signature)
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
                                            <strong>File name:</strong> <span id="signature-filename">signature_{{ preg_replace('/[^a-zA-Z0-9]/', '_', $user->name) }}.png</span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 max-w-md">
                                            Your signature will be saved as a square transparent PNG.
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

        <!-- Password Update Section -->
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold">Password Settings</h3>
                <button @click="showPasswordModal = true" class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Change Password
                </button>
            </div>

            <div class="text-sm text-gray-500 dark:text-gray-400">
                Last changed: {{ $user->password_changed_at ? $user->password_changed_at->diffForHumans() : 'Never' }}
            </div>
        </div>
    </main>

    <!-- Delete Signature Modal -->
    <div x-show="showDeleteSignatureModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-5">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="showDeleteSignatureModal = false"></div>
        <div @click.stop class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-red-600">Delete Signature</h3>
                    <button @click="showDeleteSignatureModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="mb-6">
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        Are you sure you want to delete your digital signature? This action cannot be undone.
                    </p>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <div class="flex items-center text-red-800 dark:text-red-300">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium">Warning:</span> This will remove your signature from all future loan agreements.
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('profile.signature.delete') }}">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteSignatureModal = false" type="button" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">
                            Delete Signature
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div x-show="showPasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-5">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="showPasswordModal = false"></div>
        <div @click.stop class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Change Password</h3>
                    <button @click="showPasswordModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Current Password</label>
                            <input type="password" name="current_password" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">New Password</label>
                            <input type="password" name="password" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                        <button @click="showPasswordModal = false" type="button" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include the JavaScript file -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>



<script>
function profileData() {
    return {
        showPasswordModal: false,
        showDeleteSignatureModal: false,
        showSignaturePad: {{ $user->signature ? 'false' : 'true' }},
        signatureData: '',
        activeSection: 'basic',
        
        init() {
            // Check if there are password errors and open the modal if so
            @if($errors->has('current_password') || $errors->has('password'))
                this.showPasswordModal = true;
            @endif
            
            // Initialize signature pad
            this.initializeSignaturePad();
            
            // Initialize section navigation
            this.initializeSectionNavigation();
            
            // Initialize file previews
            this.initializeFilePreviews();
            
            // Initialize date pickers
            this.initializeDatePickers();
            
            // Setup education field
            this.setupEducationField();
            
            // Setup real-time validation
            this.setupRealTimeValidation();
        },
        
        initializeDatePickers() {
            // Wait for DOM to be fully loaded
            this.$nextTick(() => {
                const datePickers = document.querySelectorAll('.datepicker');
                
                datePickers.forEach((datepicker) => {
                    // Remove any existing flatpickr instances
                    if (datepicker._flatpickr) {
                        datepicker._flatpickr.destroy();
                    }
                    
                    // Initialize flatpickr with DD/MM/YYYY format
                    flatpickr(datepicker, {
                        dateFormat: "d/m/Y",
                        allowInput: true,
                        clickOpens: true,
                        disableMobile: true,
                        locale: {
                            firstDayOfWeek: 1
                        },
                        onChange: (selectedDates, dateStr, instance) => {
                            // Trigger age calculation when date changes
                            if (datepicker.name === 'dob') {
                                this.calculateAgeFromDDMMYYYY(dateStr);
                            }
                        }
                    });
                });
            });
        },
        
        calculateAgeFromDDMMYYYY(dateString) {
            if (!dateString) return;
            
            // Parse DD/MM/YYYY format
            const parts = dateString.split('/');
            if (parts.length === 3) {
                const day = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1; // Months are 0-indexed
                const year = parseInt(parts[2], 10);
                
                const dob = new Date(year, month, day);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                // Show age validation
                if (age < 18) {
                    this.showFieldError('dob', 'You must be at least 18 years old');
                } else if (age > 100) {
                    this.showFieldError('dob', 'Please enter a valid date of birth');
                } else {
                    this.clearFieldError('dob');
                    
                    // Show age info
                    let ageInfo = document.getElementById('age-info');
                    if (!ageInfo) {
                        ageInfo = document.createElement('p');
                        ageInfo.id = 'age-info';
                        ageInfo.className = 'mt-1 text-sm text-gray-600 dark:text-gray-400';
                        const dobInput = document.querySelector('input[name="dob"]');
                        if (dobInput) {
                            dobInput.parentNode.parentNode.appendChild(ageInfo);
                        }
                    }
                    ageInfo.textContent = `Age: ${age} years`;
                }
            }
        },
        
        initializeSignaturePad() {
            const canvas = document.querySelector('#signature-pad canvas');
            if (!canvas) return;
            
            const CANVAS_SIZE = 400;
            
            // Set explicit square dimensions
            canvas.width = CANVAS_SIZE;
            canvas.height = CANVAS_SIZE;
            canvas.style.width = CANVAS_SIZE + 'px';
            canvas.style.height = CANVAS_SIZE + 'px';
            
            // Initialize signature pad
            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 2,
                maxWidth: 4,
                throttle: 16,
                velocityFilterWeight: 0.7
            });

            // Clear signature
            const clearButton = document.getElementById('clear-signature');
            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    this.signaturePad.clear();
                    this.updateSignatureStatus('Signature cleared', 'text-gray-600');
                    this.hideSignaturePreview();
                });
            }

            // Save signature
            const saveButton = document.getElementById('save-signature');
            if (saveButton) {
                saveButton.addEventListener('click', () => {
                    if (this.signaturePad.isEmpty()) {
                        this.updateSignatureStatus('Please provide a signature first', 'text-red-500');
                        return;
                    }

                    const signatureData = this.getFullSquareSignature();
                    this.saveSignatureToServer(signatureData);
                    this.showSignaturePreview(signatureData);
                });
            }

            // Handle touch/mouse events
            canvas.addEventListener('touchstart', (e) => {
                e.preventDefault();
            });
            
            canvas.addEventListener('touchmove', (e) => {
                e.preventDefault();
            });

            // Auto-update preview when drawing
            canvas.addEventListener('mouseup', () => {
                if (!this.signaturePad.isEmpty()) {
                    const signatureData = this.getFullSquareSignature();
                    this.showSignaturePreview(signatureData);
                }
            });
        },
        
        getFullSquareSignature() {
            return this.signaturePad.toDataURL('image/png');
        },
        
        updateSignatureStatus(message, className) {
            const statusEl = document.getElementById('signature-status');
            if (statusEl) {
                statusEl.textContent = message;
                statusEl.className = 'mt-2 text-sm ' + className;
            }
        },
        
        showSignaturePreview(dataUrl) {
            const container = document.getElementById('signature-preview-container');
            const preview = document.getElementById('signature-preview');
            
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
        },
        
        hideSignaturePreview() {
            const container = document.getElementById('signature-preview-container');
            container.classList.add('hidden');
        },
        
        async saveSignatureToServer(signatureData) {
            try {
                this.updateSignatureStatus('Saving signature...', 'text-blue-500');
                
                const response = await fetch('{{ route("profile.signature.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        signature_data: signatureData
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.updateSignatureStatus('Signature saved successfully! Reloading...', 'text-green-500');
                    
                    // Show success message and reload
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.updateSignatureStatus('Error: ' + result.message, 'text-red-500');
                    console.error('Signature save error:', result);
                }
            } catch (error) {
                console.error('Error saving signature:', error);
                this.updateSignatureStatus('Network error saving signature', 'text-red-500');
            }
        },
        
        initializeSectionNavigation() {
            // Add smooth scrolling to sections
            const sections = document.querySelectorAll('[data-section]');
            sections.forEach(section => {
                section.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = section.getAttribute('data-section');
                    this.scrollToSection(targetId);
                });
            });
            
            // Highlight current section on scroll
            this.observeSections();
        },
        
        scrollToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                const headerOffset = 100;
                const elementPosition = section.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                this.activeSection = sectionId;
            }
        },
        
        observeSections() {
            const sections = document.querySelectorAll('.section-anchor');
            const observerOptions = {
                root: null,
                rootMargin: '-20% 0px -70% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.activeSection = entry.target.id;
                    }
                });
            }, observerOptions);

            sections.forEach(section => {
                observer.observe(section);
            });
        },
        
        initializeFilePreviews() {
            // ID Front Photo Preview
            const idFrontInput = document.querySelector('input[name="id_front_path"]');
            if (idFrontInput) {
                idFrontInput.addEventListener('change', (e) => {
                    this.previewImage(e.target, 'id-front-preview');
                });
            }
            
            // ID Back Photo Preview
            const idBackInput = document.querySelector('input[name="id_back_path"]');
            if (idBackInput) {
                idBackInput.addEventListener('change', (e) => {
                    this.previewImage(e.target, 'id-back-preview');
                });
            }
            
            // Profile Photo Preview
            const profilePhotoInput = document.querySelector('input[name="profile_photo"]');
            if (profilePhotoInput) {
                profilePhotoInput.addEventListener('change', (e) => {
                    this.previewImage(e.target, 'profile-photo-preview');
                });
            }
        },
        
        previewImage(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    let previewContainer = document.getElementById(previewId);
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.id = previewId;
                        previewContainer.className = 'mt-2';
                        input.parentNode.appendChild(previewContainer);
                    }
                    
                    previewContainer.innerHTML = `
                        <div class="inline-block relative">
                            <img src="${e.target.result}" alt="Preview" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                            <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                ×
                            </button>
                        </div>
                    `;
                };
                
                reader.readAsDataURL(file);
            }
        },
        
        // Form validation helpers
        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        validatePhone(phone) {
            const re = /^[\+]?[1-9][\d]{0,15}$/;
            return re.test(phone.replace(/[\s\-\(\)]/g, ''));
        },
        
        showFieldError(fieldName, message) {
            let errorElement = document.getElementById(`${fieldName}-error`);
            if (!errorElement) {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    errorElement = document.createElement('p');
                    errorElement.id = `${fieldName}-error`;
                    errorElement.className = 'mt-1 text-sm text-red-600 dark:text-red-400';
                    field.parentNode.appendChild(errorElement);
                }
            }
            
            if (errorElement) {
                errorElement.textContent = message;
            }
        },
        
        clearFieldError(fieldName) {
            const errorElement = document.getElementById(`${fieldName}-error`);
            if (errorElement) {
                errorElement.remove();
            }
        },
        
        // Real-time validation
        setupRealTimeValidation() {
            // Email validation
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput) {
                emailInput.addEventListener('blur', () => {
                    if (emailInput.value && !this.validateEmail(emailInput.value)) {
                        this.showFieldError('email', 'Please enter a valid email address');
                    } else {
                        this.clearFieldError('email');
                    }
                });
            }
            
            // Phone validation
            const phoneInput = document.querySelector('input[name="phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('blur', () => {
                    if (phoneInput.value && !this.validatePhone(phoneInput.value)) {
                        this.showFieldError('phone', 'Please enter a valid phone number');
                    } else {
                        this.clearFieldError('phone');
                    }
                });
            }
            
            // Kin Email validation
            const kinEmailInput = document.querySelector('input[name="kin_email"]');
            if (kinEmailInput) {
                kinEmailInput.addEventListener('blur', () => {
                    if (kinEmailInput.value && !this.validateEmail(kinEmailInput.value)) {
                        this.showFieldError('kin_email', 'Please enter a valid email address for next of kin');
                    } else {
                        this.clearFieldError('kin_email');
                    }
                });
            }
            
            // Kin Phone validation
            const kinPhoneInput = document.querySelector('input[name="kin_phone"]');
            if (kinPhoneInput) {
                kinPhoneInput.addEventListener('blur', () => {
                    if (kinPhoneInput.value && !this.validatePhone(kinPhoneInput.value)) {
                        this.showFieldError('kin_phone', 'Please enter a valid phone number for next of kin');
                    } else {
                        this.clearFieldError('kin_phone');
                    }
                });
            }
        },
        
        // Progress calculation
        calculateSectionProgress(sectionName) {
            const section = document.getElementById(sectionName);
            if (!section) return 0;
            
            const inputs = section.querySelectorAll('input[required], select[required], textarea[required]');
            const filled = Array.from(inputs).filter(input => {
                if (input.type === 'checkbox') return input.checked;
                return input.value.trim() !== '';
            }).length;
            
            return inputs.length > 0 ? Math.round((filled / inputs.length) * 100) : 100;
        },
        
        updateAllProgressBars() {
            const sections = ['basic', 'identification', 'next-of-kin', 'additional'];
            sections.forEach(section => {
                const progress = this.calculateSectionProgress(section);
                const progressBar = document.getElementById(`${section}-progress`);
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                    progressBar.textContent = `${progress}%`;
                }
            });
        },
        
        // Auto-save functionality (optional)
        setupAutoSave() {
            let saveTimeout;
            const form = document.querySelector('form');
            
            if (form) {
                form.addEventListener('input', (e) => {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(() => {
                        this.autoSaveForm();
                    }, 2000);
                });
            }
        },
        
        async autoSaveForm() {
            const form = document.querySelector('form');
            if (!form) return;
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch('{{ route("profile.update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                if (response.ok) {
                    this.showAutoSaveNotification('Changes saved automatically', 'success');
                }
            } catch (error) {
                console.error('Auto-save failed:', error);
            }
        },
        
        showAutoSaveNotification(message, type) {
            // Remove existing notification
            const existingNotification = document.getElementById('auto-save-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            // Create new notification
            const notification = document.createElement('div');
            notification.id = 'auto-save-notification';
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        },
        
        // Export profile data (for debugging)
        exportProfileData() {
            const form = document.querySelector('form');
            if (!form) return;
            
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            console.log('Profile Data:', data);
            return data;
        },
        
        // Print profile
        printProfile() {
            window.print();
        },
        
        // Dark mode helpers
        toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        },

        setupEducationField() {
            const educationSelect = document.querySelector('select[name="education"]');
            const educationCustomInput = document.getElementById('education-custom-input');
            
            if (educationSelect && educationCustomInput) {
                educationSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        educationCustomInput.classList.remove('hidden');
                        educationCustomInput.required = true;
                    } else {
                        educationCustomInput.classList.add('hidden');
                        educationCustomInput.required = false;
                    }
                });
                
                // Trigger change on page load
                educationSelect.dispatchEvent(new Event('change'));
            }
        }
    }
}

// Additional utility functions
document.addEventListener('DOMContentLoaded', function() {
    // Initialize main Alpine.js component
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded');
        return;
    }
    
    // Load SignaturePad if not already loaded
    if (typeof SignaturePad === 'undefined') {
        console.warn('SignaturePad not loaded, loading from CDN...');
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js';
        script.onload = () => {
            console.log('SignaturePad loaded successfully');
        };
        document.head.appendChild(script);
    }
    
    // Load Flatpickr if not already loaded
    if (typeof flatpickr === 'undefined') {
        console.warn('Flatpickr not loaded, loading from CDN...');
        const flatpickrCSS = document.createElement('link');
        flatpickrCSS.rel = 'stylesheet';
        flatpickrCSS.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
        document.head.appendChild(flatpickrCSS);
        
        const flatpickrScript = document.createElement('script');
        flatpickrScript.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
        flatpickrScript.onload = () => {
            console.log('Flatpickr loaded successfully');
        };
        document.head.appendChild(flatpickrScript);
    }
    
    // Add section anchors for navigation
    const sections = [
        { id: 'basic', title: 'Basic Information' },
        { id: 'identification', title: 'Identification' },
        { id: 'next-of-kin', title: 'Next of Kin' },
        { id: 'additional', title: 'Additional Information' }
    ];
    
    sections.forEach(section => {
        const sectionElement = document.getElementById(section.id);
        if (sectionElement) {
            const anchor = document.createElement('div');
            anchor.id = section.id;
            anchor.className = 'section-anchor';
            anchor.style.height = '1px';
            anchor.style.marginTop = '-100px';
            anchor.style.paddingTop = '100px';
            sectionElement.parentNode.insertBefore(anchor, sectionElement);
        }
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.click();
            }
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('[x-show]');
            modals.forEach(modal => {
                if (modal.style.display !== 'none') {
                    const closeButton = modal.querySelector('button[aria-label="Close"]');
                    if (closeButton) closeButton.click();
                }
            });
        }
    });
    
    // Add form persistence
    window.addEventListener('beforeunload', function(e) {
        const form = document.querySelector('form');
        if (form) {
            const formData = new FormData(form);
            let hasData = false;
            
            for (let value of formData.values()) {
                if (value) {
                    hasData = true;
                    break;
                }
            }
            
            if (hasData) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        }
    });
    
    // Handle date format conversion before form submission
    const form = document.getElementById('profile-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Convert DD/MM/YYYY dates to YYYY-MM-DD for server processing
            const dateInputs = form.querySelectorAll('.datepicker');
            dateInputs.forEach(input => {
                if (input.value) {
                    const parts = input.value.split('/');
                    if (parts.length === 3) {
                        // Create a hidden input with the formatted date
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name;
                        hiddenInput.value = `${parts[2]}-${parts[1]}-${parts[0]}`;
                        form.appendChild(hiddenInput);
                        
                        // Disable the original input to prevent duplicate submission
                        input.disabled = true;
                    }
                }
            });
        });
    }
    
    // Initialize character counters for textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (maxLength) {
            const counter = document.createElement('div');
            counter.className = 'text-right text-xs text-gray-500 dark:text-gray-400 mt-1';
            counter.textContent = `0/${maxLength}`;
            
            textarea.parentNode.appendChild(counter);
            
            textarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                counter.textContent = `${currentLength}/${maxLength}`;
                
                if (currentLength > maxLength * 0.9) {
                    counter.classList.add('text-orange-500');
                } else {
                    counter.classList.remove('text-orange-500');
                }
                
                if (currentLength > maxLength) {
                    counter.classList.add('text-red-500');
                } else {
                    counter.classList.remove('text-red-500');
                }
            });
        }
    });
    
    // Enhanced file input styling
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            const label = this.nextElementSibling || this.parentNode.querySelector('label');
            
            if (label && label.textContent.includes('Choose file')) {
                label.textContent = fileName;
            }
        });
    });
    
    console.log('Profile edit page initialized successfully');
});

// Error boundary for the component
window.addEventListener('error', function(e) {
    console.error('Global error caught:', e.error);
    
    // Show user-friendly error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
    errorDiv.textContent = 'Something went wrong. Please refresh the page.';
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
});

// Performance monitoring
const observer = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
        console.log(`${entry.name}: ${entry.duration}ms`);
    });
});

observer.observe({ entryTypes: ['measure', 'navigation'] });

// Export for global access (for debugging)
window.ProfileEditor = {
    validateForm: function() {
        const form = document.querySelector('form');
        return form.checkValidity();
    },
    
    getFormData: function() {
        const form = document.querySelector('form');
        return new FormData(form);
    },
    
    resetForm: function() {
        const form = document.querySelector('form');
        if (form) {
            form.reset();
            
            // Clear all previews
            const previews = document.querySelectorAll('[id$="-preview"]');
            previews.forEach(preview => preview.remove());
            
            // Clear all custom error messages
            const errors = document.querySelectorAll('[id$="-error"]');
            errors.forEach(error => error.remove());
            
            // Clear age info
            const ageInfo = document.getElementById('age-info');
            if (ageInfo) ageInfo.remove();
            
            // Reset date pickers
            const datePickers = document.querySelectorAll('.datepicker');
            datePickers.forEach(datepicker => {
                if (datepicker._flatpickr) {
                    datepicker._flatpickr.clear();
                }
            });
        }
    },
    
    scrollToSection: function(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            const headerOffset = 100;
            const elementPosition = section.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    },
    
    scrollToTop: function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    
    scrollToBottom: function() {
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    },
    
    // Date picker utilities
    initializeDatePickers: function() {
        const datePickers = document.querySelectorAll('.datepicker');
        datePickers.forEach((datepicker) => {
            if (datepicker._flatpickr) {
                datepicker._flatpickr.destroy();
            }
            
            flatpickr(datepicker, {
                dateFormat: "d/m/Y",
                allowInput: true,
                clickOpens: true,
                disableMobile: true,
                locale: {
                    firstDayOfWeek: 1
                }
            });
        });
    },
    
    // Export form data as JSON
    exportFormData: function() {
        const form = document.querySelector('form');
        if (!form) return null;
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return JSON.stringify(data, null, 2);
    }
};
</script>
@endsection