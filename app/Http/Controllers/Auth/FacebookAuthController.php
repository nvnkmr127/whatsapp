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
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class FacebookAuthController extends Controller
{
    public function __construct(
        private readonly UniqueIdentityService $identity,
    ) {}

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
    public function callback(\Illuminate\Http\Request $request)
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();
        } catch (\Exception $e) {
            Log::error('Facebook OAuth error: '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Failed to authenticate with Facebook.');
        }

        return DB::transaction(function () use ($facebookUser, $request) {
            // 1. Check if user is already logged in (Linking while authenticated)
            if (Auth::check()) {
                $user = Auth::user();

                // Check if this Facebook identity is already linked to ANOTHER user
                $existingIdentity = UserIdentity::where('provider', 'facebook')
                    ->where('provider_id', $facebookUser->getId())
                    ->where('user_id', '!=', $user->id)
                    ->first();

                if ($existingIdentity) {
                    return redirect()->route('profile.show')->with('error', 'This Facebook account is already linked to another user.');
                }

                // Update user email if it's empty
                $email = $facebookUser->getEmail();
                if (empty($user->email) && $email) {
                    $user->forceFill(['email' => $email])->save();
                }

                // Link identity if not already linked
                UserIdentity::updateOrCreate(
                    ['provider' => 'facebook', 'provider_id' => $facebookUser->getId()],
                    ['user_id' => $user->id, 'last_login_at' => now()]
                );

                return redirect()->route('dashboard')->with('message', 'Facebook account linked successfully.');
            }

            // 2. Find existing identity by Facebook ID (Normal Login)
            $identity = UserIdentity::where('provider', 'facebook')
                ->where('provider_id', $facebookUser->getId())
                ->first();

            if ($identity) {
                $user = $identity->user;
                $identity->update(['last_login_at' => now()]);

                // Update email if it's missing or unverified on the user record
                $email = $facebookUser->getEmail();
                if ((empty($user->email) && $email) || empty($user->email_verified_at)) {
                    $user->forceFill([
                        'email' => $user->email ?: $email,
                        'email_verified_at' => now(),
                    ])->save();
                }
            } else {
                // 3. No identity, check if User exists by email (Account Linking)
                // Note: Facebook email might be null if not authorized or not verified
                $email = $facebookUser->getEmail();
                $user = $email ? User::where('email', $email)->first() : null;

                if (! $user) {
                    $ip = $request->ip() ?? '0.0.0.0';
                    $identityResult = $this->identity->check(
                        email: $email ?? 'unknown@unknown.local',
                        ip: $ip,
                    );

                    if (! $identityResult->passed) {
                        return redirect()->route('login')->withErrors(['oauth' => $identityResult->denial_reason]);
                    }

                    // 4. Create new user
                    $user = User::create([
                        'name' => $facebookUser->getName() ?: 'Facebook User',
                        'email' => $email,
                        'password' => null, // Passwordless
                        'email_verified_at' => now(),
                    ]);

                    // ── Create team ─────────────────────────────────────────────
                    $team = \App\Models\Team::forceCreate([
                        'user_id' => $user->id,
                        'name' => ($user->name ?: 'Facebook')."'s Team",
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

                    AuditService::log('Auth.Signup', $user->id, $user->email ?? $facebookUser->getId(), 'facebook', ['team_id' => $team->id]);
                }

                // 5. Link Facebook Identity
                UserIdentity::create([
                    'user_id' => $user->id,
                    'provider' => 'facebook',
                    'provider_id' => $facebookUser->getId(),
                    'last_login_at' => now(),
                ]);
            }

            Auth::login($user, true);

            AuditService::log('Auth.Success', $user->id, $user->email ?? $facebookUser->getId(), 'facebook', [
                'facebook_id' => $facebookUser->getId(),
            ]);

            return redirect()->route('dashboard');
        });
    }
}
