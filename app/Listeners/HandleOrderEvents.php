<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HandleOrderEvents
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;
        $status = $event->status;
        $team = $order->team;
        $config = $team->commerce_config ?? [];

        Log::info("Processing Order Event: Ord #{$order->id} -> {$status}");

        // 1. Customer Notification (WhatsApp)
        // Check if there is a template mapped for this status
        $templates = $config['templates'] ?? [];
        $templateName = $templates[$status] ?? null;

        if ($templateName) {
            // Placeholder: Call WhatsApp Service
            Log::info("--> Triggering Customer WhatsApp: {$templateName}");
            // WhatsappService::sendTemplate($order->contact, $templateName, $this->buildVariables($order));
        }

        // 2. Agent Notification (Internal)
        $agentAlerts = $config['agent_notifications'] ?? [];
        if (!empty($agentAlerts[$status])) {
            Log::info("--> Triggering Agent Alert for {$status}");
            // Notification::send($team->users, new InternalOrderAlert($order, $status));
        }
    }

    protected function buildVariables($order)
    {
        // Simple variable builder for now
        return [$order->order_id ?? $order->id];
    }
}
