<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExecuteWebhookWorkflow implements ShouldQueue
{
    use Queueable;

    public $workflow;

    public $recipient;

    public $parameters;

    // Stable across retries (set once at dispatch) so a retry triggered by a
    // post-send DB failure does not re-send the template or re-count metrics.
    public $idempotencyKey;

    /**
     * Create a new job instance.
     */
    public function __construct(\App\Models\WebhookWorkflow $workflow, $recipient, $parameters)
    {
        $this->workflow = $workflow;
        $this->recipient = $recipient;
        $this->parameters = $parameters;
        $this->idempotencyKey = (string) \Illuminate\Support\Str::uuid();
    }

    /**
     * Execute the job.
     */
    public function handle(\App\Services\WhatsAppService $whatsappService): void
    {
        $template = $this->workflow->template;

        if (! $template) {
            \Illuminate\Support\Facades\Log::error("Workflow {$this->workflow->id} has no template.");

            return;
        }

        // ponytail: 24h cache guard — closes the common retry-after-DB-failure
        // dupe; not the rarer crash between send and marker set below.
        $sentKey = "wf_sent:{$this->idempotencyKey}";
        if (\Illuminate\Support\Facades\Cache::has($sentKey)) {
            \Illuminate\Support\Facades\Log::info("Workflow {$this->workflow->id}: send already completed for this dispatch, skipping re-send on retry.");

            return;
        }

        try {
            // Set context
            $whatsappService->setTeam($this->workflow->team);

            // Send
            $result = $whatsappService->sendTemplate(
                $this->recipient,
                $template->name,
                $template->language,
                $this->parameters
            );

            if ($result['success']) {
                \Illuminate\Support\Facades\Cache::put($sentKey, true, now()->addDay());
                $this->workflow->increment('total_delivered'); // 'sent' count (metric name kept for compatibility)

                // Create Message Record
                $wamid = $result['data']['messages'][0]['id'] ?? null;

                if ($wamid) {
                    \App\Models\Message::create([
                        'team_id' => $this->workflow->team_id,
                        'webhook_workflow_id' => $this->workflow->id,
                        'contact_id' => \App\Models\Contact::where('phone_number', $this->recipient)->value('id'), // Best effort match
                        'whatsapp_message_id' => $wamid,
                        'type' => 'template',
                        'direction' => 'outbound',
                        'status' => 'sent', // Initially sent
                        'content' => "Template: {$template->name}",
                        'metadata' => [
                            'template_name' => $template->name,
                            'parameters' => $this->parameters,
                        ],
                        'sent_at' => now(),
                    ]);
                }

            } else {
                \Illuminate\Support\Facades\Log::error('Workflow Send Failed: '.json_encode($result));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Workflow Job Error: '.$e->getMessage());
            $this->fail($e);
        }
    }
}
