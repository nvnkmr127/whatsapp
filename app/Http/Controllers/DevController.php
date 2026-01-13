<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevController extends Controller
{
    public function loginAs($email)
    {
        // Only allow in local/dev environments for safety
        if (!app()->environment('local', 'testing')) {
            abort(403, 'Auto-login is only allowed in development.');
        }

        $user = User::where('email', $email)->firstOrFail();
        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}
