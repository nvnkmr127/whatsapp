<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebhookWorkflow;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookTriggerController extends Controller
{
    use StandardApiResponses;

    /**
     * Trigger a webhook-based workflow.
     */
    public function trigger(Request $request, $id)
    {
        $user = $request->user();
        $team = $user?->currentTeam;
        
        if (!$team) {
            return $this->error('No team context selected.', 400);
        }

        $workflow = WebhookWorkflow::where('id', $id)
            ->where('status', true)
            ->where('team_id', $team->id)
            ->first();

        if (!$workflow) {
            return $this->error('Workflow not found or inactive.', 404);
        }

        // Validate payload: expect 'phone' or 'recipient_id'
        $recipient = $request->input('phone') ?? $request->input('recipient_id');

        if (!$recipient) {
            return $this->error('Missing phone or recipient_id in payload.', 400);
        }

        // Increment trigger count
        $workflow->increment('total_triggers');

        // Allow 'parameters' array in payload for template variables
        $parameters = $request->input('parameters', []);

        try {
            // Dispatch job for reliable asynchronous execution
            \App\Jobs\ExecuteWebhookWorkflow::dispatch($workflow, $recipient, $parameters);

            return $this->success([], 'Workflow triggered successfully.');

        } catch (\Exception $e) {
            Log::error("Webhook Workflow Execution Error: " . $e->getMessage(), [
                'workflow_id' => $workflow->id,
                'team_id' => $team->id,
                'recipient' => $recipient
            ]);
            return $this->error('Failed to trigger workflow due to server error.', 500, null, 'ERR_SERVER_ERROR');
        }
    }
}
