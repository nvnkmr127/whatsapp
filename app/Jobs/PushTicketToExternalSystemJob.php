<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PushTicketToExternalSystemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $ticketId;

    public string $endpointUrl;

    public array $payload;

    public ?string $secret;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public string $deliveryId;

    public function __construct(int $ticketId, string $endpointUrl, array $payload, ?string $secret = null)
    {
        $this->ticketId = $ticketId;
        $this->endpointUrl = $endpointUrl;
        $this->payload = $payload;
        $this->secret = $secret;
        $this->deliveryId = (string) Str::uuid();
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Delivery-ID' => $this->deliveryId,
            'User-Agent' => 'Watxio-Webhook-Dispatcher/1.0',
        ];

        if ($this->secret) {
            $headers['X-Webhook-Signature'] = 'sha256='.hash_hmac('sha256', json_encode($this->payload), $this->secret);
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders($headers)
                ->post($this->endpointUrl, $this->payload);

            if ($response->successful()) {
                Log::info("PushTicketToExternalSystemJob: Ticket #{$this->ticketId} pushed to {$this->endpointUrl}. Status: {$response->status()}");

                // If response contains an external ticket ID or reference, store it
                $respData = $response->json();
                if (is_array($respData)) {
                    $extId = $respData['external_id'] ?? ($respData['ticket_id'] ?? ($respData['id'] ?? null));
                    if ($extId) {
                        Ticket::where('id', $this->ticketId)->update(['external_id' => (string) $extId]);
                    }
                }
            } else {
                Log::warning("PushTicketToExternalSystemJob: External endpoint returned {$response->status()} for Ticket #{$this->ticketId}", [
                    'body' => Str::limit($response->body(), 500),
                ]);

                if ($this->attempts() < $this->tries) {
                    throw new \Exception("External endpoint returned HTTP status: {$response->status()}");
                }
            }
        } catch (\Throwable $e) {
            Log::error("PushTicketToExternalSystemJob failed for Ticket #{$this->ticketId}: ".$e->getMessage());

            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
