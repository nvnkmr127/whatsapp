<?php

namespace App\Listeners;

use App\Events\ContactCreated;
use App\Services\WorkflowEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class TriggerWorkflowsOnContactCreated implements ShouldQueue
{
    use InteractsWithQueue;

    protected $workflowEngine;

    /**
     * Create the event listener.
     *
     * @param WorkflowEngine $workflowEngine
     */
    public function __construct(WorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Handle the event.
     *
     * @param ContactCreated $event
     * @return void
     */
    public function handle(ContactCreated $event): void
    {
        try {
            $this->workflowEngine->trigger(
                'contact_created', 
                $event->contact, 
                $event->context
            );
        } catch (\Exception $e) {
            Log::error("Workflow trigger failed on ContactCreated Event: " . $e->getMessage(), [
                'contact_id' => $event->contact->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
