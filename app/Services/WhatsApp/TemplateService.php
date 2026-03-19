<?php

namespace App\Services\WhatsApp;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use App\Core\WhatsApp\WhatsAppClient;
use Illuminate\Support\Facades\Log;

class TemplateService
{
    protected WhatsAppClient $client;
    protected \App\Core\WhatsApp\CredentialResolver $resolver;
    protected ?Team $team = null;

    public function __construct(WhatsAppClient $client, \App\Core\WhatsApp\CredentialResolver $resolver)
    {
        $this->client = $client;
        $this->resolver = $resolver;
    }

    public function setTeam(Team $team): self
    {
        $this->team = $team;
        $this->client->forTeam($team);
        return $this;
    }

    /**
     * Sync templates from Meta for the current team.
     */
    public function syncTemplates(): array
    {
        if (!$this->team) return ['success' => false, 'error' => 'Team not set'];

        $wabaId = $this->team->whatsapp_business_account_id;
        if (!$wabaId) return ['success' => false, 'error' => 'WABA ID not found'];

        $baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
        $url = "{$baseUrl}/{$wabaId}/message_templates?limit=100";
        $response = $this->client->sendRequestFullUrl($url, 'get');

        if (!$response['success']) return $response;

        $templates = $response['data']['data'] ?? [];
        foreach ($templates as $tpl) {
            WhatsappTemplate::updateOrCreate(
                ['team_id' => $this->team->id, 'whatsapp_template_id' => $tpl['id']],
                [
                    'name' => $tpl['name'],
                    'status' => $tpl['status'],
                    'category' => $tpl['category'],
                    'language' => $tpl['language'],
                    'components' => json_encode($tpl['components']),
                    'last_synced_at' => now()
                ]
            );
        }

        return ['success' => true, 'count' => count($templates)];
    }
}
