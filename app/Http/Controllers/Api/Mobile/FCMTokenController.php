<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\UserFcmToken;
use Illuminate\Http\Request;

class FCMTokenController extends Controller
{
    /**
     * Store a new FCM token or update existing one.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'nullable|string|in:ios,android,web',
            'device_id' => 'nullable|string',
        ]);

        $user = $request->user();

        \Illuminate\Support\Facades\Log::info('FCM [1/3] Token registration received', [
            'user_id'      => $user->id,
            'user_email'   => $user->email,
            'platform'     => $request->platform,
            'device_id'    => $request->device_id,
            'token_length' => strlen($request->token),
            'token_prefix' => substr($request->token, 0, 30),
            'token_suffix' => substr($request->token, -15),
        ]);

        // Update or Create
        $fcmToken = UserFcmToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id'      => $user->id,
                'platform'     => $request->platform ?? 'unknown',
                'device_id'    => $request->device_id,
                'last_used_at' => now(),
                'metadata'     => [
                    'ip'         => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ],
            ]
        );

        \Illuminate\Support\Facades\Log::info('FCM [2/3] Token saved to DB', [
            'db_id'       => $fcmToken->id,
            'was_created' => $fcmToken->wasRecentlyCreated,
            'user_id'     => $fcmToken->user_id,
        ]);

        $tokenCount = UserFcmToken::where('user_id', $user->id)->count();
        \Illuminate\Support\Facades\Log::info("FCM [3/3] User {$user->id} now has {$tokenCount} registered token(s)");

        return response()->json([
            'success' => true,
            'message' => 'Token registered successfully',
            'token'   => $fcmToken,
        ]);
    }

    /**
     * Remove an FCM token (e.g. on logout).
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        UserFcmToken::where('token', $request->token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => 'Token removed successfully',
        ]);
    }
}
