<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MessageBot;
use App\Http\Requests\Api\Mobile\StoreMessageBotRequest;
use App\Http\Requests\Api\Mobile\UpdateMessageBotRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageBotController extends Controller
{
    /**
     * List all message bots for the current team.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', MessageBot::class);

        $team = $request->user()?->currentTeam;
        if (! $team) {
            return response()->json([
                'current_page' => 1,
                'data' => [],
                'last_page' => 1,
                'total' => 0,
            ]);
        }

        $bots = $team->message_bots()
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($bots);
    }

    /**
     * Create a new bot.
     */
    public function store(StoreMessageBotRequest $request)
    {
        Gate::authorize('create', MessageBot::class);

        $team = $request->user()?->currentTeam;
        if (! $team) {
            return response()->json(['message' => 'No active team selected'], 422);
        }

        $bot = $team->message_bots()->create(array_merge($request->validated(), [
            'last_modified_by' => $request->user()->id,
        ]));
        
        return response()->json($bot, 201);
    }

    /**
     * Display the specified message bot.
     */
    public function show(MessageBot $bot)
    {
        Gate::authorize('view', $bot);

        return response()->json($bot);
    }

    /**
     * Update basic settings of the bot.
     */
    public function update(UpdateMessageBotRequest $request, MessageBot $bot)
    {
        Gate::authorize('update', $bot);

        $bot->update(array_merge($request->validated(), [
            'last_modified_by' => $request->user()->id,
        ]));
        
        return response()->json($bot);
    }

    /**
     * Toggle bot active status.
     */
    public function toggle(Request $request, MessageBot $bot)
    {
        Gate::authorize('update', $bot);

        $newStatus = !$bot->is_bot_active;
        $bot->update([
            'is_bot_active' => $newStatus,
            'last_modified_by' => $request->user()->id,
            'last_published_at' => $newStatus ? now() : $bot->last_published_at,
        ]);

        return response()->json([
            'message' => $bot->is_bot_active ? 'Bot published' : 'Bot unpublished',
            'is_bot_active' => $bot->is_bot_active
        ]);
    }

    /**
     * Duplicate an existing bot.
     */
    public function duplicate(Request $request, MessageBot $bot)
    {
        Gate::authorize('create', MessageBot::class);
        Gate::authorize('view', $bot);

        $newBot = $bot->replicate();
        $newBot->name = $bot->name . ' (Copy)';
        $newBot->is_bot_active = false;
        $newBot->original_bot_id = $bot->id;
        $newBot->last_modified_by = $request->user()->id;
        $newBot->save();

        return response()->json($newBot);
    }

    /**
     * Remove the specified bot.
     */
    public function destroy(MessageBot $bot)
    {
        Gate::authorize('delete', $bot);

        $bot->delete();
        return response()->json(['message' => 'Bot deleted']);
    }
}
