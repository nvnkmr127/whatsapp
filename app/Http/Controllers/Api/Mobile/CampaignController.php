<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Jobs\PrepareCampaignJob;

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
            'scheduled_at' => 'nullable|date',
        ]);

        $team = $request->user()->currentTeam;
        if (! $team) {
            abort(403);
        }

        $template = WhatsappTemplate::where('team_id', $team->id)->findOrFail($request->template_id);

        $scheduledAt = $request->scheduled_at ? Carbon::parse($request->scheduled_at) : now();
        if ($scheduledAt->isPast()) {
            $scheduledAt = now();
        }

        $campaign = Campaign::create([
            'team_id' => $team->id,
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'whatsapp_template_id' => $template->whatsapp_template_id,
            'template_id' => $template->id,
            'status' => 'scheduled',
            'total_contacts' => Contact::where('team_id', $team->id)->whereHas('tags', function ($q) use ($request) {
                $q->where('contact_tags.id', $request->tag_id);
            })->count(),
            'template_variables' => $request->variables ?? [],
            'scheduled_at' => $scheduledAt,
        ]);

        $delaySeconds = now()->diffInSeconds($scheduledAt, false);
        $delaySeconds = $delaySeconds > 0 ? $delaySeconds : 0;

        $criteria = [
            'selection_type' => 'tags',
            'tags' => [$request->tag_id],
        ];

        PrepareCampaignJob::dispatch($campaign->id, $criteria)->delay($delaySeconds);

        return response()->json([
            'success' => true,
            'campaign' => $campaign->load('template'),
        ]);
    }
}
