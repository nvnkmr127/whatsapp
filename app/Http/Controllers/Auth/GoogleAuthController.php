<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\AuditService;
use App\Services\OfferEligibilityService;
use App\Services\UniqueIdentityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Requires laravel/socialite
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly UniqueIdentityService $identity,
    ) {}

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
    public function callback(\Illuminate\Http\Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth error: '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Failed to authenticate with Google.');
        }

        return DB::transaction(function () use ($googleUser, $request) {
            // 1. Check if user is already logged in (Linking while authenticated)
            if (Auth::check()) {
                $user = Auth::user();

                // Check if this Google identity is already linked to ANOTHER user
                $existingIdentity = UserIdentity::where('provider', 'google')
                    ->where('provider_id', $googleUser->getId())
                    ->where('user_id', '!=', $user->id)
                    ->first();

                if ($existingIdentity) {
                    return redirect()->route('profile.show')->with('error', 'This Google account is already linked to another user.');
                }

                // Update user email if it's empty
                if (empty($user->email)) {
                    $user->forceFill(['email' => $googleUser->getEmail()])->save();
                }

                // Link identity if not already linked
                UserIdentity::updateOrCreate(
                    ['provider' => 'google', 'provider_id' => $googleUser->getId()],
                    ['user_id' => $user->id, 'last_login_at' => now()]
                );

                return redirect()->route('dashboard')->with('message', 'Google account linked successfully.');
            }

            // 2. Find existing identity by Google ID (Normal Login)
            $identity = UserIdentity::where('provider', 'google')
                ->where('provider_id', $googleUser->getId())
                ->first();

            if ($identity) {
                $user = $identity->user;
                $identity->update(['last_login_at' => now()]);

                // Update email if it's missing on the user record, and ensure verified
                if (empty($user->email) || empty($user->email_verified_at)) {
                    $user->forceFill([
                        'email' => $googleUser->getEmail(),
                        'email_verified_at' => now(),
                    ])->save();
                }
            } else {
                // 3. No identity, check if User exists by email (Account Linking)
                $user = User::where('email', $googleUser->getEmail())->first();

                if (! $user) {
                    $ip = $request->ip() ?? '0.0.0.0';
                    $identityResult = $this->identity->check(
                        email: $googleUser->getEmail(),
                        ip: $ip,
                    );

                    if (! $identityResult->passed) {
                        return redirect()->route('login')->withErrors(['oauth' => $identityResult->denial_reason]);
                    }

                    // 4. Create new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'password' => null, // Passwordless
                        'email_verified_at' => now(),
                    ]);

                    // ── Create team ─────────────────────────────────────────────
                    $team = \App\Models\Team::forceCreate([
                        'user_id' => $user->id,
                        'name' => ($user->name ?: 'Google')."'s Team",
                        'personal_team' => true,
                        'subscription_plan' => 'trial',
                        'subscription_status' => 'trial',
                        'trial_ends_at' => now()->addMonths(
                            (int) get_setting('offer_trial_months', 6)
                        ),
                    ]);

                    $user->ownedTeams()->save($team);
                    $user->forceFill(['current_team_id' => $team->id])->save();

                    // ── Welcome credit (gated through full 6-rule eligibility) ──
                    if (app(OfferEligibilityService::class)->isEligible($team)) {
                        $credit = (float) get_setting('offer_initial_credit', 5.00);
                        if ($credit > 0) {
                            app(\App\Services\BillingService::class)->deposit(
                                $team,
                                $credit,
                                'Welcome Gift (Launch Offer)'
                            );
                        }
                        app(OfferEligibilityService::class)->markClaimed($team);
                    }

                    // ── Record fingerprints + hit IP rate limiter ────────────────
                    $this->identity->record(team: $team, user: $user, ip: $ip);

                    AuditService::log('Auth.Signup', $user->id, $googleUser->getEmail(), 'google', ['team_id' => $team->id]);
                }

                // 5. Link Google Identity
                UserIdentity::create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_id' => $googleUser->getId(),
                    'last_login_at' => now(),
                ]);
            }

            Auth::login($user, true);

            AuditService::log('Auth.Success', $user->id, $googleUser->getEmail(), 'google', [
                'google_id' => $googleUser->getId(),
            ]);

            return redirect()->route('dashboard');
        });
    }
}
