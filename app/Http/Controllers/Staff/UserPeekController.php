<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

/** The person side panel's content (users/_peek), fetched when a name is clicked anywhere in the staff pages. */
class UserPeekController extends Controller
{
    public function __invoke(User $user): View
    {
        return view('users._peek', ['user' => $user]);
    }
}
