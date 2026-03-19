<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Workflow;
use App\Services\WorkflowEngine;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkflowIncomingWebhookController extends Controller
{
    use StandardApiResponses;

    /**
     * Handle incoming webhooks that trigger workflows.
     */
    public function handle(Request $request, $webhookUrlId)
    {
        Log::info("Incoming Workflow Webhook hit for URL ID: {$webhookUrlId}", $request->all());

        $workflow = Workflow::where('trigger_type', 'webhook_incoming')
            ->where('trigger_config->webhook_url_id', $webhookUrlId)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return $this->error('Workflow not found or inactive.', 404, null, 'ERR_WORKFLOW_NOT_FOUND');
        }

        $payload = $request->all();

        // Attempt to extract contact info if available
        $contact = null;
        if (isset($payload['phone']) || isset($payload['phone_number'])) {
            $phone = \App\Helpers\PhoneNumberHelper::normalize($payload['phone'] ?? $payload['phone_number']);

            $contact = Contact::where('team_id', $workflow->team_id)
                ->where('phone_number', $phone)
                ->first();
        }

        if (isset($payload['id']) && !$contact) {
            $contact = Contact::where('team_id', $workflow->team_id)->find($payload['id']);
        }

        if (!$contact) {
            Log::warning("Workflow incoming webhook: No contact resolved for payload.", ['url_id' => $webhookUrlId]);
        }

        // Fire engine
        app(WorkflowEngine::class)->trigger('webhook_incoming', $contact ?? new Contact(), [
            'webhook_payload' => $payload,
            'source_url_id'   => $webhookUrlId,
            'workflow_id'     => $workflow->id // explicit target
        ]);

        return $this->success([], 'Webhook received and workflow triggered.');
    }
}

