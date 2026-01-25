<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite; // Requires laravel/socialite
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google Callback.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error("Google OAuth error: " . $e->getMessage());
            return redirect()->route('login')->with('error', 'Failed to authenticate with Google.');
        }

        return DB::transaction(function () use ($googleUser) {
            // 1. Find existing identity
            $identity = UserIdentity::where('provider', 'google')
                ->where('provider_id', $googleUser->getId())
                ->first();

            if ($identity) {
                $user = $identity->user;
                $identity->update(['last_login_at' => now()]);
            } else {
                // 2. No identity, check if User exists by email (Account Linking)
                $user = User::where('email', $googleUser->getEmail())->first();

                if (!$user) {
                    // 3. Create new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'password' => null, // Passwordless
                    ]);
                }

                // 4. Link Google Identity
                UserIdentity::create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'last_login_at' => now(),
                ]);
            }

            Auth::login($user, true);

            AuditService::log('Auth.Success', $user->id, $googleUser->getEmail(), 'google', [
                'google_id' => $googleUser->getId()
            ]);

            return redirect()->route('dashboard');
        });
    }
}
