<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
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
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->membership?->role ?? 'admin',
            ],
            'teams' => $teams,
            'members' => $members,
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
}
