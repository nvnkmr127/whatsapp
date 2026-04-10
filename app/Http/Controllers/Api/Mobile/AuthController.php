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

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'teams' => $teams,
        ]);
    }

    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $teams = $user->allTeams()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'teams' => $teams,
        ]);
    }

    public function teams(Request $request)
    {
        return response()->json($request->user()->allTeams()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values());
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
