    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 z-99999 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen p-5">
            <!-- Backdrop -->
            <div onclick="document.getElementById('editProfileModal').classList.add('hidden')" 
                 class="fixed inset-0 az-backdrop"></div>
            
            <!-- Modal Content -->
            <div class="az-sheet az-sheet-wide relative w-full max-w-4xl rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-10 z-50 max-h-[90vh] overflow-y-auto">
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
                    @if($user->hasRole('borrower') && $user->borrower)
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

                            @if(auth()->user()->hasRole('admin'))
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
                                    @foreach (\App\Models\User::ID_TYPES as $idValue => $idLabel)
                                        <option value="{{ $idValue }}" @selected(\App\Models\User::normaliseIdType(old('id_type', $user->id_type)) === $idValue)>{{ $idLabel }}</option>
                                    @endforeach
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
                    @if($user->hasRole('borrower'))
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
                                    Current: <a href="{{ $user->documentUrl('photo') }}" target="_blank" class="text-blue-600 hover:underline">View</a>
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
                                    Current: <a href="{{ $user->documentUrl('signature') }}" target="_blank" class="text-blue-600 hover:underline">View</a>
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
                                    Current: <a href="{{ $user->documentUrl('id-front') }}" target="_blank" class="text-blue-600 hover:underline">View</a>
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
                                    Current: <a href="{{ $user->documentUrl('id-back') }}" target="_blank" class="text-blue-600 hover:underline">View</a>
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
