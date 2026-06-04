<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use App\Models\Setting;
use App\Services\AI\AIProviderManager;
use Illuminate\Http\Request;

class AiController extends Controller
{
    /**
     * Get all AI Assistant settings for the team.
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) return response()->json([]);

        $teamId = $team->id;
        $provider = get_setting("ai_provider_{$teamId}", 'openai');

        $settings = [
            'enabled' => (bool) $team->ai_auto_reply_enabled,
            'provider' => $provider,
            'api_key' => get_setting("ai_{$provider}_api_key_{$teamId}") ?? '',
            'model' => get_setting("ai_model_{$teamId}") ?? 'gpt-4o',
            'persona' => get_setting("ai_persona_{$teamId}") ?? '',
            'use_kb' => (bool) get_setting("ai_use_kb_{$teamId}"),
            'kb_strict' => (bool) get_setting("ai_kb_strict_{$teamId}"),
            'confidence_threshold' => (float) get_setting("ai_confidence_threshold_{$teamId}", 0.7),
            'operating_hours_only' => (bool) get_setting("ai_operating_hours_only_{$teamId}"),
        ];

        return response()->json($settings);
    }

    /**
     * Update AI Assistant settings.
     */
    public function update(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) return response()->json(['error' => 'No team selected'], 422);

        $teamId = $team->id;

        // 1. Toggle Global AI
        if ($request->has('enabled')) {
            $team->update(['ai_auto_reply_enabled' => (bool) $request->enabled]);
        }

        // Determine provider (from request first, then DB)
        $provider = $request->input('provider') ?? get_setting("ai_provider_{$teamId}", 'openai');

        // 2. Update Detailed Settings
        $keys = [
            'provider' => "ai_provider_{$teamId}",
            'api_key' => "ai_{$provider}_api_key_{$teamId}",
            'model' => "ai_model_{$teamId}",
            'persona' => "ai_persona_{$teamId}",
            'use_kb' => "ai_use_kb_{$teamId}",
            'kb_strict' => "ai_kb_strict_{$teamId}",
            'confidence_threshold' => "ai_confidence_threshold_{$teamId}",
            'operating_hours_only' => "ai_operating_hours_only_{$teamId}",
        ];

        foreach ($keys as $reqKey => $settingKey) {
            if ($request->has($reqKey)) {
                set_setting($settingKey, $request->input($reqKey), 'ai_settings');
            }
        }

        return response()->json(['message' => 'AI settings updated successfully!']);
    }

    /**
     * Generate an AI reply suggestion for a conversation.
     * Returns a suggested response the agent can accept or edit before sending.
     */
    public function suggest(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        // Authorize access
        if ($conversation->team_id !== $user->currentTeam?->id && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized access to this conversation.');
        }

        $team = $user->currentTeam;
        $teamId = $team->id;

        // Build conversation context from the last 10 messages
        $messages = Message::where('contact_id', $conversation->contact_id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        if ($messages->isEmpty()) {
            return response()->json(['error' => 'No messages to generate a suggestion from.'], 422);
        }

        // Build a transcript string for the AI prompt
        $transcript = $messages->map(function ($msg) {
            $role = $msg->direction === 'inbound' ? 'Customer' : 'Agent';
            return "[{$role}]: " . ($msg->content ?? '(media)');
        })->join("\n");

        $persona = get_setting("ai_persona_{$teamId}")
            ?? 'You are a helpful and professional customer service agent.';

        $prompt = "{$persona}\n\nConversation:\n{$transcript}\n\n"
            . "Based on the above conversation, generate a concise and professional reply for the Agent to send next. "
            . "Return ONLY the reply text, nothing else.";

        try {
            // Use the summarize method which accepts a plain prompt string
            $suggestion = AIProviderManager::summarize($team, $prompt);

            return response()->json([
                'suggestion' => trim($suggestion),
            ]);
        } catch (\Exception $e) {
            \Log::error('[AI_SUGGEST] Failed: ' . $e->getMessage(), ['team_id' => $teamId]);
            return response()->json(['error' => 'AI suggestion failed. Please check your AI settings.'], 500);
        }
    }
}
