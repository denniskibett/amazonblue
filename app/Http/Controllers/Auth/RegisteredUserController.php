<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Borrower;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Public sign-up. Everyone who registers here is an individual borrower;
 * staff, brokers and partners are created by an admin from Users.
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => preg_replace('/[\s-]/', '', (string) $request->input('phone'))]);
        $request->validate([
            'account_type' => ['required', Rule::in(['individual'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^(?:\+?254|0)[17]\d{8}$/', 'unique:'.User::class],
            'password' => ['required', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'account_type.in' => 'Business accounts are coming soon. Register as an individual for now.',
            'phone.regex' => 'Enter a Kenyan mobile number like 0712 345 678.',
            'phone.unique' => 'This phone number is already registered.',
            'terms.accepted' => 'Please accept the Terms of service and Privacy policy.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'borrower', // legacy column, kept in step with the Spatie role
                'status' => 0,
            ]);

            // model_has_roles pivot: this is what permissions and dashboards check
            $user->assignRole('borrower');

            Borrower::create([
                'user_id' => $user->id,
                'client_type' => 'individual',
                'status' => 0,
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('kyc.personal');
    }
}
