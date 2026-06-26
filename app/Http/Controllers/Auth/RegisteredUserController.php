<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Borrower;
use App\Models\Broker;
use App\Models\Teller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,teller,borrower,broker'],
            // Add role-specific validation rules as needed
            'national_id' => ['required_if:role,borrower', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => '0'
        ]);

        // Create role-specific records
        switch ($request->role) {
            case 'borrower':
                Borrower::create([
                    'user_id' => $user->id,
                    'national_id' => $request->national_id,
                    'client_type' => $request->client_type ?? 0, // Default to 0 if not provided
                    'status' => '0',

                ]);
                break;
                
            case 'broker':
                Broker::create([
                    'user_id' => $user->id,
                    'interest_client' => 0, // Default values
                    'interest_broker' => 0,
                    'penalty_client' => 0,
                    'penalty_broker' => 0,
                    // Add other broker fields
                ]);
                break;
                
            case 'teller':
                Teller::create([
                    'user_id' => $user->id,
                    'branch' => 'main', // Default or from form
                    // Add other teller fields
                ]);
                break;
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}