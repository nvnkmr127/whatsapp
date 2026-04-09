<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Models\Integration;
use App\Models\Message;
use App\Services\Integrations\MetaMarketingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendConversionApiEvent implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;
        $status = $event->status;

        // 1. Filter for Conversion Events (Purchase)
        // We consider 'paid' (Prepaid) and 'confirmed' (COD verified) as conversions
        if (! in_array($status, ['paid', 'confirmed'])) {
            return;
        }

        // 2. Check for Meta Marketing Integration
        $integration = Integration::where('team_id', $order->team_id)
            ->where('type', 'meta_marketing')
            ->where('status', 'connected')
            ->first();

        if (! $integration) {
            return;
        }

        // 3. Check for Pixel ID in configuration
        // Assuming Pixel ID is stored in integration credentials or team settings
        // For this implementation, we'll look in integration credentials
        $pixelId = $integration->credentials['pixel_id'] ?? null;

        if (! $pixelId) {
            // Log warning if integration exists but pixel is missing
            Log::warning("Meta CAPI: Missing Pixel ID for Team {$order->team_id}");

            return;
        }

        try {
            // 4. Attribution Matching
            // Try to find the original message that led to this user
            // We look for messages with 'referral' metadata for this contact
            $attributionData = [];
            $contact = $order->contact;

            $firstMessage = Message::where('contact_id', $contact->id)
                ->where('direction', 'inbound')
                ->whereNotNull('metadata->referral')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstMessage) {
                $referral = json_decode($firstMessage->metadata, true)['referral'] ?? [];
                // fbc (Click ID) and fbp (Browser ID) logic would go here if we were tracking cookies
                // For now, we rely on Advanced Matching (hashed email/phone)
            }

            // 5. Construct CAPI Payload
            $userData = [
                'ph' => [hash('sha256', $contact->phone_number)], // SHA256 hashed phone
            ];

            if ($contact->email) {
                $userData['em'] = [hash('sha256', strtolower($contact->email))];
            }
            if ($contact->name) {
                // Basic split for fn/ln - imperfect but standard for simple matching
                $parts = explode(' ', trim($contact->name), 2);
                $userData['fn'] = [hash('sha256', strtolower($parts[0]))];
                if (isset($parts[1])) {
                    $userData['ln'] = [hash('sha256', strtolower($parts[1]))];
                }
            }

            $eventPayload = [
                'event_name' => 'Purchase',
                'event_time' => time(),
                'action_source' => 'chat', // Conversions happening in WhatsApp
                'user_data' => $userData,
                'custom_data' => [
                    'currency' => $order->currency ?? 'USD',
                    'value' => (float) $order->total_amount,
                    'order_id' => (string) $order->id,
                    'content_ids' => $order->items->pluck('product_id')->toArray(),
                    'content_type' => 'product',
                    'num_items' => $order->items->sum('quantity'),
                ],
            ];

            // 6. Send Event
            $service = new MetaMarketingService($integration);
            $service->sendConversionEvents($pixelId, [$eventPayload]);

            Log::info("Meta CAPI: Sent Purchase event for Order {$order->id}");

        } catch (\Exception $e) {
            Log::error("Meta CAPI Error for Order {$order->id}: ".$e->getMessage());
        }
    }
}
