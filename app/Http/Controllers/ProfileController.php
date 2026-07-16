<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Hash;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\Category;
use PragmaRX\Countries\Package\Countries;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show(Request $request): View
    {
        $user = $request->user()->load(['broker', 'borrower', 'teller']);
        
        // Calculate completion percentage for the view
        $completionPercentage = $user->getBiodataCompletionPercentage();
        $missingFields = $user->getMissingBiodataFields();
        
        // Check if profile is locked
        $isProfileLocked = $user->shouldShowLock();
        $loanStatusMessage = $user->getLoanStatusMessage();
        $lockedFields = $user->getLockedFields();
        
        return view('profile.show', compact(
            'user', 
            'completionPercentage', 
            'missingFields',
            'isProfileLocked',
            'loanStatusMessage',
            'lockedFields'
        ));
    }

    public function edit(Request $request): View
    {
        $user = $request->user()->load(['broker', 'borrower', 'teller']);
        
        // Check if profile is locked
        $isProfileLocked = $user->shouldShowLock();
        $loanStatusMessage = $user->getLoanStatusMessage();
        $lockedFields = $user->getLockedFields();
        
        // Get categories for dropdowns
        $religions = Category::where('category_type', 'religion')->orderBy('name')->get();
        $relationships = Category::where('category_type', 'relationship')->orderBy('name')->get();
        $educationLevels = Category::where('category_type', 'education')->orderBy('name')->get();
        $incomeTypes = Category::where('category_type', 'income_type')->orderBy('name')->get();
        
        // Get countries for nationality dropdown
        $countries = Countries::all()
            ->map(function ($country) {
                $flag = '🏳️';
                if (isset($country->flag)) {
                    try {
                        if (is_object($country->flag) && property_exists($country->flag, 'emoji')) {
                            $flag = $country->flag->emoji;
                        } elseif (is_array($country->flag) && isset($country->flag['emoji'])) {
                            $flag = $country->flag['emoji'];
                        }
                    } catch (\Throwable $e) {
                        $flag = '🏳️';
                    }
                }
                return [
                    'code' => $country->cca2,
                    'iso3' => $country->cca3,
                    'name' => $country->name->common,
                    'official_name' => $country->name->official,
                    'flag' => $flag,
                    'nationality' => $country->demonyms['eng']['m'] ?? null,
                    'capital' => optional($country->capital)->first() ?? null,
                    'region' => $country->region ?? null,
                    'subregion' => $country->subregion ?? null,
                    'currency' => collect($country->currencies ?? [])->keys()->first(),
                    'currency_name' => collect($country->currencies ?? [])->first()['name'] ?? null,
                    'currency_symbol' => collect($country->currencies ?? [])->first()['symbol'] ?? null,
                    'calling_code' => isset($country->idd['root']) ? $country->idd['root'] . (optional($country->idd['suffixes'])->first() ?? '') : null,
                    'tld' => optional($country->tld)->first() ?? null,
                ];
            })
            ->sortBy('name')
            ->values();
            
        $hasSignature = !empty($user->signature);
        $completionPercentage = $user->getBiodataCompletionPercentage();
        $missingFields = $user->getMissingBiodataFields();
        $sectionCounts = $this->getSectionCompletionCounts($user);
        
        return view('profile.edit', compact(
            'user', 
            'hasSignature', 
            'completionPercentage', 
            'missingFields',
            'religions',
            'relationships',
            'educationLevels',
            'incomeTypes',
            'sectionCounts',
            'countries',
            'isProfileLocked',
            'loanStatusMessage',
            'lockedFields'
        ));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        // Check if profile is locked - prevent updates if locked
        if ($user->shouldShowLock()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your profile is locked because you have an active loan. Please contact support for assistance.',
                ], 403);
            }
            
            return redirect()->route('profile.show')
                ->withErrors(['error' => 'Your profile is locked because you have an active loan. Please contact support for assistance.']);
        }
        
        // Basic validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'required|string|max:20|unique:users,phone,'.$user->id,
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date_format:d/m/Y|before:today|after:01/01/1900',
            'nationality' => 'required|string|size:2',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'religion' => 'nullable|string|max:100',
            'education' => 'nullable|string|max:100',
            'disability' => 'boolean',
            
            // Identification fields
            'id_type' => 'required|in:national_id,passport,drivers_license',
            'id_number' => 'required|string|max:50',
            'id_front_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_back_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            
            // Next of kin fields
            'kin_name' => 'required|string|max:255',
            'kin_email' => 'required|email',
            'kin_phone' => 'required|string|max:20',
            'kin_occupation' => 'required|string|max:255',
            'kin_relation' => 'required|string|max:100',
            'kin_id_type' => 'required|in:national_id,passport',
            'kin_id_number' => 'required|string|max:50',
        ];
        
        // Add borrower-specific validation rules
        if ($user->role === 'borrower') {
            $rules = array_merge($rules, [
                'income_type' => 'required|string|max:50',
                'gross_salary' => 'nullable|numeric|min:0',
                'net_salary' => 'nullable|numeric|min:0',
                'job_title' => 'nullable|string|max:255',
                'workplace' => 'nullable|string|max:255',
                'employer_name' => 'nullable|string|max:255',
                'employer_email' => 'nullable|email',
                'employer_title' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'client_type' => 'required|in:0,1',
                'status' => 'required|in:0,1',
            ]);
        }
        
        // Add other role-specific validation rules
        if ($user->role === 'broker') {
            $rules = array_merge($rules, [
                'cert_no' => 'required|string|max:255|unique:brokers,cert_no,'.($user->broker ? $user->broker->id : 'NULL'),
                'interest_client' => 'required|numeric|min:0',
                'interest_broker' => 'required|numeric|min:0',
                'penalty_client' => 'required|numeric|min:0',
                'penalty_broker' => 'required|numeric|min:0',
            ]);
        } elseif ($user->role === 'teller') {
            $rules = array_merge($rules, [
                'branch' => 'required|string|max:255',
            ]);
        }
        
        $validatedData = $request->validate($rules);
        
        // Handle file uploads
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
            $validatedData['profile_photo_path'] = $profilePhotoPath;
        }
        
        if ($request->hasFile('id_front_path')) {
            $idFrontPath = $request->file('id_front_path')->store('id-documents', 'public');
            $validatedData['id_front_path'] = $idFrontPath;
        }
        
        if ($request->hasFile('id_back_path')) {
            $idBackPath = $request->file('id_back_path')->store('id-documents', 'public');
            $validatedData['id_back_path'] = $idBackPath;
        }
        
        // Convert disability to boolean
        $validatedData['disability'] = $request->has('disability');
        
        // Parse DOB from DD/MM/YYYY to YYYY-MM-DD for database
        if (!empty($validatedData['dob'])) {
            try {
                $validatedData['dob'] = Carbon::createFromFormat('d/m/Y', $validatedData['dob'])->format('Y-m-d');
            } catch (\Exception $e) {
                try {
                    $validatedData['dob'] = Carbon::parse($validatedData['dob'])->format('Y-m-d');
                } catch (\Exception $e2) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'dob' => ['Invalid date format. Please use DD/MM/YYYY.']
                    ]);
                }
            }
        }
        
        // Update basic user info
        $userData = $validatedData;
        $dob = $userData['dob'] ?? null;
        unset($userData['dob']);
        
        $user->fill($userData);
        if ($dob) {
            $user->dob = $dob;
        }
        
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        
        $user->save();
        
        // Update role-specific info
        if ($user->role === 'broker') {
            if ($user->broker) {
                $user->broker->update([
                    'cert_no' => $validatedData['cert_no'],
                    'interest_client' => $validatedData['interest_client'],
                    'interest_broker' => $validatedData['interest_broker'],
                    'penalty_client' => $validatedData['penalty_client'],
                    'penalty_broker' => $validatedData['penalty_broker'],
                ]);
            } else {
                $user->broker()->create([
                    'cert_no' => $validatedData['cert_no'],
                    'interest_client' => $validatedData['interest_client'],
                    'interest_broker' => $validatedData['interest_broker'],
                    'penalty_client' => $validatedData['penalty_client'],
                    'penalty_broker' => $validatedData['penalty_broker'],
                ]);
            }
        } elseif ($user->role === 'borrower') {
            $borrowerData = [
                'client_type' => $validatedData['client_type'],
                'status' => $validatedData['status'],
                'income_type' => $validatedData['income_type'] ?? null,
                'gross_salary' => $validatedData['gross_salary'] ?? null,
                'net_salary' => $validatedData['net_salary'] ?? null,
                'job_title' => $validatedData['job_title'] ?? null,
                'workplace' => $validatedData['workplace'] ?? null,
                'employer_name' => $validatedData['employer_name'] ?? null,
                'employer_email' => $validatedData['employer_email'] ?? null,
                'employer_title' => $validatedData['employer_title'] ?? null,
                'department' => $validatedData['department'] ?? null,
            ];
            
            if ($user->borrower) {
                $user->borrower->update($borrowerData);
            } else {
                $user->borrower()->create($borrowerData);
            }
        } elseif ($user->role === 'teller') {
            if ($user->teller) {
                $user->teller->update([
                    'branch' => $validatedData['branch'],
                ]);
            } else {
                $user->teller()->create([
                    'branch' => $validatedData['branch'],
                ]);
            }
        }
        
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'completion_percentage' => $user->getBiodataCompletionPercentage()
            ]);
        }
        
        return Redirect::route('profile.show')
            ->with('status', 'profile-updated')
            ->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        return redirect()->route('profile.edit')->with('status', 'password-updated');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);
        
        $request->user()->update([
            'password' => bcrypt($request->password)
        ]);
        
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        
        return back()->with('status', 'password-updated');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);
        
        $user = $request->user();
        
        Auth::logout();
        
        $user->delete();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($request->wantsJson()) {
            return response()->json(['redirect' => '/']);
        }
        
        return Redirect::to('/');
    }

    public function saveSignature(Request $request, SignatureService $signatureService): JsonResponse    {
        $user = $request->user();
        
        // Check if profile is locked
        if ($user->shouldShowLock()) {
            return response()->json([
                'success' => false,
                'message' => 'Your profile is locked because you have an active loan. Please contact support for assistance.',
            ], 403);
        }
        
        $request->validate([
            'signature_data' => 'required|string',
        ]);

        $result = $signatureService->saveSignature($request->signature_data, $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Signature saved successfully',
                'signature_url' => $result['url'],
                'filename' => $result['filename']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to save signature: ' . $result['error']
        ], 500);
    }

    public function deleteSignature(Request $request, SignatureService $signatureService): JsonResponse
    {
        $user = $request->user();
        
        // Check if profile is locked
        if ($user->shouldShowLock()) {
            return response()->json([
                'success' => false,
                'message' => 'Your profile is locked because you have an active loan. Please contact support for assistance.',
            ], 403);
        }
        
        $result = $signatureService->deleteSignature($user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Signature deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete signature: ' . $result['error']
        ], 500);
    }

    public function autoSave(Request $request)
    {
        $user = $request->user();
        
        // Check if profile is locked
        if ($user->shouldShowLock()) {
            return response()->json([
                'success' => false,
                'message' => 'Your profile is locked because you have an active loan. Please contact support for assistance.',
            ], 403);
        }
        
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'phone' => 'sometimes|required|string|max:20',
            'gender' => 'sometimes|required|in:male,female,other',
            'dob' => 'sometimes|required|date',
            'nationality' => 'sometimes|required|string|max:100',
            'marital_status' => 'sometimes|required|in:single,married,divorced,widowed',
            'religion' => 'sometimes|nullable|string|max:100',
            'education' => 'sometimes|nullable|string|max:100',
            'disability' => 'sometimes|boolean',
        ];
        
        $validatedData = $request->validate($rules);
        
        $user->fill($validatedData);
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Auto-saved successfully',
            'completion_percentage' => $user->getBiodataCompletionPercentage()
        ]);
    }

    private function getSectionCompletionCounts($user)
    {
        $sections = [
            'basic' => [
                'name' => 'Basic Information',
                'fields' => [
                    'user' => ['name', 'email', 'phone', 'gender', 'dob', 'nationality', 'marital_status'],
                    'borrower' => []
                ],
                'filled' => 0,
                'total' => 7
            ],
            'identification' => [
                'name' => 'Identification',
                'fields' => [
                    'user' => ['id_type', 'id_number', 'id_front_path', 'id_back_path'],
                    'borrower' => []
                ],
                'filled' => 0,
                'total' => 4
            ],
            'next-of-kin' => [
                'name' => 'Next of Kin',
                'fields' => [
                    'user' => ['kin_name', 'kin_email', 'kin_phone', 'kin_occupation', 'kin_relation', 'kin_id_type', 'kin_id_number'],
                    'borrower' => []
                ],
                'filled' => 0,
                'total' => 7
            ],
            'additional' => [
                'name' => 'Additional Information',
                'fields' => [
                    'user' => ['religion', 'education'],
                    'borrower' => []
                ],
                'filled' => 0,
                'total' => 2
            ],
            'employment' => [
                'name' => 'Employment Information',
                'fields' => [
                    'user' => [],
                    'borrower' => ['income_type', 'gross_salary', 'net_salary', 'job_title', 'workplace', 'employer_name', 'employer_email', 'employer_title', 'department']
                ],
                'filled' => 0,
                'total' => 9
            ],
            'borrower-info' => [
                'name' => 'Borrower Details',
                'fields' => [
                    'user' => [],
                    'borrower' => ['client_type', 'status']
                ],
                'filled' => 0,
                'total' => 2
            ]
        ];

        foreach ($sections as $key => &$section) {
            $filled = 0;
            $total = 0;
            
            // Count user fields
            foreach ($section['fields']['user'] as $field) {
                $total++;
                if (!empty($user->$field)) {
                    $filled++;
                }
            }
            
            // Count borrower fields
            foreach ($section['fields']['borrower'] as $field) {
                $total++;
                if ($user->borrower && !empty($user->borrower->$field)) {
                    $filled++;
                }
            }
            
            $section['filled'] = $filled;
            $section['total'] = $total;
        }

        return $sections;
    }
}