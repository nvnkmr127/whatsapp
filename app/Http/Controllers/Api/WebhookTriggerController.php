<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookTriggerController extends Controller
{
    public function trigger(Request $request, $id)
    {
        $workflow = \App\Models\WebhookWorkflow::where('id', $id)->where('status', true)->first();

        if (!$workflow) {
            return response()->json(['error' => 'Workflow not found or inactive'], 404);
        }

        // Validate payload
        // We expect 'phone' or 'recipient_id'
        $recipient = $request->input('phone') ?? $request->input('recipient_id');

        if (!$recipient) {
            return response()->json(['error' => 'Missing phone or recipient_id in payload'], 400);
        }

        // Increment trigger count
        $workflow->increment('total_triggers');

        // Send Message
        // We need to map parameters if template has variables.
        // For MVP, allow 'parameters' array in payload.
        $parameters = $request->input('parameters', []);

        try {
            // Using a Job to send for reliability, or direct service call.
            // Let's use WhatsAppService directly for now if available, or Job.
            // Checking availability of service... assuming we will implement/use sendTemplateMessage

            // Note: We need a way to cleanly send. 
            // Let's dispatch a job which we can create or use existing if any.
            // Or use the Trait if we add send method.

            // For now, let's assume we dispatch a job we will ensure exists.
            \App\Jobs\ExecuteWebhookWorkflow::dispatch($workflow, $recipient, $parameters);

            return response()->json(['status' => 'success', 'message' => 'Workflow triggered'], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Workflow Error: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
