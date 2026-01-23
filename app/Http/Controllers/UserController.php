<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Borrower; 
use App\Models\Broker;
use App\Models\LoanType;
use App\Models\Loans;
use App\Models\Repayments;
use App\Models\Penalties;
use App\Models\Disbursement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SystemHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        // Fetch all users with their loans and repayments for stats
        $users = User::with(['loans', 'repayments'])
            ->orderBy('status', 'asc')
            ->get()
            ->map(function ($user) {
                // Calculate completion percentage for each user
                $user->completion_percentage = $user->getBiodataCompletionPercentage();

                // Clean phone numbers for display
                if ($user->phone) {
                    $user->phone_display = PhoneHelper::cleanPhoneNumber($user->phone);
                }
                
                return $user;
            });
        $brokers = Broker::with('user')->get();
        return view('users.index', compact('users', 'brokers'));
    }

    public function show(User $user)  
    {
        // No need to find the user again, Laravel already injected it
        $user->load([
            'loans.loanType', 
            'loans.repayments',
            'disbursements',
            'repayments',
            'borrower' 
        ]);

        // Calculate completion data
        $completionPercentage = $user->getBiodataCompletionPercentage();
        $missingFields = $user->getMissingBiodataFields();
        
        // Calculate loan statistics
        $totalLoans = $user->loans->sum('amount');
        $totalDisbursed = $user->disbursements->sum('amount');
        $totalRepaid = $user->repayments->sum('amount');
        $repaymentRate = $totalLoans > 0 ? ($totalRepaid / $totalLoans) * 100 : 0;
        
        // Calculate active loans
        $activeLoans = $user->loans->where('status', 'active')->count();
        $completedLoans = $user->loans->where('status', 'completed')->count();
        
        // Calculate average loan amount
        $averageLoan = $user->loans->count() > 0 ? $user->loans->avg('amount') : 0;
        
        $loanTypes = LoanType::all();
        $guarantors = User::where('role', 'borrower')->get();
        $loanOfficers = User::whereIn('role', ['admin', 'teller'])->get();
        $signatureUser = null;
        
        return view('users.show', compact(
            'user',
            'completionPercentage',
            'missingFields',
            'totalLoans',
            'totalDisbursed',
            'totalRepaid',
            'repaymentRate',
            'activeLoans',
            'completedLoans',
            'averageLoan',
            'loanTypes',
            'guarantors',
            'loanOfficers',
            'signatureUser'
        ));
    }
        

    public function create()
    {
        $brokers = Broker::all();
        return view('users.create', compact('brokers'));
    }
    
    public function store(Request $request)
    {
        // Validate the request data with role-specific rules
        $validatedData = $this->validateUserData($request, null);
    
        // Handle file uploads
        $filePaths = $this->handleFileUploads($request);
    
        // Create the user
        $userData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone' => $validatedData['phone'],
            'role' => $validatedData['role'],
            'status' => $request->input('status', 0),
        ];
        
        // Add optional personal fields
        $optionalFields = [
            'gender', 'dob', 'nationality', 'marital_status', 
            'religion', 'disability', 'education', 'kin_name', 'kin_email',
            'kin_phone', 'kin_occupation', 'kin_relation', 'kin_id_type',
            'kin_id_number', 'id_type', 'id_number'
        ];
        
        foreach ($optionalFields as $field) {
            if ($request->has($field) && $request->input($field) !== null) {
                $userData[$field] = $request->input($field);
            }
        }
        
        // Add file paths
        $userData = array_merge($userData, $filePaths);
        
        $user = User::create($userData);
    
        // Handle role-specific data
        switch ($validatedData['role']) {
            case 'broker':
                Broker::create([
                    'user_id' => $user->id,
                    'penalty_client' => $validatedData['penalty_client'],
                    'penalty_broker' => $validatedData['penalty_broker'],
                    'interest_client' => $validatedData['interest_client'],
                    'interest_broker' => $validatedData['interest_broker'],
                    'cert_no' => $validatedData['cert_no'],
                ]);
                break;
    
            case 'borrower':
                $borrowerData = [
                    'user_id' => $user->id,
                    'client_type' => $validatedData['client_type'],
                    'status' => $request->input('borrower_status', 1),
                ];
                
                // Add optional borrower fields
                $borrowerOptionalFields = [
                    'income_type', 'gross_salary', 'net_salary', 'job_title',
                    'workplace', 'employer_name', 'employer_email', 'employer_title', 'department'
                ];
                
                foreach ($borrowerOptionalFields as $field) {
                    if ($request->has($field) && $request->input($field) !== null) {
                        $borrowerData[$field] = $request->input($field);
                    }
                }
                
                Borrower::create($borrowerData);
                break;
        }
    
        // Return JSON response for AJAX
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => $user->load(['borrower', 'broker'])
            ]);
        }
        
        // Redirect with success message for regular form submission
        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }
    

    public function edit(User $user)
    {
        $brokers = Broker::all();
        $broker = $user->broker;
        $borrower = $user->borrower;
    
        return view('users.edit', compact('user', 'brokers', 'broker', 'borrower'));
    }

    public function update(Request $request, User $user)
    {
        // Debug logging
        Log::info('=== USER UPDATE DEBUG START ===');
        Log::info('User ID: ' . $user->id);
        Log::info('User current data:', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'nationality' => $user->nationality,
            'gender' => $user->gender,
            'dob' => $user->dob,
            'marital_status' => $user->marital_status,
            'religion' => $user->religion,
            'education' => $user->education,
        ]);
        
        if ($user->borrower) {
            Log::info('Borrower current data:', [
                'client_type' => $user->borrower->client_type,
                'income_type' => $user->borrower->income_type,
                'job_title' => $user->borrower->job_title,
                'employer_name' => $user->borrower->employer_name,
            ]);
        }
        
        Log::info('Request ALL data:', $request->all());
        Log::info('Request FILES:', $request->allFiles());
        
        try {
            // Test database connection
            DB::connection()->getPdo();
            Log::info('Database connection OK');
            
            // Validate the request data
            Log::info('Starting validation...');
            $validatedData = $this->validateUserData($request, $user);
            Log::info('Validation passed. Validated data keys:', array_keys($validatedData));
            
            // Handle file uploads
            Log::info('Handling file uploads...');
            $filePaths = $this->handleFileUploads($request);
            Log::info('File paths generated:', $filePaths);
            
            // Update user
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'role' => $validatedData['role'],
                'status' => $request->input('status', $user->status),
            ];
            
            Log::info('User basic data to update:', $userData);
            
            // Update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($validatedData['password']);
                Log::info('Password updated');
            }
            
            // Add optional personal fields
            $optionalFields = [
                'gender', 'dob', 'nationality', 'marital_status', 
                'religion', 'disability', 'education', 'kin_name', 'kin_email',
                'kin_phone', 'kin_occupation', 'kin_relation', 'kin_id_type',
                'kin_id_number', 'id_type', 'id_number'
            ];
            
            foreach ($optionalFields as $field) {
                if ($request->has($field)) {
                    $userData[$field] = $request->input($field);
                    Log::info("Field {$field} value: " . $request->input($field));
                }
            }
            
            // Add file paths
            $userData = array_merge($userData, $filePaths);
            
            Log::info('Final user data with files:', $userData);
            
            // Update the user
            $updateResult = $user->update($userData);
            Log::info('User update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));
            Log::info('User updated successfully. New data:', [
                'name' => $user->fresh()->name,
                'email' => $user->fresh()->email,
                'nationality' => $user->fresh()->nationality,
            ]);
        
            // Update related model
            if ($user->role === 'borrower') {
                Log::info('Updating borrower data...');
                $borrowerData = [
                    'client_type' => $validatedData['client_type'] ?? null,
                    'status' => $request->input('borrower_status', $user->borrower->status ?? 1),
                ];
                
                // Add optional borrower fields
                $borrowerOptionalFields = [
                    'income_type', 'gross_salary', 'net_salary', 'job_title',
                    'workplace', 'employer_name', 'employer_email', 'employer_title', 'department'
                ];
                
                foreach ($borrowerOptionalFields as $field) {
                    if ($request->has($field)) {
                        $borrowerData[$field] = $request->input($field);
                        Log::info("Borrower field {$field} value: " . $request->input($field));
                    }
                }
                
                Log::info('Borrower data to update:', $borrowerData);
                
                if ($user->borrower) {
                    $borrowerUpdateResult = $user->borrower->update($borrowerData);
                    Log::info('Borrower update result: ' . ($borrowerUpdateResult ? 'SUCCESS' : 'FAILED'));
                } else {
                    $borrowerData['user_id'] = $user->id;
                    Borrower::create($borrowerData);
                    Log::info('Borrower created');
                }
            } elseif ($user->role === 'broker') {
                $brokerData = [
                    'penalty_client' => $validatedData['penalty_client'] ?? null,
                    'penalty_broker' => $validatedData['penalty_broker'] ?? null,
                    'interest_client' => $validatedData['interest_client'] ?? null,
                    'interest_broker' => $validatedData['interest_broker'] ?? null,
                    'cert_no' => $validatedData['cert_no'] ?? null,
                ];
                
                if ($user->broker) {
                    $user->broker->update($brokerData);
                } else {
                    $brokerData['user_id'] = $user->id;
                    Broker::create($brokerData);
                }
            }
            
            // Return JSON response for AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully.',
                    'user' => $user->load(['borrower', 'broker'])
                ]);
            }
        
            return redirect()->route('users.show', $user->id)->with('success', 'User updated successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            Log::error('Validation failed fields:', $request->all());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Exception $e) {
            Log::error('Update error: ' . $e->getMessage());
            Log::error('Stack trace:', ['exception' => $e]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }
    
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        // Return JSON response for AJAX
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        }

        return redirect()->route('users.index');
    }
    
    private function validateUserData(Request $request, $user = null)
    {
        $userId = $user ? $user->id : null;
        $borrowerId = $user && $user->borrower ? $user->borrower->id : null;
        $brokerId = $user && $user->broker ? $user->broker->id : null;
        
        Log::info('Validating user data for user ID: ' . $userId);
        Log::info('Request method: ' . $request->method());
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $userId,
            'phone' => 'required|unique:users,phone,' . $userId,
            'role' => 'required|in:admin,broker,borrower,teller',
            'status' => 'sometimes|in:0,1',
            'password' => $userId ? 'nullable|min:6' : 'required|min:6',
            
            // Broker-specific fields
            'cert_no' => 'required_if:role,broker|unique:brokers,cert_no,' . $brokerId . '|nullable',
            'penalty_client' => 'required_if:role,broker|numeric|nullable',
            'penalty_broker' => 'required_if:role,broker|numeric|nullable',
            'interest_client' => 'required_if:role,broker|numeric|nullable',
            'interest_broker' => 'required_if:role,broker|numeric|nullable',
            
            // Borrower-specific fields
            'client_type' => 'nullable|in:individual,non_individual',
            'borrower_status' => 'sometimes|in:0,1',
            
            // User personal fields
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'nationality' => 'nullable|string|max:255',
            'marital_status' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'disability' => 'nullable|boolean',
            'education' => 'nullable|string|max:255',
            
            // Next of kin fields
            'kin_name' => 'nullable|string|max:255',
            'kin_email' => 'nullable|email|max:255',
            'kin_phone' => 'nullable|string|max:50',
            'kin_occupation' => 'nullable|string|max:255',
            'kin_relation' => 'nullable|string|max:100',
            'kin_id_type' => 'nullable|string|max:100',
            'kin_id_number' => 'nullable|string|max:100',
            
            // Identification fields
            'id_type' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            
            // Borrower employment fields
            'income_type' => 'nullable|string|max:100',
            'gross_salary' => 'nullable|numeric|min:0',
            'net_salary' => 'nullable|numeric|min:0',
            'job_title' => 'nullable|string|max:255',
            'workplace' => 'nullable|string|max:255',
            'employer_name' => 'nullable|string|max:255',
            'employer_email' => 'nullable|email|max:255',
            'employer_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            
            // File uploads
            'avatar' => 'nullable|image|max:2048',
            'id_front_path' => 'nullable|image|max:2048',
            'id_back_path' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:1024',
        ];
        
        Log::info('Validation rules being applied');
        
        return $request->validate($rules);
    }
        

    private function handleFileUploads(Request $request)
    {
        $filePaths = [];
        
        // Add avatar (avatar photo) handling
        if ($request->hasFile('avatar')) {
            $filePaths['avatar'] = $request->file('avatar')->store('avatars', 'public');
            Log::info('Avatar file uploaded to: ' . $filePaths['avatar']);
        }
        
        if ($request->hasFile('id_front_path')) {
            $filePaths['id_front_path'] = $request->file('id_front_path')->store('id_cards', 'public');
            Log::info('ID front file uploaded to: ' . $filePaths['id_front_path']);
        }
        
        if ($request->hasFile('id_back_path')) {
            $filePaths['id_back_path'] = $request->file('id_back_path')->store('id_cards', 'public');
            Log::info('ID back file uploaded to: ' . $filePaths['id_back_path']);
        }
        
        if ($request->hasFile('signature')) {
            $filePaths['signature'] = $request->file('signature')->store('signatures', 'public');
            Log::info('Signature file uploaded to: ' . $filePaths['signature']);
        }
        
        return $filePaths;
    }
        
    /**
     * Get user data for AJAX requests
     */
    public function getUserData(User $user)
    {
        $userData = $user->toArray();
        
        if ($user->borrower) {
            $userData = array_merge($userData, $user->borrower->toArray());
        }
        
        if ($user->broker) {
            $userData = array_merge($userData, $user->broker->toArray());
        }
        
        return response()->json($userData);
    }
}