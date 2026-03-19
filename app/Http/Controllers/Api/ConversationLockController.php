<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConversationLockController extends Controller
{
    use StandardApiResponses;

    /**
     * Lock a conversation for the current user.
     */
    public function lock(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->currentTeam) {
            return $this->error('No team context selected.', 400);
        }

        $conversation = Conversation::where('id', $id)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();
        
        $lockKey = "conversation_lock_{$id}";
        $currentLock = Cache::get($lockKey);

        // If locked by someone else
        if ($currentLock && (int)$currentLock !== $user->id) {
            $owner = User::find($currentLock);
            return $this->error(
                'Conversation is locked by another agent.',
                200, // Return success: false but HTTP 200 for logical lock conflict
                [
                    'owner' => (int)$currentLock,
                    'owner_name' => $owner ? $owner->name : 'Unknown Agent'
                ],
                'ERR_CONVERSATION_LOCKED'
            );
        }

        // Acquire lock (expires in 30 seconds if not refreshed)
        Cache::put($lockKey, $user->id, 30);

        return $this->success([
            'owner' => $user->id,
            'owner_name' => $user->name,
            'expires_in' => 30
        ], 'Conversation locked successfully.');
    }

    /**
     * Unlock a conversation.
     */
    public function unlock(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->currentTeam) {
            return $this->error('No team context selected.', 400);
        }

        Conversation::where('id', $id)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();
        
        $lockKey = "conversation_lock_{$id}";
        $currentLock = Cache::get($lockKey);

        if ($currentLock && (int)$currentLock === $user->id) {
            Cache::forget($lockKey);
            return $this->success([], 'Conversation unlocked successfully.');
        }

        return $this->error('Not locked by you.', 403, null, 'ERR_NOT_OWNER');
    }

    /**
     * Force takeover a conversation.
     */
    public function takeover(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->currentTeam) {
            return $this->error('No team context selected.', 400);
        }

        Conversation::where('id', $id)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();
        
        // Force overwrite lock
        $lockKey = "conversation_lock_{$id}";
        Cache::put($lockKey, $user->id, 30);

        return $this->success([
            'owner' => $user->id,
            'expires_in' => 30
        ], 'Conversation takeover successful.');
    }

    /**
     * Refresh the lock heartbeat.
     */
    public function heartbeat(Request $request, $id)
    {
        $user = $request->user();
        $lockKey = "conversation_lock_{$id}";
        $currentLock = Cache::get($lockKey);

        if ($currentLock && (int)$currentLock === $user->id) {
            Cache::put($lockKey, $user->id, 30); // Extend by 30s
            return $this->success(['owner' => $user->id], 'Lock refreshed.');
        }

        return $this->error('Lock lost or expired.', 401, null, 'ERR_LOCK_LOST');
    }
}

