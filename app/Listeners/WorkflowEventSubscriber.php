<?php

namespace App\Listeners;

use App\Services\WorkflowEngine;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class WorkflowEventSubscriber
{
    protected WorkflowEngine $workflowEngine;

    public function __construct(WorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Handle incoming WhatsApp messages.
     */
    public function handleMessageReceived($event)
    {
        if ($event->message->direction === 'inbound') {
            // Provide the Contact as the primary Subject
            if ($event->message->contact) {
                Log::info("WorkflowEventSubscriber: Firing message_received for contact {$event->message->contact->id}");
                $this->workflowEngine->trigger('message_received', $event->message->contact, [
                    'message_content' => $event->message->content,
                    'message_id' => $event->message->id,
                ]);
            }
        }
    }

    /**
     * Handle orders (Using OrderStatusUpdated if applicable, or generic order events)
     */
    public function handleOrderStatusUpdated($event)
    {
        if (isset($event->order) && isset($event->order->contact)) {
            Log::info('WorkflowEventSubscriber: Firing order status event');
            $this->workflowEngine->trigger('order_placed', $event->order->contact, [
                'order_status' => $event->order->status ?? 'pending',
                'order_amount' => $event->order->total ?? 0,
            ]);
        }
    }

    /**
     * Catch-all generic string event handler for Workflows
     */
    public function handleGenericTrigger(string $eventName, array $payload)
    {
        Log::info("WorkflowEventSubscriber: Firing generic trigger {$eventName}");

        // $payload[0] would typically be the primary model subject (Contact, Deal, etc.)
        if (isset($payload[0]) && $payload[0] instanceof \Illuminate\Database\Eloquent\Model) {
            $context = $payload[1] ?? [];
            $this->workflowEngine->trigger($eventName, $payload[0], $context);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        // 1. Native Application Events
        if (class_exists(\App\Events\MessageReceived::class)) {
            $events->listen(
                \App\Events\MessageReceived::class,
                [WorkflowEventSubscriber::class, 'handleMessageReceived']
            );
        }

        if (class_exists(\App\Events\OrderStatusUpdated::class)) {
            $events->listen(
                \App\Events\OrderStatusUpdated::class,
                [WorkflowEventSubscriber::class, 'handleOrderStatusUpdated']
            );
        }

        // 2. Extensible String Events explicitly built into the Workflow visual builder dropdown keys
        $events->listen('payment_received', [WorkflowEventSubscriber::class, 'handleGenericTrigger']);
        $events->listen('form_submitted', [WorkflowEventSubscriber::class, 'handleGenericTrigger']);
        $events->listen('api_call', [WorkflowEventSubscriber::class, 'handleGenericTrigger']);
        $events->listen('ecommerce_event', [WorkflowEventSubscriber::class, 'handleGenericTrigger']);
        $events->listen('crm_event', [WorkflowEventSubscriber::class, 'handleGenericTrigger']);
    }
}
