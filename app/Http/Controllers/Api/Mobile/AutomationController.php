<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Automation::class);

        $team = $request->user()?->currentTeam;
        if (! $team) {
            return response()->json([]);
        }

        $automations = $team->automations()
            ->withCount(['runs', 'steps'])
            ->get();

        return response()->json($automations);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Automation::class);

        $team = $request->user()?->currentTeam;
        if (! $team) {
            return response()->json(['message' => 'No active team selected'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $defaultText = match ($validated['trigger_type']) {
            'birthday_event' => '🎉 Happy Birthday! We hope you have a fantastic day. As a special gift from us, here is a 15% discount coupon for your next purchase: BDAY15. Enjoy! 🎁',
            'order_delivered' => 'Thank you for shopping with us! 📦 Your order has been delivered. On a scale of 1-10, how likely are you to recommend us to a friend or colleague? Reply with a number from 1 to 10.',
            'outbound_spam' => '⚠️ Your outbound message was flagged by our system spam filter due to suspicious keywords. Please review our fair usage policy.',
            'inbound_translation' => '🌐 [System] Translating inbound message to workspace default language...',
            default => '⚡ Custom automation flow has been triggered successfully.',
        };

        $flowData = [
            'nodes' => [
                ['id' => '1', 'type' => 'trigger'],
                ['id' => '2', 'type' => 'message', 'data' => ['text' => $defaultText]],
                ['id' => '3', 'type' => 'stop'],
            ],
            'edges' => [
                ['source' => '1', 'target' => '2'],
                ['source' => '2', 'target' => '3'],
            ],
        ];

        $automation = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $defaultText, $flowData, $team) {
            $automation = $team->automations()->create([
                'name' => $validated['name'],
                'trigger_type' => $validated['trigger_type'],
                'trigger_config' => [],
                'is_active' => $validated['is_active'] ?? true,
                'flow_data' => $flowData,
            ]);

            $automation->steps()->create([
                'team_id' => $automation->team_id,
                'type' => 'trigger',
                'config' => [],
                'order_index' => 0,
            ]);

            $automation->steps()->create([
                'team_id' => $automation->team_id,
                'type' => 'message',
                'config' => ['text' => $defaultText],
                'order_index' => 1,
            ]);

            $automation->steps()->create([
                'team_id' => $automation->team_id,
                'type' => 'stop',
                'config' => [],
                'order_index' => 2,
            ]);

            return $automation;
        });

        return response()->json($automation);
    }

    public function show(Request $request, Automation $automation)
    {
        $this->authorize('view', $automation);

        $automation->loadCount(['runs', 'steps']);

        return response()->json($automation);
    }

    public function update(Request $request, Automation $automation)
    {
        $this->authorize('update', $automation);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $automation->update($validated);

        return response()->json($automation);
    }

    public function toggle(Request $request, Automation $automation)
    {
        $this->authorize('update', $automation);

        $automation->update(['is_active' => !$automation->is_active]);

        return response()->json($automation);
    }

    public function destroy(Request $request, Automation $automation)
    {
        $this->authorize('delete', $automation);
        $automation->delete();
        return response()->json(['message' => 'Automation deleted']);
    }
}
