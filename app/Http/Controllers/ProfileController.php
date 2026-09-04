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
        $lockedFields = $this->getLockedFields($user);
        $isProfileLocked = !empty($lockedFields);
        $loanStatusMessage = $user->getLoanStatusMessage();
        
        return view('profile.show', compact(
            'user', 
            'completionPercentage', 
            'missingFields',
            'isProfileLocked',
            'loanStatusMessage',
            'lockedFields'
        ));
    }

    /**
     * Show the form for editing the profile.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['broker', 'borrower', 'teller']);
        
        // Get locked fields
        $lockedFields = $this->getLockedFields($user);
        $isProfileLocked = !empty($lockedFields);
        
        // Get loan status message
        $loanStatusMessage = null;
        if ($isProfileLocked) {
            $hasEverTakenLoan = $user->loans()->exists();
            if ($hasEverTakenLoan) {
                $loanStatusMessage = "You have previously taken a loan. Your KYC information is locked for security and compliance. Only non-critical fields can be updated.";
            }
        }
        
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

    /**
     * Update the user's profile.
     * Only updates fields that are NOT locked
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        // Get locked fields
        $lockedFields = $this->getLockedFields($user);
        
        // Build validation rules - only for non-locked fields
        $rules = [];
        
        // ============ UNLOCKED FIELDS (Can always be edited) ============
        // Personal (non-KYC)
        $rules['marital_status'] = 'sometimes|in:single,married,divorced,widowed';
        $rules['religion'] = 'nullable|string|max:100';
        $rules['education'] = 'nullable|string|max:100';
        $rules['disability'] = 'boolean';
        $rules['profile_photo'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        
        // Employment (for borrowers)
        if ($user->role === 'borrower') {
            $rules['income_type'] = 'sometimes|string|max:50';
            $rules['gross_salary'] = 'nullable|numeric|min:0';
            $rules['net_salary'] = 'nullable|numeric|min:0';
            $rules['job_title'] = 'nullable|string|max:255';
            $rules['workplace'] = 'nullable|string|max:255';
            $rules['employer_name'] = 'nullable|string|max:255';
            $rules['employer_email'] = 'nullable|email';
            $rules['employer_title'] = 'nullable|string|max:255';
            $rules['department'] = 'nullable|string|max:255';
        }
        
        // Broker rates (if broker)
        if ($user->role === 'broker') {
            $rules['interest_client'] = 'sometimes|numeric|min:0';
            $rules['interest_broker'] = 'sometimes|numeric|min:0';
            $rules['penalty_client'] = 'sometimes|numeric|min:0';
            $rules['penalty_broker'] = 'sometimes|numeric|min:0';
        }
        
        // Teller branch (if teller)
        if ($user->role === 'teller') {
            $rules['branch'] = 'sometimes|string|max:255';
        }
        
        // ============ LOCKED FIELDS (Removed from validation) ============
        // Remove all locked fields from request data
        foreach ($lockedFields as $field) {
            $request->request->remove($field);
        }
        
        $validatedData = $request->validate($rules);
        
        // Handle file uploads
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
            $validatedData['profile_photo_path'] = $profilePhotoPath;
        }
        
        // Convert disability to boolean
        if (isset($validatedData['disability'])) {
            $validatedData['disability'] = $request->has('disability');
        }
        
        // Update basic user info (only unlocked fields)
        $userData = [];
        $unlockedUserFields = ['marital_status', 'religion', 'education', 'disability'];
        foreach ($unlockedUserFields as $field) {
            if (isset($validatedData[$field])) {
                $userData[$field] = $validatedData[$field];
            }
        }
        
        if (!empty($userData)) {
            $user->fill($userData);
            $user->save();
        }
        
        // Update role-specific info
        if ($user->role === 'broker' && $user->broker) {
            $brokerData = [];
            $brokerFields = ['interest_client', 'interest_broker', 'penalty_client', 'penalty_broker'];
            foreach ($brokerFields as $field) {
                if (isset($validatedData[$field])) {
                    $brokerData[$field] = $validatedData[$field];
                }
            }
            if (!empty($brokerData)) {
                $user->broker->update($brokerData);
            }
        } elseif ($user->role === 'borrower') {
            $borrowerData = [];
            $borrowerFields = ['income_type', 'gross_salary', 'net_salary', 'job_title', 'workplace', 
                               'employer_name', 'employer_email', 'employer_title', 'department'];
            foreach ($borrowerFields as $field) {
                if (isset($validatedData[$field])) {
                    $borrowerData[$field] = $validatedData[$field];
                }
            }
            
            if (!empty($borrowerData)) {
                if ($user->borrower) {
                    $user->borrower->update($borrowerData);
                } else {
                    $user->borrower()->create($borrowerData);
                }
            }
        } elseif ($user->role === 'teller' && isset($validatedData['branch'])) {
            if ($user->teller) {
                $user->teller->update(['branch' => $validatedData['branch']]);
            } else {
                $user->teller()->create(['branch' => $validatedData['branch']]);
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

    /**
     * Update the user's password.
     */
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

    /**
     * Update password (legacy method).
     */
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

    /**
     * Delete the user's account.
     */
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

    /**
     * Save the user's digital signature.
     */
    public function saveSignature(Request $request, SignatureService $signatureService): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has ever taken a loan
        $hasEverTakenLoan = $user->loans()->exists();
        if ($hasEverTakenLoan) {
            return response()->json([
                'success' => false,
                'message' => 'Signature cannot be changed once you have taken a loan. Please contact support for assistance.',
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

    /**
     * Delete the user's digital signature.
     */
    public function deleteSignature(Request $request, SignatureService $signatureService): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has ever taken a loan
        $hasEverTakenLoan = $user->loans()->exists();
        if ($hasEverTakenLoan) {
            return response()->json([
                'success' => false,
                'message' => 'Signature cannot be changed once you have taken a loan. Please contact support for assistance.',
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

    /**
     * Auto-save the profile (for AJAX autosave functionality).
     */
    public function autoSave(Request $request)
    {
        $user = $request->user();
        
        // Get locked fields
        $lockedFields = $this->getLockedFields($user);
        
        $rules = [];
        
        // Only non-locked fields can be auto-saved
        $unlockedFields = ['marital_status', 'religion', 'education', 'disability'];
        foreach ($unlockedFields as $field) {
            if (!in_array($field, $lockedFields)) {
                $rules[$field] = 'sometimes|nullable|string|max:255';
            }
        }
        
        // Remove locked fields from request
        foreach ($lockedFields as $field) {
            $request->request->remove($field);
        }
        
        $validatedData = $request->validate($rules);
        
        $user->fill($validatedData);
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Auto-saved successfully',
            'completion_percentage' => $user->getBiodataCompletionPercentage()
        ]);
    }

    /**
     * Get locked fields based on loan history.
     * 
     * Once a user has ever taken a loan, KYC fields are permanently locked.
     */
    private function getLockedFields($user): array
    {
        // Check if user has EVER taken a loan
        $hasEverTakenLoan = $user->loans()->exists();

        if (!$hasEverTakenLoan) {
            return [];
        }

        // ALL KYC-critical fields are permanently locked once a loan has been taken
        return [
            // ============ PERMANENTLY LOCKED FIELDS ============
            
            // Personal Information (KYC critical)
            'name',
            'email',
            'phone',
            'gender',
            'dob',
            'nationality',
            
            // Identification (KYC critical)
            'id_type',
            'id_number',
            'id_front_path',
            'id_back_path',
            
            // Next of Kin (emergency contact - locked for consistency)
            'kin_name',
            'kin_email',
            'kin_phone',
            'kin_occupation',
            'kin_relation',
            'kin_id_type',
            'kin_id_number',
            
            // Borrower account (system-managed)
            'client_type',
            'status',
            
            // Broker certificate (legal requirement)
            'cert_no',
            
            // Signature (legal requirement)
            'signature',
            
            // Profile photo (identity verification)
            'profile_photo_path',
        ];
    }

    /**
     * Get section completion counts for the profile edit page.
     */
    private function getSectionCompletionCounts($user)
    {
        // Get locked fields
        $lockedFields = $this->getLockedFields($user);
        
        $sections = [
            'basic' => [
                'name' => 'Basic Information',
                'fields' => ['name', 'email', 'phone', 'gender', 'dob', 'nationality', 'marital_status'],
                'filled' => 0,
                'total' => 7
            ],
            'identification' => [
                'name' => 'Identification',
                'fields' => ['id_type', 'id_number', 'id_front_path', 'id_back_path'],
                'filled' => 0,
                'total' => 4
            ],
            'next-of-kin' => [
                'name' => 'Next of Kin',
                'fields' => ['kin_name', 'kin_email', 'kin_phone', 'kin_occupation', 'kin_relation', 'kin_id_type', 'kin_id_number'],
                'filled' => 0,
                'total' => 7
            ],
            'additional' => [
                'name' => 'Additional Information',
                'fields' => ['religion', 'education'],
                'filled' => 0,
                'total' => 2
            ],
            'employment' => [
                'name' => 'Employment Information',
                'fields' => ['income_type', 'gross_salary', 'net_salary', 'job_title', 'workplace', 'employer_name', 'employer_email', 'employer_title', 'department'],
                'filled' => 0,
                'total' => 9
            ],
            'borrower-info' => [
                'name' => 'Borrower Details',
                'fields' => ['client_type', 'status'],
                'filled' => 0,
                'total' => 2
            ]
        ];

        foreach ($sections as $key => &$section) {
            $filled = 0;
            $total = $section['total'];
            
            foreach ($section['fields'] as $field) {
                // Check if field exists on user
                if (property_exists($user, $field) && !empty($user->$field)) {
                    $filled++;
                } 
                // Check if field exists on borrower
                elseif ($user->borrower && property_exists($user->borrower, $field) && !empty($user->borrower->$field)) {
                    $filled++;
                }
            }
            
            $section['filled'] = $filled;
            $section['total'] = $total;
            $section['locked_fields'] = array_intersect($section['fields'], $lockedFields);
        }

        return $sections;
    }
}