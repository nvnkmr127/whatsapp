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
