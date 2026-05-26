<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    /**
     * Get recent mobile campaigns for the team.
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        if (! $team) {
            return response()->json([]);
        }

        $campaigns = Campaign::where('team_id', $team->id)
            ->with('template')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($campaigns);
    }

    /**
     * Start a simple broadcast/campaign.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'template_id' => 'required|exists:whatsapp_templates,id',
            'tag_id' => 'required|exists:contact_tags,id',
            'variables' => 'nullable|array',
        ]);

        $team = $request->user()->currentTeam;
        if (! $team) {
            abort(403);
        }

        $template = WhatsappTemplate::where('team_id', $team->id)->findOrFail($request->template_id);

        $campaign = Campaign::create([
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'whatsapp_template_id' => $template->whatsapp_template_id,
            'template_id' => $template->id,
            'status' => 'preparing',
            'total_contacts' => Contact::where('team_id', $team->id)->whereHas('tags', function ($q) use ($request) {
                $q->where('contact_tags.id', $request->tag_id);
            })->count(),
            'template_variables' => $request->variables ?? [],
        ]);

        // Logic here would call a dedicated CampaignService (matching web)
        // For now, assume a background job is already linked to campaign creation
        if ($campaign->total_contacts > 0) {
            // \App\Jobs\ProcessCampaignJob::dispatch($campaign);
        }

        return response()->json([
            'success' => true,
            'campaign' => $campaign->load('template'),
        ]);
    }
}
