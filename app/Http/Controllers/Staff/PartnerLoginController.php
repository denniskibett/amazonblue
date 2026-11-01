<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use App\Services\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Give a partner a portal login (owner, 1 Nov 2026): link an existing user (email or phone) or create one,
 * which gets the normal "set your password" email. The user gets the partner role; one user per partner.
 */
class PartnerLoginController extends Controller
{
    public function store(Request $request, Partner $partner): RedirectResponse
    {
        abort_unless($request->user()->can('edit partners'), 403);
        $fail = fn (string $msg) => back()->withInput()->withErrors(['login' => $msg], 'login');

        if ($partner->user_id && User::whereKey($partner->user_id)->exists()) {
            return $fail($partner->name . ' already has a login. Unlink it before linking another.');
        }
        $data = $request->validateWithBag('login', [
            'mode' => ['required', 'in:existing,new'],
            'lookup' => ['required_if:mode,existing', 'nullable', 'string', 'max:120'],
            'name' => ['required_if:mode,new', 'nullable', 'string', 'max:120'],
            'email' => ['required_if:mode,new', 'nullable', 'email', 'max:120'],
            'phone' => ['required_if:mode,new', 'nullable', 'string', 'max:30'],
        ], [], ['lookup' => 'email or phone']);

        if ($data['mode'] === 'existing') {
            $user = $this->find((string) $data['lookup']);
            if (! $user) {
                return $fail('No user with that email or phone. Choose "Create a login" instead.');
            }
        } else {
            $phone = PhoneHelper::normalizePhoneNumber($data['phone']);
            if (User::where('email', $data['email'])->exists() || ($phone && $this->byPhone($phone))) {
                return $fail('A user with that email or phone already exists. Choose "Existing user" and link them.');
            }
            $user = null;
        }

        if ($user) {
            if ($user->isStaff()) {
                return $fail($user->email . ' is a staff account; partners need their own login.');
            }
            $other = Partner::where('user_id', $user->id)->where('id', '!=', $partner->id)->first();
            if ($other) {
                return $fail($user->email . ' is already linked to partner ' . $other->name . '.');
            }
        }

        $created = false;
        DB::transaction(function () use (&$user, &$created, $data, $partner) {
            if (! $user) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => '+' . PhoneHelper::normalizePhoneNumber($data['phone']),
                    'password' => Hash::make(Str::random(40)),
                ]);
                $user->forceFill(['email_verified_at' => now()])->save(); // the set-password email proves the address
                $created = true;
            }
            Role::findOrCreate('partner', 'web');
            $user->assignRole('partner');
            $partner->forceFill(['user_id' => $user->id])->save();
        });

        AuditTrail::record('partners', $partner->id, 'update', ['user_id' => null],
            ['user_id' => $user->id, 'request' => 'partner_login_linked', 'created_user' => $created]);
        if ($created) {
            Password::broker()->sendResetLink(['email' => $user->email]);
        }

        return back()->with('alert', ['type' => 'success', 'title' => 'Login linked',
            'message' => $partner->name . ' can now sign in as ' . $user->email . ($created ? '. We emailed them a link to set their password.' : ' with their existing password.')]);
    }

    private function find(string $lookup): ?User
    {
        $lookup = trim($lookup);
        if (str_contains($lookup, '@')) {
            return User::where('email', $lookup)->first();
        }
        $phone = PhoneHelper::normalizePhoneNumber($lookup);

        return $phone ? $this->byPhone($phone) : null;
    }

    /** Phones are stored in mixed formats (07…, +254…); match on the last nine digits. */
    private function byPhone(string $digits): ?User
    {
        $tail = substr($digits, -9);
        if (strlen($tail) < 9) {
            return null;
        }

        return User::where('phone', 'like', '%' . $tail)->get()
            ->first(fn (User $u) => substr(preg_replace('/\D/', '', (string) $u->phone), -9) === $tail);
    }
}
