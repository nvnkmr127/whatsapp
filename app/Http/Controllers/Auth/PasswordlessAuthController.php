<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\OTPService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PasswordlessAuthController extends Controller
{
    protected $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Request an OTP code.
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required', // Email or Phone
            'type' => 'required|in:email,phone',
        ]);

        $identifier = $request->identifier;
        $type = $request->type;

        $sent = $this->otpService->send($identifier, $type);

        if ($sent) {
            AuditService::log('Auth.OTP.Request', null, $identifier, $type . '_otp');
            return response()->json(['message' => 'OTP sent successfully.']);
        }

        return response()->json(['message' => 'Failed to send OTP.'], 500);
    }

    /**
     * Verify OTP and login/signup.
     */
    public function verifyOtp(Request $request)
    {
        // Check for auto-login in local environment
        $autoLogin = $request->has('auto_login') && $request->auto_login === 'true' && app()->environment('local');

        if ($autoLogin) {
            // Auto-login: skip OTP verification in local environment
            $request->validate([
                'identifier' => 'required',
                'type' => 'required|in:email,phone',
            ]);
        } else {
            // Normal flow: validate OTP code
            $request->validate([
                'identifier' => 'required',
                'type' => 'required|in:email,phone',
                'code' => 'required|string|size:6',
            ]);
        }

        $identifier = $request->identifier;
        $type = $request->type;
        $code = $request->code ?? null;

        // Verify OTP only if not auto-login
        if (!$autoLogin) {
            if (!$this->otpService->verify($identifier, $code)) {
                AuditService::log('Auth.Failure', null, $identifier, $type . '_otp', ['reason' => 'Invalid or expired OTP']);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Invalid or expired OTP.'], 422);
                }
                return redirect()->back()->withErrors(['code' => 'Invalid or expired code.']);
            }
        }

        return DB::transaction(function () use ($identifier, $type, $request) {
            $user = null;
            $provider = ($type === 'email') ? 'email_otp' : 'phone_otp';

            // Find user by identity first
            $identity = UserIdentity::where('provider', $provider)
                ->where('provider_id', $identifier)
                ->first();

            if ($identity) {
                $user = $identity->user;
            } else {
                // Find or create user by direct field
                if ($type === 'email') {
                    $user = User::where('email', $identifier)->first();
                } else {
                    $user = User::where('phone', $identifier)->first();
                }

                if (!$user) {
                    // Create new user if not found
                    $user = User::create([
                        'name' => explode('@', $identifier)[0] ?: $identifier, // Better default name
                        'email' => ($type === 'email') ? $identifier : null,
                        'phone' => ($type === 'phone') ? $identifier : null,
                        'password' => null, // Passwordless
                    ]);

                    // 2. Create Personal Team (Tenant Role)
                    $trialDays = config('whatsapp.trial_days', 14);
                    $teamName = (explode('@', $identifier)[0] ?: $identifier) . "'s Team";

                    $team = \App\Models\Team::forceCreate([
                        'user_id' => $user->id,
                        'name' => $teamName,
                        'personal_team' => true,
                        'subscription_plan' => 'trial',
                        'subscription_status' => 'trial',
                        'trial_ends_at' => now()->addDays($trialDays),
                    ]);

                    $user->ownedTeams()->save($team);
                    $user->forceFill(['current_team_id' => $team->id])->save();

                    AuditService::log('Auth.Signup', $user->id, $identifier, $provider, ['team_id' => $team->id]);
                }

                // Link identity
                UserIdentity::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $identifier,
                    'last_login_at' => now(),
                ]);
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
