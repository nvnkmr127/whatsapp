<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (app()->environment('local')) {
            Log::debug('[DEBUG] [MOBILE_AUTH] Login attempt', ['email' => $request->email]);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password ?? '')) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        if (app()->environment('local')) {
            Log::debug('[DEBUG] [MOBILE_AUTH] Login successful', ['user_id' => $user->id]);
        }

        $teams = $user->allTeams()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values();

        $currentTeam = $user->currentTeam;
        $members = $currentTeam ? $currentTeam->allUsers()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->membership?->role ?? 'agent',
        ])->values() : [];

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->membership?->role ?? 'admin', // Global or team role
            ],
            'teams' => $teams,
            'members' => $members,
        ]);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if (app()->environment('local')) {
            Log::debug('[DEBUG] [MOBILE_AUTH] /me endpoint reached', [
                'user_id' => $user->id,
                'auth_via_token' => $request->user()->currentAccessToken() !== null,
                'token_abilities' => $request->user()->currentAccessToken()?->abilities,
                'is_stateful' => $request->attributes->has('sanctum'),
            ]);
        }

        $teams = $user->allTeams()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values();

        $currentTeam = $user->currentTeam;
        $members = $currentTeam ? $currentTeam->allUsers()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->membership?->role ?? 'agent',
        ])->values() : [];

        $numbers = $currentTeam ? [[
            'id' => $currentTeam->whatsapp_phone_number_id,
            'display_number' => $currentTeam->whatsapp_phone_display ?? 'Primary Number',
            'verified_name' => $currentTeam->whatsapp_verified_name ?? $currentTeam->name,
        ]] : [];

        $businessProfile = null;
        if ($currentTeam && $currentTeam->whatsapp_phone_number_id && $currentTeam->whatsapp_access_token) {
            $cacheKey = 'business_profile_'.$currentTeam->id;
            $businessProfile = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(20), function () use ($currentTeam) {
                try {
                    $service = app(\App\Services\WhatsAppService::class);
                    $service->setTeam($currentTeam);
                    $metaResponse = $service->getBusinessProfile();

                    return $metaResponse['data']['data'][0] ?? null;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Could not fetch business profile for team '.$currentTeam->id.': '.$e->getMessage());

                    return null;
                }
            });
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->membership?->role ?? 'admin',
            ],
            'teams' => $teams,
            'members' => $members,
            'numbers' => $numbers,
            'business_profile' => $businessProfile,
        ]);
    }



    public function finalize(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        if (app()->environment('local')) {
            Log::debug('[DEBUG] [MOBILE_AUTH] Finalize pairing reached', [
                'user_id' => $user->id,
                'device' => $request->header('User-Agent'),
            ]);
        }

        // Potential for state update here (e.g. marking device as 'paired')
        // For now, we return the same structure as 'me' to allow immediate login.
        
        $teams = $user->allTeams()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values();

        $currentTeam = $user->currentTeam;
        $members = $currentTeam ? $currentTeam->allUsers()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->membership?->role ?? 'agent',
        ])->values() : [];

        $numbers = $currentTeam ? [[
            'id' => $currentTeam->whatsapp_phone_number_id,
            'display_number' => $currentTeam->whatsapp_phone_display ?? 'Primary Number',
            'verified_name' => $currentTeam->whatsapp_verified_name ?? $currentTeam->name,
        ]] : [];

        return response()->json([
            'status' => 'paired',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->membership?->role ?? 'admin',
            ],
            'teams' => $teams,
            'members' => $members,
            'numbers' => $numbers,
        ]);
    }


    public function numbers(Request $request)
    {
        $team = $request->user()->currentTeam;
        
        // For now, returning the primary number. 
        // This can be expanded to return multiple if the schema evolves.
        return response()->json([[
            'id' => $team->whatsapp_phone_number_id,
            'display_number' => $team->whatsapp_phone_display ?? 'Primary Number',
            'verified_name' => $team->whatsapp_verified_name ?? $team->name,
        ]]);
    }

    public function switchTeam(Request $request)
    {
        $request->validate(['team_id' => 'required|exists:teams,id']);
        
        $user = $request->user();
        $team = $user->allTeams()->where('id', $request->team_id)->first();
        
        if (!$team) {
            return response()->json(['message' => 'Team not found or access denied'], 403);
        }

        $user->forceFill(['current_team_id' => $team->id])->save();

        return response()->json(['success' => true, 'team' => ['id' => $team->id, 'name' => $team->name]]);
    }

    public function logout(Request $request)
    {
        $request->user()?->tokens()->delete();

        return response()->json(['success' => true]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        
        $phone = $request->phone;
        // Normalize phone to match how web login handles it
        try {
            $normalizedPhone = \App\Helpers\PhoneNumberHelper::normalize($phone);
        } catch (\Exception $e) {
            $normalizedPhone = $phone;
        }
        
        $user = User::where('phone', $normalizedPhone)->orWhere('phone', $phone)->first();
        if (!$user) {
            // Also try suffix match like web app
            if (strlen($phone) >= 10) {
                $suffix = substr($phone, -10);
                $user = User::where('phone', 'like', "%{$suffix}")->first();
            }
            if (!$user) {
                return response()->json(['message' => 'Phone number not registered'], 404);
            }
        }

        $otpService = app(\App\Services\OTPService::class);
        $sent = $otpService->send($user->phone, 'phone');

        if (!$sent) {
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }
        
        return response()->json(['success' => true, 'message' => 'OTP sent successfully']);
    }

    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        $phone = $request->phone;
        $otp = $request->otp;

        try {
            $normalizedPhone = \App\Helpers\PhoneNumberHelper::normalize($phone);
        } catch (\Exception $e) {
            $normalizedPhone = $phone;
        }

        $user = User::where('phone', $normalizedPhone)->orWhere('phone', $phone)->first();
        if (!$user && strlen($phone) >= 10) {
            $suffix = substr($phone, -10);
            $user = User::where('phone', 'like', "%{$suffix}")->first();
        }

        if (!$user) {
             return response()->json(['message' => 'User not found'], 404);
        }

        $otpService = app(\App\Services\OTPService::class);
        if (!$otpService->verify($user->phone, $otp)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        $teams = $user->allTeams()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values();

        $currentTeam = $user->currentTeam;
        $members = $currentTeam ? $currentTeam->allUsers()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'role' => $u->membership?->role ?? 'agent',
        ])->values() : [];

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->membership?->role ?? 'admin',
            ],
            'teams' => $teams,
            'members' => $members,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->membership?->role ?? 'admin',
            ]
        ]);
    }

    public function registerFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'sometimes|string|in:android,ios,web',
        ]);

        Log::info('FCM Token Registration Attempt', [
            'user_id' => $request->user()->id,
            'token' => substr($request->token, 0, 10) . '...',
            'platform' => $request->platform,
        ]);

        $request->user()->fcmTokens()->updateOrCreate(
            ['token' => $request->token],
            [
                'platform' => $request->platform ?? 'unknown',
                'last_used_at' => now(),
                'metadata' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]
            ]
        );

        return response()->json(['success' => true]);
    }
}
