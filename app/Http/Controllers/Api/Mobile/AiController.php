<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Setting;

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

        $settings = [
            'enabled' => (bool) $team->ai_auto_reply_enabled,
            'api_key' => Setting::where('key', "ai_openai_api_key_{$teamId}")->value('value') ?? '',
            'model' => Setting::where('key', "ai_openai_model_{$teamId}")->value('value') ?? 'gpt-4o',
            'persona' => Setting::where('key', "ai_persona_{$teamId}")->value('value') ?? '',
            'use_kb' => (bool) Setting::where('key', "ai_use_kb_{$teamId}")->value('value'),
            'kb_strict' => (bool) Setting::where('key', "ai_kb_strict_{$teamId}")->value('value'),
            'confidence_threshold' => (float) Setting::where('key', "ai_confidence_threshold_{$teamId}")->value('value') ?? 0.7,
            'operating_hours_only' => (bool) Setting::where('key', "ai_operating_hours_only_{$teamId}")->value('value'),
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

        // 2. Update Detailed Settings
        $keys = [
            'api_key' => "ai_openai_api_key_{$teamId}",
            'model' => "ai_openai_model_{$teamId}",
            'persona' => "ai_persona_{$teamId}",
            'use_kb' => "ai_use_kb_{$teamId}",
            'kb_strict' => "ai_kb_strict_{$teamId}",
            'confidence_threshold' => "ai_confidence_threshold_{$teamId}",
            'operating_hours_only' => "ai_operating_hours_only_{$teamId}",
        ];

        foreach ($keys as $reqKey => $settingKey) {
            if ($request->has($reqKey)) {
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    ['value' => $request->input($reqKey)]
                );
            }
        }

        return response()->json(['message' => 'AI settings updated successfully!']);
    }
}
