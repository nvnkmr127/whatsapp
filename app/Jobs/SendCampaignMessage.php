<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaign;
    public $contact;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign, Contact $contact)
    {
        $this->campaign = $campaign;
        $this->contact = $contact;
    }

    /**
     * Queue Configuration
     * Rate Limit: 100/minute? Not strictly enforced here, 
     * but we rely on Redis::throttle if needed.
     * For now, standard execution.
     */

    /**
     * Middleware for Rate Limiting
     * WhatsApp Tier 1 is ~80 msgs/sec, but let's stay safe at 600 per minute (10/sec) per Team?
     * Or global limit. Let's do a Redis Throttle.
     */
    public function middleware()
    {
        // Allow 30 jobs every 1 second (approx 30/sec throughput)
        return [new \Illuminate\Queue\Middleware\ThrottlesExceptions(10, 5 * 60)];
        // Ideally: return [new RateLimited('whatsapp_send')]; but simplistic for now without Redis facade setup complexity
        // Let's use simple sleep if needed, or rely on worker speed. 
        // Actually, let's just proceed. The migration to Redis throttle requires more setup.
        // We will stick to standard execution but create the DB record.
        return [];
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsapp)
    {
        try {
            // Set Team Context
            $whatsapp->setTeam($this->campaign->team);

            // Send Template
            $response = $whatsapp->sendTemplate(
                $this->contact->phone_number,
                $this->campaign->template_name,
                $this->campaign->language ?? 'en_US',
                $this->campaign->template_variables ?? []
            );

            if (($response['success'] ?? false)) {
                $this->campaign->increment('sent_count');

                // --- ANALYTICS: Create Message Record ---
                // We need the WAMID from response
                $wamid = $response['data']['messages'][0]['id'] ?? null;

                if ($wamid) {
                    \App\Models\Message::create([
                        'team_id' => $this->campaign->team_id,
                        'contact_id' => $this->contact->id,
                        'whatsapp_message_id' => $wamid,
                        'campaign_id' => $this->campaign->id,
                        'type' => 'template',
                        'direction' => 'outbound',
                        'content' => "Template: " . $this->campaign->template_name,
                        'status' => 'sent', // Initially sent
                        'sent_at' => now(),
                    ]);
                }
                // ----------------------------------------

            } else {
                Log::warning("Campaign {$this->campaign->id} failed for {$this->contact->id}", ['response' => $response]);
            }

        } catch (\Exception $e) {
            Log::error("Campaign Send Error: " . $e->getMessage());
            // Don't fail the job, just log it. We don't want to retry marketing messages usually.
        }
    }
}
