<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Services\ConversationService;

class ConversationController extends Controller
{
    protected $service;

    public function __construct(ConversationService $service)
    {
        $this->service = $service;
    }

    public function lock(Request $request, Conversation $conversation)
    {
        // Policy check or Team check recommended here
        if ($conversation->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        $result = $this->service->acquireLock($conversation->id, $request->user()->id);

        return response()->json($result);
    }

    public function unlock(Request $request, Conversation $conversation)
    {
        if ($conversation->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        $this->service->releaseLock($conversation->id, $request->user()->id);

        return response()->json(['success' => true]);
    }

    public function heartbeat(Request $request, Conversation $conversation)
    {
        if ($conversation->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        // Heartbeat is essentially a re-acquire
        $result = $this->service->acquireLock($conversation->id, $request->user()->id);

        return response()->json($result);
    }

    public function forceTakeOver(Request $request, Conversation $conversation)
    {
        if ($conversation->team_id !== $request->user()->currentTeam->id) {
            abort(403);
        }

        $this->service->forceTakeOver($conversation->id, $request->user()->id);

        // Broadcast LockBroken event ??
        // For now, next heartbeat of other user will fail or UI will update via Echo if we added that.
        // Let's assume frontend Presence handles the "Oh I lost lock" notification via Echo Presence channel updates,
        // but ideally an explicit event `LockTaken` is better.

        return response()->json(['success' => true]);
    }
}
