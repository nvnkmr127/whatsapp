<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCampaignMessageJob implements ShouldQueue
{
    use Queueable;

    protected $campaignId;
    protected $contactId;

    public function __construct($campaignId, $contactId)
    {
        $this->campaignId = $campaignId;
        $this->contactId = $contactId;
    }

    public function handle(): void
    {
        $campaign = \App\Models\Campaign::find($this->campaignId);
        $contact = \App\Models\Contact::find($this->contactId);

        if (!$campaign || !$contact)
            return;

        $waService = new \App\Services\WhatsAppService();

        $vars = $campaign->template_variables ?? [];

        // Simple personalization (Support {{name}} in variables)
        $vars = array_map(function ($v) use ($contact) {
            return str_replace('{{name}}', $contact->name, $v);
        }, $vars);

        try {
            $waService->setTeam($campaign->team);
            $response = $waService->sendTemplate(
                $contact->phone_number,
                $campaign->template_name,
                $campaign->template_language,
                $vars
            );

            if (!empty($response['success']) && $response['success']) {
                $msgId = $response['data']['messages'][0]['id'] ?? 'unknown_' . uniqid();

                \App\Models\Message::create([
                    'team_id' => $campaign->team_id,
                    'contact_id' => $contact->id,
                    'campaign_id' => $campaign->id,
                    // 'conversation_id' => active convo? Or null?
                    // We should link to convo for UI.
                    'conversation_id' => $contact->activeConversation->id ?? (new \App\Services\ConversationService())->ensureActiveConversation($contact)->id,
                    'whatsapp_message_id' => $msgId,
                    'direction' => 'outbound',
                    'type' => 'template',
                    'status' => 'sent',
                    'content' => "Template: {$campaign->template_name}",
                    'sent_at' => now(),
                    'metadata' => json_encode($response['data'])
                ]);

                $campaign->increment('sent_count');
            } else {
                throw new \Exception(json_encode($response['error'] ?? 'Unknown Error'));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Campaign {$campaign->id} Send Failed to {$contact->id}: " . $e->getMessage());

            \App\Models\Message::create([
                'team_id' => $campaign->team_id,
                'contact_id' => $contact->id,
                'campaign_id' => $campaign->id,
                'conversation_id' => $contact->activeConversation->id ?? (new \App\Services\ConversationService())->ensureActiveConversation($contact)->id,
                'whatsapp_message_id' => 'failed_' . uniqid(),
                'direction' => 'outbound',
                'type' => 'template',
                'status' => 'failed',
                'content' => "Template: {$campaign->template_name} (Failed)",
                'sent_at' => now(),
                'metadata' => json_encode(['error' => $e->getMessage()])
            ]);

            // Optionally increment 'failed_count' if column exists
        }
    }
}
