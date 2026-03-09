<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Workflow;
use App\Services\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkflowWebhookController extends Controller
{
    /**
     * Handle incoming webhooks specifically routed to a workflow.
     */
    public function handle(Request $request, string $workflowId, WorkflowEngine $engine)
    {
        $workflow = Workflow::where('id', $workflowId)
            ->where('is_active', true)
            ->where('trigger_type', 'webhook_incoming')
            ->first();

        if (!$workflow) {
            return response()->json(['error' => 'Workflow not found or not active'], 404);
        }

        // Standardize payload extraction
        $payload = $request->all();

        // Attempt to resolve context model. If phone number is passed in webhook, grab contact.
        // Assuming webhooks commonly pass 'phone' or 'email'
        $contact = null;
        if (!empty($payload['phone'])) {
            $contact = Contact::where('team_id', $workflow->team_id)
                ->where('phone_number', $payload['phone'])
                ->first();
        } elseif (!empty($payload['contact_id'])) {
            $contact = Contact::where('team_id', $workflow->team_id)
                ->find($payload['contact_id']);
        }

        // Fallback: If no contact resolved, create a dummy standard model or log it.
        // The engine expects a Model Subject.
        if (!$contact) {
            // Can't run workflow without a subject (for now). 
            // In future, you could have a "Lead" or "System" subject.
            Log::warning("Workflow Webhook: No contact resolved for webhook payload", $payload);
            return response()->json(['error' => 'No subject resolved (requires phone or contact_id)'], 400);
        }

        try {
            // Note: We are triggering directly to the engine bypassing the global trigger lookup
            // since this webhook is bound uniquely to a single workflow ID.
            $reflectionEngine = new \ReflectionClass($engine);
            $method = $reflectionEngine->getMethod('executeWorkflow');
            $method->setAccessible(true);
            $method->invokeArgs($engine, [$workflow, $contact, $payload]);

            return response()->json(['status' => 'success', 'message' => 'Workflow executed']);
        } catch (\Exception $e) {
            Log::error("Workflow Webhook Execution failed: " . $e->getMessage());
            return response()->json(['error' => 'Execution failed'], 500);
        }
    }
}
