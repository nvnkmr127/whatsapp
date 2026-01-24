<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderLifecycleNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function handle(OrderStatusUpdated $event)
    {
        $order = $event->order;
        $status = $event->status;
        $context = $event->context;
        $team = $order->team;

        // 1. Check Lifecycle Notification Rules
        $lifecycleService = app(\App\Services\CommerceLifecycleService::class);
        if (!$lifecycleService->shouldNotify($team, $status)) {
            Log::info("Notification suppressed by lifecycle rules for order status: {$status} in Team {$team->id}");
            return;
        }

        $config = $team->commerce_config ?? [];
        $templates = $config['templates'] ?? [];

        $templateName = $templates[$status] ?? null;

        if (!$templateName) {
            Log::info("No template configured for order status: {$status} in Team {$team->id}");
            return;
        }

        // 2. Prepare Template Parameters
        // Assumption: transactional templates usually use BODY params.
        // {{1}} Order ID
        // {{2}} Additional Info (Total for placed, Tracking for shipped)

        $bodyParams = [$order->order_id]; // {{1}}

        if ($status === 'shipped') {
            // {{2}} Tracking Info
            if (!empty($context['tracking_url'])) {
                $bodyParams[] = $context['tracking_url'];
            } elseif (!empty($context['tracking_number'])) {
                $bodyParams[] = $context['tracking_number'];
            } else {
                $bodyParams[] = 'See details';
            }
        } elseif ($status === 'placed') {
            // {{2}} Total Amount
            $bodyParams[] = $order->currency . ' ' . $order->total_amount;
        }

        // 3. Send Message
        try {
            // Initialize service with team context
            $this->whatsappService->setTeam($team);

            $this->whatsappService->sendTemplate(
                $order->contact->phone_number,
                $templateName,
                'en_US', // Default, should effectively be order->contact->language ?? 'en_US'
                $bodyParams
            );

            Log::info("Sent order status notification ({$status}) to {$order->contact->phone_number}");

        } catch (\Exception $e) {
            Log::error("Failed to send order lifecycle notification: " . $e->getMessage());
        }
    }
}
