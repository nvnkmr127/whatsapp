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
            try {
                $service = app(\App\Services\WhatsAppService::class);
                $service->setTeam($currentTeam);
                $metaResponse = $service->getBusinessProfile();
                if (isset($metaResponse['data']['data'][0])) {
                    $businessProfile = $metaResponse['data']['data'][0];
                }
            } catch (\Exception $e) {
                // Ignore API failures to prevent blocking login
            }
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
        // Normalize phone (remove +, spaces, etc if needed)
        
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Phone number not registered'], 404);
        }

        $otpService = app(\App\Services\OTPService::class);
        $sent = $otpService->send($phone, 'phone');

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

        $otpService = app(\App\Services\OTPService::class);
        if (!$otpService->verify($phone, $otp)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $user = User::where('phone', $phone)->first();
        if (!$user) {
             return response()->json(['message' => 'User not found'], 404);
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
}
