<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ConversationLockController extends Controller
{
    /**
     * Lock a conversation for the current user.
     */
    public function lock(Request $request, $id)
    {
        $conversation = Conversation::where('id', $id)->where('team_id', Auth::user()->currentTeam->id)->firstOrFail();
        
        $lockKey = "conversation_lock_{$id}";
        $currentLock = Cache::get($lockKey);

        // If locked by someone else
        if ($currentLock && (int)$currentLock !== Auth::id()) {
            $user = \App\Models\User::find($currentLock);
            return response()->json([
                'success' => false,
                'owner' => (int)$currentLock,
                'owner_name' => $user ? $user->name : 'Unknown Agent',
                'message' => 'Conversation is locked by another agent.'
            ]);
        }

        // Acquire lock (expires in 30 seconds if not refreshed)
        Cache::put($lockKey, Auth::id(), 30);

        return response()->json([
            'success' => true,
            'owner' => Auth::id(),
            'owner_name' => Auth::user()->name
        ]);
    }

    /**
     * Unlock a conversation.
     */
    public function unlock(Request $request, $id)
    {
        $conversation = Conversation::where('id', $id)->where('team_id', Auth::user()->currentTeam->id)->firstOrFail();
        
        $lockKey = "conversation_lock_{$id}";
        $currentLock = Cache::get($lockKey);

        if ($currentLock && $currentLock === Auth::id()) {
            Cache::forget($lockKey);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Not locked by you.']);
    }

    /**
     * Force takeover a conversation.
     */
    public function takeover(Request $request, $id)
    {
        $conversation = Conversation::where('id', $id)->where('team_id', Auth::user()->currentTeam->id)->firstOrFail();
        
        // Force overwrite lock
        $lockKey = "conversation_lock_{$id}";
        Cache::put($lockKey, Auth::id(), 30);

        return response()->json([
            'success' => true,
            'owner' => Auth::id(),
            'message' => 'Conversation takeover successful.'
        ]);
    }

    /**
     * Refresh the lock heartbeat.
     */
    public function heartbeat(Request $request, $id)
    {
        $lockKey = "conversation_lock_{$id}";
        $currentLock = Cache::get($lockKey);

        if ($currentLock && $currentLock === Auth::id()) {
            Cache::put($lockKey, Auth::id(), 30); // Extend by 30s
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Lock lost or expired.']);
    }
}
