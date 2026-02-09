<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;

class FacebookAuthController extends Controller
{
    /**
     * Redirect to Facebook.
     */
    public function redirect()
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Handle Facebook Callback.
     */
    public function callback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();
        } catch (\Exception $e) {
            Log::error("Facebook OAuth error: " . $e->getMessage());
            return redirect()->route('login')->with('error', 'Failed to authenticate with Facebook.');
        }

        return DB::transaction(function () use ($facebookUser) {
            // 1. Find existing identity
            $identity = UserIdentity::where('provider', 'facebook')
                ->where('provider_id', $facebookUser->getId())
                ->first();

            if ($identity) {
                $user = $identity->user;
                $identity->update(['last_login_at' => now()]);
            } else {
                // 2. No identity, check if User exists by email (Account Linking)
                // Note: Facebook email might be null if not authorized or not verified
                $email = $facebookUser->getEmail();
                $user = $email ? User::where('email', $email)->first() : null;

                if (!$user) {
                    // 3. Create new user
                    $user = User::create([
                        'name' => $facebookUser->getName() ?: 'Facebook User',
                        'email' => $email,
                        'password' => null, // Passwordless
                    ]);
                }

                // 4. Link Facebook Identity
                UserIdentity::create([
                    'user_id' => $user->id,
                    'provider' => 'facebook',
                    'provider_id' => $facebookUser->getId(),
                    'last_login_at' => now(),
                ]);
            }

            Auth::login($user, true);

            AuditService::log('Auth.Success', $user->id, $user->email ?? $facebookUser->getId(), 'facebook', [
                'facebook_id' => $facebookUser->getId()
            ]);

            return redirect()->route('dashboard');
        });
    }
}
