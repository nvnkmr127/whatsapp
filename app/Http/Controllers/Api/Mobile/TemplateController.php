<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * List all templates for the team with filters.
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (!$team) return response()->json([]);

        $query = WhatsappTemplate::where('team_id', $team->id);

        // Search by name
        if ($request->filled('query')) {
            $query->where('name', 'like', '%' . $request->input('query') . '%');
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by language
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        $templates = $query->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json($templates);
    }

    /**
     * Get a single template details.
     */
    public function show(Request $request, $id)
    {
        $team = $request->user()->currentTeam;
        $template = WhatsappTemplate::where('team_id', $team->id)->findOrFail($id);

        return response()->json($template);
    }

    /**
     * Send a test template message.
     */
    public function sendTest(Request $request, $id)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'whatsapp_number_id' => 'required|integer',
        ]);

        $team = $request->user()->currentTeam;
        $template = WhatsappTemplate::where('team_id', $team->id)->findOrFail($id);

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
}
