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

        $automations = $request->user()->currentTeam->automations()
            ->withCount(['runs', 'steps'])
            ->get();

        return response()->json($automations);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Automation::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $automation = $request->user()->currentTeam->automations()->create([
            'name' => $validated['name'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_config' => [],
            'is_active' => $validated['is_active'] ?? true,
            'flow_data' => [],
        ]);

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
