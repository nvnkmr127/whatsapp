<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PresenceController extends Controller
{
    /**
     * Heartbeat to signal the user is actively viewing a conversation.
     * Prevents push notifications for messages in this chat.
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
        ]);

        $user = $request->user();
        $convId = $request->conversation_id;

        // Presence expires in 45 seconds (Mobile app should send heartbeat every 30s)
        $key = "user_presence:{$user->id}:conv:{$convId}";
        Cache::put($key, true, 45);

        return response()->json([
            'status' => 'active',
            'expires_at' => now()->addSeconds(45)->timestamp,
        ]);
    }

    /**
     * Signal the user left the conversation.
     */
    public function leave(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
        ]);

        $user = $request->user();
        $convId = $request->conversation_id;

        Cache::forget("user_presence:{$user->id}:conv:{$convId}");

        return response()->json(['status' => 'inactive']);
    }
}
