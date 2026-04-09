<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Workflow;
use App\Services\WorkflowEngine;
use App\Traits\StandardApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkflowWebhookController extends Controller
{
    use StandardApiResponses;

    /**
     * Handle incoming webhooks specifically routed to a workflow.
     */
    public function handle(Request $request, string $workflowId, WorkflowEngine $engine)
    {
        $workflow = Workflow::where('id', $workflowId)
            ->where('is_active', true)
            ->where('trigger_type', 'webhook_incoming')
            ->first();

        if (! $workflow) {
            return $this->error('Workflow not found or inactive.', 404, null, 'ERR_WORKFLOW_NOT_FOUND');
        }

        $payload = $request->all();

        // Attempt to resolve context model
        $contact = null;
        if (! empty($payload['phone'])) {
            $contact = Contact::where('team_id', $workflow->team_id)
                ->where('phone_number', $payload['phone'])
                ->first();
        } elseif (! empty($payload['contact_id'])) {
            $contact = Contact::where('team_id', $workflow->team_id)
                ->find($payload['contact_id']);
        }

        if (! $contact) {
            Log::warning('Workflow Webhook: No contact resolved for webhook payload', $payload);

            return $this->error('No subject resolved. Requires phone or contact_id.', 400, null, 'ERR_SUBJECT_MISSING');
        }

        try {
            // Triggering directly via engine reflection (bypass global trigger lookup)
            $reflectionEngine = new \ReflectionClass($engine);
            $method = $reflectionEngine->getMethod('executeWorkflow');
            $method->setAccessible(true);
            $method->invokeArgs($engine, [$workflow, $contact, $payload]);

            return $this->success([], 'Workflow executed successfully.');
        } catch (\Exception $e) {
            Log::error('Workflow Webhook Execution failed: '.$e->getMessage(), [
                'workflow_id' => $workflowId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Execution failed due to internal error.', 500, null, 'ERR_SERVER_ERROR');
        }
    }
}
