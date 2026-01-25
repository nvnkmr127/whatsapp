<?php

namespace App\Http\Controllers;

use App\Models\UserIdentity;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserIdentityController extends Controller
{
    /**
     * Remove a linked identity.
     */
    public function destroy(UserIdentity $identity)
    {
        // 1. Ensure ownership
        if ($identity->user_id !== Auth::id()) {
            abort(403);
        }

        // 2. Security Check: Cannot unlink last remaining identity
        if (!$identity->isSafeToUnlink()) {
            return redirect()->back()->with('flash.banner', 'You cannot unlink your last remaining login method. Please add another first.')->with('flash.bannerStyle', 'danger');
        }

        $provider = $identity->provider;

        // 3. Log the action
        AuditService::log('Identity.Unlink', Auth::id(), Auth::user()->email, 'security', [
            'provider' => $provider,
            'provider_id' => $identity->provider_id,
        ]);

        // 4. Delete
        $identity->delete();

        return redirect()->back()->with('flash.banner', "Successfully unlinked your {$provider} account.");
    }
}
