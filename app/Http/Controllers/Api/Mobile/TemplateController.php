<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use App\Http\Requests\Api\Mobile\StoreTemplateDraftRequest;
use App\Http\Requests\Api\Mobile\UpdateTemplateDraftRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TemplateController extends Controller
{
    /**
     * List all templates for the team with filters.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', WhatsappTemplate::class);

        $team = $request->user()->currentTeam;
        if (!$team) return response()->json([]);

        $query = WhatsappTemplate::where('team_id', $team->id);

        if ($request->filled('query')) {
            $query->where('name', 'like', '%' . $request->input('query') . '%');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        $templates = $query->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return response()->json($templates);
    }

    /**
     * Get a single template details.
     */
    public function show(WhatsappTemplate $template)
    {
        Gate::authorize('view', $template);

        return response()->json($template);
    }

    /**
     * Create a new template draft.
     */
    public function store(StoreTemplateDraftRequest $request)
    {
        Gate::authorize('create', WhatsappTemplate::class);

        $template = WhatsappTemplate::create(array_merge($request->validated(), [
            'team_id' => $request->user()->current_team_id,
            'status' => 'DRAFT',
            'is_mobile_draft' => true,
            'readiness_score' => 0,
        ]));

        return response()->json($template, 201);
    }

    /**
     * Update a template draft.
     */
    public function update(UpdateTemplateDraftRequest $request, WhatsappTemplate $template)
    {
        Gate::authorize('update', $template);

        if ($template->status !== 'DRAFT') {
            return response()->json(['error' => 'Only drafts can be updated'], 422);
        }

        $template->update($request->validated());

        return response()->json($template);
    }

    /**
     * Toggle template paused status.
     */
    public function toggle(Request $request, WhatsappTemplate $template)
    {
        Gate::authorize('update', $template);

        $template->update([
            'is_paused' => !$template->is_paused,
            'last_status_sync_at' => now(),
        ]);

        return response()->json([
            'message' => $template->is_paused ? 'Template paused' : 'Template resumed',
            'is_paused' => $template->is_paused
        ]);
    }

    /**
     * Send a test template message.
     */
    public function sendTest(Request $request, WhatsappTemplate $template)
    {
        Gate::authorize('view', $template);

        $request->validate([
            'phone_number' => 'required|string',
            'whatsapp_number_id' => 'required|integer',
        ]);

        try {
            $service = app(\App\Services\WhatsAppService::class);
            $service->sendTemplateMessage(
                $request->whatsapp_number_id,
                $request->phone_number,
                $template->name,
                $template->language,
                [] 
            );

            return response()->json(['message' => 'Test message sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified template draft.
     */
    public function destroy(WhatsappTemplate $template)
    {
        Gate::authorize('delete', $template);

        if ($template->status !== 'DRAFT') {
            return response()->json(['error' => 'Only drafts can be deleted via mobile'], 422);
        }

        $template->delete();
        return response()->json(['message' => 'Template draft deleted']);
    }
}
