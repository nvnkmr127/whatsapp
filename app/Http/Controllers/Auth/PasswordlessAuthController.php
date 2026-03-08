<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\OTPService;
use App\Services\AuditService;
use App\Services\UniqueIdentityService;
use App\Services\OfferEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PasswordlessAuthController extends Controller
{
    public function __construct(
        private readonly OTPService $otpService,
        private readonly UniqueIdentityService $identity,
    ) {
    }

    /**
     * Request an OTP code.
     */
    public function requestOtp(\App\Http\Requests\Auth\OTPRequest $request)
    {
        $identifier = $request->identifier;
        $type = $request->type;

        $sent = $this->otpService->send($identifier, $type);

        if ($sent) {
            AuditService::log('Auth.OTP.Request', null, $identifier, $type . '_otp');
            return response()->json(['message' => 'OTP sent successfully.']);
        }

        return response()->json(['message' => 'Failed to send OTP or limit reached.'], 429);
    }

    /**
     * Verify OTP and login/signup.
     */
    public function verifyOtp(\App\Http\Requests\Auth\OTPVerifyRequest $request)
    {

        $identifier = $request->identifier;
        $type = $request->type;
        $code = $request->code ?? null;

        if (!$this->otpService->verify($identifier, $code)) {
            AuditService::log('Auth.Failure', null, $identifier, $type . '_otp', ['reason' => 'Invalid or expired OTP']);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid or expired OTP.'], 422);
            }
            return redirect()->back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        return DB::transaction(function () use ($identifier, $type, $request) {
            $user = null;
            $provider = ($type === 'email') ? 'email_otp' : 'phone_otp';

            // 1. Check if user is already logged in (Linking while authenticated)
            if (Auth::check()) {
                $user = Auth::user();

                // Check if this identity is already linked to ANOTHER user
                $existingIdentity = UserIdentity::where('provider', $provider)
                    ->where('provider_id', $identifier)
                    ->where('user_id', '!=', $user->id)
                    ->first();

                if ($existingIdentity) {
                    throw ValidationException::withMessages([
                        'identifier' => "This " . ($type === 'email' ? 'email' : 'phone number') . " is already linked to another account."
                    ]);
                }

                // Link identity
                UserIdentity::updateOrCreate(
                    ['provider' => $provider, 'provider_id' => $identifier],
                    ['user_id' => $user->id, 'last_login_at' => now()]
                );

                // Update primary field if missing
                if ($type === 'email') {
                    $user->forceFill([
                        'email' => $user->email ?: $identifier,
                        'email_verified_at' => now(),
                    ])->save();
                } elseif ($type === 'phone' && empty($user->phone)) {
                    $user->forceFill(['phone' => $identifier])->save();
                }

                return $user; // Return user for login/audit below
            }

            // 2. Find user by identity first (Normal Login)
            $identity = UserIdentity::where('provider', $provider)
                ->where('provider_id', $identifier)
                ->first();

            if ($identity) {
                $user = $identity->user;
                $identity->update(['last_login_at' => now()]);

                // Update primary field if missing
                if ($type === 'email') {
                    $user->forceFill([
                        'email' => $user->email ?: $identifier,
                        'email_verified_at' => now(),
                    ])->save();
                } elseif ($type === 'phone' && empty($user->phone)) {
                    $user->forceFill(['phone' => $identifier])->save();
                }
            } else {
                // 3. No identity, check if User exists by direct field (Account Linking)
                if ($type === 'email') {
                    $user = User::where('email', $identifier)->first();
                } else {
                    // Try exact match first for phone
                    $user = User::where('phone', $identifier)->first();

                    // If not found, try normalizing and searching again
                    if (!$user) {
                        try {
                            $normalizedPhone = \App\Helpers\PhoneNumberHelper::normalize($identifier);
                            $user = User::where('phone', $normalizedPhone)->first();
                        } catch (\Exception $e) {
                            // Invalid phone number format, continue
                        }
                    }

                    // If still not found, try a suffix match (last 10 digits)
                    if (!$user && strlen($identifier) >= 10) {
                        $suffix = substr($identifier, -10);
                        $user = User::where('phone', 'like', "%{$suffix}")->first();
                    }
                }

                if ($user) {
                    // 3. User exists by field, so LINK this identity
                    UserIdentity::updateOrCreate(
                        ['provider' => $provider, 'provider_id' => $identifier],
                        ['user_id' => $user->id, 'last_login_at' => now()]
                    );

                    // Login and return
                    Auth::login($user, true);
                    AuditService::log('Auth.Success', $user->id, $identifier, $provider);

                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Login successful.',
                            'redirect' => route('dashboard'),
                        ]);
                    }
                    return redirect()->intended(route('dashboard'));
                }

                if (!$user) {
                    // ── Identity uniqueness check (mirrors CreateNewUser) ──────
                    $ip = $request->ip() ?? '0.0.0.0';
                    $identityResult = $this->identity->check(
                        email: ($type === 'email') ? $identifier : 'unknown@unknown.local',
                        ip: $ip,
                    );

                    if (!$identityResult->passed) {
                        throw ValidationException::withMessages([
                            'identifier' => $identityResult->denial_reason,
                        ]);
                    }

                    // ── Create user ─────────────────────────────────────────────
                    $user = User::create([
                        'name' => ($type === 'email') ? explode('@', $identifier)[0] : $identifier,
                        'email' => ($type === 'email') ? $identifier : null,
                        'phone' => ($type === 'phone') ? $identifier : null,
                        'password' => null,
                        'email_verified_at' => now(),
                    ]);

                    // ── Create team ─────────────────────────────────────────────
                    $team = \App\Models\Team::forceCreate([
                        'user_id' => $user->id,
                        'name' => (($type === 'email') ? explode('@', $identifier)[0] : $identifier) . "'s Team",
                        'personal_team' => true,
                        'subscription_plan' => 'trial',
                        'subscription_status' => 'trial',
                        'trial_ends_at' => now()->addMonths(
                            (int) get_setting('offer_trial_months', 6)
                        ),
                    ]);

                    $user->ownedTeams()->save($team);
                    $user->forceFill(['current_team_id' => $team->id])->save();

                    // ── Welcome credit ──
                    if (app(OfferEligibilityService::class)->isEligible($team)) {
                        $credit = (float) get_setting('offer_initial_credit', 5.00);
                        if ($credit > 0) {
                            app(\App\Services\BillingService::class)->deposit($team, $credit, 'Welcome Gift (Launch Offer)');
                        }
                        app(OfferEligibilityService::class)->markClaimed($team);
                    }

                    // ── Record fingerprints ──
                    $this->identity->record(team: $team, user: $user, ip: $ip);

                    // ── Save UTM Parameters ──
                    $utms = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
                    $utmData = [];
                    foreach ($utms as $utm) {
                        if (request()->hasCookie($utm)) {
                            $utmData[$utm] = request()->cookie($utm);
                        }
                    }
                    if (!empty($utmData)) {
                        $user->forceFill($utmData)->save();
                    }

                    // ── Check Referral ──
                    if (request()->hasCookie('referral_code')) {
                        $code = request()->cookie('referral_code');
                        $affiliate = \App\Models\Affiliate::where('code', $code)->first();
                        
                        if ($affiliate) {
                            \App\Models\Referral::create([
                                'affiliate_id' => $affiliate->id,
                                'user_id' => $user->id,
                                'visitor_ip' => $ip,
                                'status' => 'converted',
                                'converted_at' => now(),
                            ]);
                        }
                    }

                    AuditService::log('Auth.Signup', $user->id, $identifier, $provider, ['team_id' => $team->id]);
                }

                // 4. Link identity for future login
                UserIdentity::updateOrCreate(
                    ['provider' => $provider, 'provider_id' => $identifier],
                    ['user_id' => $user->id, 'last_login_at' => now()]
                );
            }

            // Update last login
            if ($identity) {
                $identity->update(['last_login_at' => now()]);
            }

            Auth::login($user, true);

            AuditService::log('Auth.Success', $user->id, $identifier, $provider);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Login successful.',
                    'redirect' => route('dashboard'),
                ]);
            }

            return redirect()->intended(route('dashboard'));
        });
    }
}
