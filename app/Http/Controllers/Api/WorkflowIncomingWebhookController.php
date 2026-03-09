<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workflow;
use App\Services\WorkflowEngine;
use Illuminate\Support\Facades\Log;
use App\Models\Contact;

class WorkflowIncomingWebhookController extends Controller
{
    public function handle(Request $request, $webhookUrlId)
    {
        Log::info("Incoming Workflow Webhook hit for URL ID: {$webhookUrlId}", $request->all());

        $workflow = Workflow::where('trigger_type', 'webhook_incoming')
            ->where('trigger_config->webhook_url_id', $webhookUrlId)
            ->where('is_active', true)
            ->first();

        if (!$workflow) {
            return response()->json(['status' => 'error', 'message' => 'Workflow not found or inactive'], 404);
        }

        $payload = $request->all();

        // Attempt to extract contact info if available
        $contact = null;
        if (isset($payload['phone']) || isset($payload['phone_number'])) {
            $phone = $payload['phone'] ?? $payload['phone_number'];
            $phone = \App\Helpers\PhoneNumberHelper::normalize($phone);

            $contact = Contact::where('team_id', $workflow->team_id)
                ->where('phone_number', $phone)
                ->first();
        }

        if (isset($payload['id']) && !$contact) {
            $contact = Contact::where('team_id', $workflow->team_id)->find($payload['id']);
        }

        // Fire engine
        app(WorkflowEngine::class)->trigger('webhook_incoming', $contact, [
            'webhook_payload' => $payload,
            'source_url_id' => $webhookUrlId,
            'workflow_id' => $workflow->id // explicit target
        ]);

        return response()->json(['status' => 'success', 'message' => 'Webhook received and workflow triggered']);
    }
}
