@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 transition-colors duration-200" 
     x-data="profileData()"
     x-init="init()">
    
    <div id="auto-save-notification" class="hidden"></div>

    <main class="mx-auto max-w-screen-2xl p-4 md:p-6">
        <!-- Breadcrumb -->
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

        <!-- Quick Navigation with Section Counts -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex flex-wrap gap-3 justify-between items-center">
                <div class="flex flex-wrap gap-2">
                    @foreach($sectionCounts as $key => $section)
                    <button @click="scrollToSection('{{ $key }}')" 
                            :class="activeSection === '{{ $key }}' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                            class="px-4 py-3 rounded-lg text-sm font-medium transition-colors min-w-[130px] text-left">
                        <div class="font-semibold text-xs md:text-sm">{{ $section['name'] }}</div>
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
                        Export
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
            <div class="flex items-center justify-between flex-wrap gap-4">
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
                        {{ ucfirst(str_replace('_', ' ', $field)) }}
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

                <!-- ============================================ -->
                <!-- BUCKET 1: PERSONAL INFORMATION                -->
                <!-- ============================================ -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="basic">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">👤</span>
                            <h5 class="text-lg font-medium">Personal Information</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['basic']['filled'] }}/{{ $sectionCounts['basic']['total'] }}</span>
                            @if($sectionCounts['basic']['filled'] === $sectionCounts['basic']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Complete</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Incomplete</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Basic personal details including contact information and demographics.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Phone Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Gender <span class="text-red-500">*</span></label>
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
                        <div>
                            <label class="block mb-2 text-sm font-medium">Date of Birth <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="text" name="dob" 
                                       value="{{ old('dob', $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d/m/Y') : '') }}"
                                       placeholder="DD/MM/YYYY"
                                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 datepicker"
                                       required readonly>
                                <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z"/>
                                    </svg>
                                </span>
                            </div>
                            <div id="age-info" class="mt-1 text-sm"></div>
                            @error('dob')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Nationality <span class="text-red-500">*</span></label>
                            <select name="nationality" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">Select Nationality</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country['code'] }}" 
                                        {{ (old('nationality', $user->nationality ?? '') == $country['code']) ? 'selected' : '' }}>
                                        {{ $country['flag'] }} {{ $country['name'] }} ({{ $country['nationality'] }})
                                    </option>
                                @endforeach
                            </select>
                            @error('nationality')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Marital Status <span class="text-red-500">*</span></label>
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
                            <select name="education" id="education-select" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Education (Optional)</option>
                                @foreach($educationLevels as $education)
                                <option value="{{ $education->name }}" {{ old('education', $user->education) == $education->name ? 'selected' : '' }}>
                                    {{ $education->name }}
                                </option>
                                @endforeach
                                <option value="other">Other (Specify)</option>
                            </select>
                            <input type="text" name="education_custom" 
                                id="education-custom-input"
                                value="{{ old('education_custom', '') }}"
                                placeholder="Specify your education level"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mt-2 hidden">
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
                        <div>
                            <label class="block mb-2 text-sm font-medium">Profile Photo</label>
                            <input type="file" name="profile_photo" accept="image/*" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if($user->profile_photo_path)
                            <p class="mt-1 text-sm text-green-600">Current photo uploaded</p>
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="Profile Photo" class="w-20 h-20 rounded-full object-cover">
                            </div>
                            @endif
                            @error('profile_photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- BUCKET 2: IDENTIFICATION                      -->
                <!-- ============================================ -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="identification">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🪪</span>
                            <h5 class="text-lg font-medium">Identification Documents</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['identification']['filled'] }}/{{ $sectionCounts['identification']['total'] }}</span>
                            @if($sectionCounts['identification']['filled'] === $sectionCounts['identification']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Complete</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Incomplete</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Government-issued identification documents for verification.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Type <span class="text-red-500">*</span></label>
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
                            <label class="block mb-2 text-sm font-medium">ID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="id_number" value="{{ old('id_number', $user->id_number) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('id_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Front Photo <span class="text-red-500">*</span></label>
                            <input type="file" name="id_front_path" accept="image/*" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if($user->id_front_path)
                            <p class="mt-1 text-sm text-green-600">Current file uploaded</p>
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $user->id_front_path) }}" alt="ID Front" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            @endif
                            @error('id_front_path')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">ID Back Photo <span class="text-red-500">*</span></label>
                            <input type="file" name="id_back_path" accept="image/*" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @if($user->id_back_path)
                            <p class="mt-1 text-sm text-green-600">Current file uploaded</p>
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $user->id_back_path) }}" alt="ID Back" class="w-32 h-32 object-cover rounded-lg border border-gray-300">
                            </div>
                            @endif
                            @error('id_back_path')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- BUCKET 3: FAMILY & NEXT OF KIN                -->
                <!-- ============================================ -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="next-of-kin">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">👨‍👩‍👧‍👦</span>
                            <h5 class="text-lg font-medium">Family & Next of Kin</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['next-of-kin']['filled'] }}/{{ $sectionCounts['next-of-kin']['total'] }}</span>
                            @if($sectionCounts['next-of-kin']['filled'] === $sectionCounts['next-of-kin']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Complete</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Incomplete</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Emergency contact and next of kin details for security and contact purposes.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="kin_name" value="{{ old('kin_name', $user->kin_name) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="kin_email" value="{{ old('kin_email', $user->kin_email) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Phone Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="kin_phone" value="{{ old('kin_phone', $user->kin_phone) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Occupation <span class="text-red-500">*</span></label>
                            <input type="text" name="kin_occupation" value="{{ old('kin_occupation', $user->kin_occupation) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_occupation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Relationship <span class="text-red-500">*</span></label>
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
                            <label class="block mb-2 text-sm font-medium">Kin's ID Type <span class="text-red-500">*</span></label>
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
                            <label class="block mb-2 text-sm font-medium">Kin's ID Number <span class="text-red-500">*</span></label>
                            <input type="text" name="kin_id_number" value="{{ old('kin_id_number', $user->kin_id_number) }}" 
                                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            @error('kin_id_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- BUCKET 4: EMPLOYMENT & FINANCIAL (Borrowers)  -->
                <!-- ============================================ -->
                @if($user->role === 'borrower')
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="employment">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">💼</span>
                            <h5 class="text-lg font-medium">Employment & Financial Information</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['employment']['filled'] }}/{{ $sectionCounts['employment']['total'] }}</span>
                            @if($sectionCounts['employment']['filled'] === $sectionCounts['employment']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Complete</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Incomplete</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Employment details and income information for loan assessment.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Income Type <span class="text-red-500">*</span></label>
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
                            <label class="block mb-2 text-sm font-medium">Net Salary ({{ config('app.currency', '$') }}) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="net_salary" value="{{ old('net_salary', $user->borrower->net_salary ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('net_salary')
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
                            <label class="block mb-2 text-sm font-medium">Job Title <span class="text-red-500">*</span></label>
                            <input type="text" name="job_title" value="{{ old('job_title', $user->borrower->job_title ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('job_title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Employer Name <span class="text-red-500">*</span></label>
                            <input type="text" name="employer_name" value="{{ old('employer_name', $user->borrower->employer_name ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required>
                            @error('employer_name')
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
                            <label class="block mb-2 text-sm font-medium">Employer Email</label>
                            <input type="email" name="employer_email" value="{{ old('employer_email', $user->borrower->employer_email ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Optional">
                            @error('employer_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
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

                <!-- ============================================ -->
                <!-- BUCKET 5: BORROWER ACCOUNT                    -->
                <!-- ============================================ -->
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="borrower-info">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🏦</span>
                            <h5 class="text-lg font-medium">Borrower Account</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['borrower-info']['filled'] }}/{{ $sectionCounts['borrower-info']['total'] }}</span>
                            @if($sectionCounts['borrower-info']['filled'] === $sectionCounts['borrower-info']['total'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Complete</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Incomplete</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Account settings and preferences for borrowing.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Client Type <span class="text-red-500">*</span></label>
                            <select name="client_type" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="0" {{ old('client_type', $user->borrower->client_type ?? '') == '0' ? 'selected' : '' }}>Individual</option>
                                <option value="1" {{ old('client_type', $user->borrower->client_type ?? '') == '1' ? 'selected' : '' }}>Business</option>
                            </select>
                            @error('client_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Account Status <span class="text-red-500">*</span></label>
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

                <!-- ============================================ -->
                <!-- BUCKET 6: BROKER INFORMATION (Brokers)        -->
                <!-- ============================================ -->
                @if($user->role === 'broker')
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="broker-info">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🤝</span>
                            <h5 class="text-lg font-medium">Broker Information</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['broker-info']['filled'] ?? 0 }}/{{ $sectionCounts['broker-info']['total'] ?? 5 }}</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Broker certification and commission rates.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Certificate Number <span class="text-red-500">*</span></label>
                            <input type="text" name="cert_no" value="{{ old('cert_no', $user->broker->cert_no ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('cert_no')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Client Interest Rate (%) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="interest_client" value="{{ old('interest_client', $user->broker->interest_client ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('interest_client')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Broker Interest Rate (%) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="interest_broker" value="{{ old('interest_broker', $user->broker->interest_broker ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('interest_broker')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Client Penalty Rate (%) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="penalty_client" value="{{ old('penalty_client', $user->broker->penalty_client ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('penalty_client')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium">Broker Penalty Rate (%) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="penalty_broker" value="{{ old('penalty_broker', $user->broker->penalty_broker ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('penalty_broker')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- ============================================ -->
                <!-- BUCKET 7: TELLER INFORMATION (Tellers)        -->
                <!-- ============================================ -->
                @if($user->role === 'teller')
                <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-6" id="teller-info">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🏧</span>
                            <h5 class="text-lg font-medium">Teller Information</h5>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-0.5 rounded-full">Required</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500">{{ $sectionCounts['teller-info']['filled'] ?? 0 }}/{{ $sectionCounts['teller-info']['total'] ?? 1 }}</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Branch assignment for teller operations.</p>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium">Branch <span class="text-red-500">*</span></label>
                            <input type="text" name="branch" value="{{ old('branch', $user->teller->branch ?? '') }}" 
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            @error('branch')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                @endif

                <!-- Form Actions -->
                <div class="flex flex-wrap justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
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
                <div class="flex items-center gap-3">
                    <span class="text-2xl">✍️</span>
                    <h3 class="text-lg font-semibold">Digital Signature</h3>
                </div>
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

            @if($user->signature)
            <div id="existing-signature-display" class="mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-shrink-0">
                            <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-green-200 dark:border-green-700 shadow-sm">
                                <div class="w-32 h-32 flex items-center justify-center bg-gray-50 dark:bg-gray-900 rounded">
                                    <img src="{{ asset('storage/' . $user->signature) }}?v={{ time() }}"
                                        alt="Signature of {{ $user->name }}"
                                        class="max-w-full max-h-full object-contain"
                                        onerror="this.style.display='none';">
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
                            <button type="button" @click="showSignaturePad = true" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                Update Signature
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                            <div class="flex justify-center">
                                <div id="signature-pad" class="signature-pad relative">
                                    <canvas class="border border-gray-300 rounded-lg bg-white" 
                                            style="touch-action: none; width: 400px; height: 400px; max-width: 100%;"></canvas>
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
                                            <div id="signature-preview" class="w-64 h-64 flex items-center justify-center bg-transparent"></div>
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

        <!-- Password Section -->
        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-800 lg:p-6">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🔒</span>
                    <h3 class="text-lg font-semibold">Password Settings</h3>
                </div>
                <button @click="showPasswordModal = true" class="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- Password Modal -->
    <div x-show="showPasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-5">
        <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" @click="showPasswordModal = false"></div>
        <div @click.stop class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Change Password</h3>
                    <button @click="showPasswordModal = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div class="mt-6 flex justify-end gap-3">
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

<!-- Include scripts -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
function profileData() {
    return {
        showPasswordModal: false,
        showSignaturePad: {{ $user->signature ? 'false' : 'true' }},
        signatureData: '',
        activeSection: 'basic',
        signaturePad: null,
        
        init() {
            @if($errors->has('current_password') || $errors->has('password'))
                this.showPasswordModal = true;
            @endif
            
            this.initializeSignaturePad();
            this.initializeSectionNavigation();
            this.initializeFilePreviews();
            this.initializeDatePickers();
            this.setupEducationField();
            this.setupRealTimeValidation();
        },
        
        initializeDatePickers() {
            this.$nextTick(() => {
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
                        },
                        onChange: (selectedDates, dateStr, instance) => {
                            if (datepicker.name === 'dob') {
                                this.calculateAge(dateStr);
                            }
                        }
                    });
                });
            });
        },
        
        calculateAge(dateString) {
            if (!dateString) return;
            
            const parts = dateString.split('/');
            if (parts.length === 3) {
                const day = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                const year = parseInt(parts[2], 10);
                
                const dob = new Date(year, month, day);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                
                const ageInfo = document.getElementById('age-info');
                if (ageInfo) {
                    if (age < 18) {
                        ageInfo.textContent = `⚠️ Age: ${age} years (Must be at least 18)`;
                        ageInfo.className = 'mt-1 text-sm text-red-600';
                    } else if (age > 100) {
                        ageInfo.textContent = `⚠️ Age: ${age} years (Please verify)`;
                        ageInfo.className = 'mt-1 text-sm text-yellow-600';
                    } else {
                        ageInfo.textContent = `✅ Age: ${age} years`;
                        ageInfo.className = 'mt-1 text-sm text-green-600';
                    }
                }
            }
        },
        
        initializeSignaturePad() {
            const canvas = document.querySelector('#signature-pad canvas');
            if (!canvas) return;
            
            const CANVAS_SIZE = 400;
            canvas.width = CANVAS_SIZE;
            canvas.height = CANVAS_SIZE;
            canvas.style.width = CANVAS_SIZE + 'px';
            canvas.style.height = CANVAS_SIZE + 'px';
            
            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)',
                minWidth: 2,
                maxWidth: 4,
                throttle: 16,
                velocityFilterWeight: 0.7
            });

            document.getElementById('clear-signature')?.addEventListener('click', () => {
                this.signaturePad.clear();
                this.updateSignatureStatus('Signature cleared', 'text-gray-600');
                this.hideSignaturePreview();
            });

            document.getElementById('save-signature')?.addEventListener('click', () => {
                if (this.signaturePad.isEmpty()) {
                    this.updateSignatureStatus('Please provide a signature first', 'text-red-500');
                    return;
                }
                const signatureData = this.signaturePad.toDataURL('image/png');
                this.saveSignatureToServer(signatureData);
                this.showSignaturePreview(signatureData);
            });

            canvas.addEventListener('touchstart', (e) => e.preventDefault());
            canvas.addEventListener('touchmove', (e) => e.preventDefault());

            canvas.addEventListener('mouseup', () => {
                if (!this.signaturePad.isEmpty()) {
                    const signatureData = this.signaturePad.toDataURL('image/png');
                    this.showSignaturePreview(signatureData);
                }
            });
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
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.src = dataUrl;
            img.alt = 'Signature Preview';
            img.className = 'w-full h-full object-contain';
            preview.appendChild(img);
            container.classList.remove('hidden');
        },
        
        hideSignaturePreview() {
            document.getElementById('signature-preview-container')?.classList.add('hidden');
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
                    body: JSON.stringify({ signature_data: signatureData })
                });

                const result = await response.json();

                if (result.success) {
                    this.updateSignatureStatus('Signature saved successfully! Reloading...', 'text-green-500');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.updateSignatureStatus('Error: ' + result.message, 'text-red-500');
                }
            } catch (error) {
                console.error('Error saving signature:', error);
                this.updateSignatureStatus('Network error saving signature', 'text-red-500');
            }
        },
        
        initializeSectionNavigation() {
            document.querySelectorAll('[data-section]').forEach(section => {
                section.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = section.getAttribute('data-section');
                    this.scrollToSection(targetId);
                });
            });
            this.observeSections();
        },
        
        scrollToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (section) {
                const headerOffset = 100;
                const elementPosition = section.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                this.activeSection = sectionId;
            }
        },
        
        observeSections() {
            const sections = document.querySelectorAll('.section-anchor');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.activeSection = entry.target.id;
                    }
                });
            }, { root: null, rootMargin: '-20% 0px -70% 0px', threshold: 0 });

            sections.forEach(section => observer.observe(section));
        },
        
        initializeFilePreviews() {
            const idFrontInput = document.querySelector('input[name="id_front_path"]');
            if (idFrontInput) {
                idFrontInput.addEventListener('change', (e) => this.previewImage(e.target, 'id-front-preview'));
            }
            
            const idBackInput = document.querySelector('input[name="id_back_path"]');
            if (idBackInput) {
                idBackInput.addEventListener('change', (e) => this.previewImage(e.target, 'id-back-preview'));
            }
            
            const profilePhotoInput = document.querySelector('input[name="profile_photo"]');
            if (profilePhotoInput) {
                profilePhotoInput.addEventListener('change', (e) => this.previewImage(e.target, 'profile-photo-preview'));
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
                            <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">×</button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        },
        
        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        validatePhone(phone) {
            return /^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/[\s\-\(\)]/g, ''));
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
            document.getElementById(`${fieldName}-error`)?.remove();
        },
        
        setupRealTimeValidation() {
            document.querySelector('input[name="email"]')?.addEventListener('blur', function() {
                if (this.value && !this._x_data?.validateEmail?.(this.value)) {
                    this._x_data?.showFieldError?.('email', 'Please enter a valid email address');
                } else {
                    this._x_data?.clearFieldError?.('email');
                }
            });
            
            document.querySelector('input[name="phone"]')?.addEventListener('blur', function() {
                if (this.value && !this._x_data?.validatePhone?.(this.value)) {
                    this._x_data?.showFieldError?.('phone', 'Please enter a valid phone number');
                } else {
                    this._x_data?.clearFieldError?.('phone');
                }
            });
        },
        
        setupEducationField() {
            const educationSelect = document.getElementById('education-select');
            const educationCustomInput = document.getElementById('education-custom-input');
            
            if (educationSelect && educationCustomInput) {
                educationSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        educationCustomInput.classList.remove('hidden');
                        educationCustomInput.required = true;
                    } else {
                        educationCustomInput.classList.add('hidden');
                        educationCustomInput.required = false;
                        educationCustomInput.value = '';
                    }
                });
                
                if (educationSelect.value === 'other') {
                    educationCustomInput.classList.remove('hidden');
                    educationCustomInput.required = true;
                }
            }
        },
        
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
        
        printProfile() {
            window.print();
        }
    }
}

// Global ProfileEditor
window.ProfileEditor = {
    resetForm: function() {
        const form = document.querySelector('form');
        if (form) {
            form.reset();
            document.querySelectorAll('[id$="-preview"]').forEach(el => el.remove());
            document.querySelectorAll('[id$="-error"]').forEach(el => el.remove());
            document.getElementById('age-info').textContent = '';
            document.querySelectorAll('.datepicker').forEach(dp => {
                if (dp._flatpickr) dp._flatpickr.clear();
            });
            const customInput = document.getElementById('education-custom-input');
            if (customInput) {
                customInput.classList.add('hidden');
                customInput.required = false;
            }
        }
    }
};

// Add section anchors
document.addEventListener('DOMContentLoaded', function() {
    ['basic', 'identification', 'next-of-kin', 'additional', 'employment', 'borrower-info', 'broker-info', 'teller-info'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            const anchor = document.createElement('div');
            anchor.id = id;
            anchor.className = 'section-anchor';
            anchor.style.cssText = 'height:1px;margin-top:-100px;padding-top:100px;';
            element.parentNode.insertBefore(anchor, element);
        }
    });

    // Ctrl+S to save
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.querySelector('button[type="submit"]')?.click();
        }
    });

    console.log('Profile edit page initialized');
});
</script>
@endsection