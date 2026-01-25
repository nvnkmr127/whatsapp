<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function enter(User $user)
    {
        $admin = Auth::user();

        // 1. Security Check: Only Super Admins can impersonate
        if (!$admin->isSuperAdmin()) {
            abort(403, 'Unauthorized impersonation attempt.');
        }

        // 2. Log the action
        AuditService::log('Admin.Impersonation.Enter', $admin->id, $user->email, 'impersonation', [
            'target_user_id' => $user->id,
            'target_user_name' => $user->name,
        ]);

        // 3. Store the original admin ID in session
        Session::put('impersonated_by', $admin->id);

        // 4. Log in as the target user
        Auth::login($user);

        return redirect()->route('dashboard')->with('status', "Now impersonating {$user->name}");
    }

    /**
     * Exit impersonation and return to admin.
     */
    public function exit()
    {
        if (!Session::has('impersonated_by')) {
            return redirect()->route('dashboard');
        }

        $adminId = Session::get('impersonated_by');
        $admin = User::findOrFail($adminId);
        $currentUser = Auth::user();

        // 1. Log the exit
        AuditService::log('Admin.Impersonation.Exit', $admin->id, $currentUser->email, 'impersonation', [
            'impersonated_user_id' => $currentUser->id,
        ]);

        // 2. Clear impersonation session
        Session::forget('impersonated_by');

        // 3. Log back in as admin
        Auth::login($admin);

        return redirect()->route('admin.dashboard')->with('status', "Impersonation ended.");
    }
}
