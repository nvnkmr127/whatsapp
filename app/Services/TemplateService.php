<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateService
{
    protected $baseUrl = 'https://graph.facebook.com/v21.0';

    /**
     * Sync Templates from Meta for a Team using its WABA ID.
     */
    public function syncTemplates(Team $team)
    {
        $wabaId = $team->whatsapp_business_account_id;
        $accessToken = $team->whatsapp_access_token;

        if (!$wabaId || !$accessToken) {
            throw new \Exception("WABA ID or Access Token missing for Team {$team->id}");
        }

        // Fetch from Meta
        // Pagination logic omitted for MVP
        $response = Http::withToken($accessToken)->get("{$this->baseUrl}/{$wabaId}/message_templates", [
            'limit' => 100
        ]);

        if ($response->failed()) {
            throw new \Exception("Failed to fetch templates: " . $response->body());
        }

        $remoteTemplates = $response->json()['data'] ?? [];
        $syncedCount = 0;

        foreach ($remoteTemplates as $remote) {
            $this->updateOrCreateTemplate($team, $remote);
            $syncedCount++;
        }

        return $syncedCount;
    }

    protected function updateOrCreateTemplate(Team $team, array $remote)
    {
        return WhatsappTemplate::updateOrCreate(
            [
                'team_id' => $team->id,
                'name' => $remote['name'],
                'language' => $remote['language'],
            ],
            [
                'whatsapp_template_id' => $remote['id'],
                'category' => $remote['category'],
                'status' => $remote['status'],
                'components' => $remote['components'], // Json cast handles array
            ]
        );
    }

    /**
     * Validate that provided variables match the template placeholders.
     * Prevents "Parameter count mismatch" errors from Meta.
     */
    public function validateVariables(WhatsappTemplate $template, array $variables): bool
    {
        $expectedCount = $this->countPlaceholders($template);
        $providedCount = count($variables);

        if ($providedCount !== $expectedCount) {
            Log::warning("Template Validation Failed: {$template->name}", [
                'expected' => $expectedCount,
                'provided' => $providedCount
            ]);
            return false;
        }

        return true;
    }

    /**
     * Count {{1}}, {{2}} in BODY and HEADER (text).
     */
    protected function countPlaceholders(WhatsappTemplate $template): int
    {
        $count = 0;
        $components = $template->components ?? [];

        foreach ($components as $component) {
            if (in_array($component['type'], ['BODY', 'HEADER']) && isset($component['text'])) {
                // Regex to find {{number}}
                // Meta uses {{1}}, {{2}}, etc.
                if (preg_match_all('/\{\{\d+\}\}/', $component['text'], $matches)) {
                    $count += count($matches[0]);
                }
            }
        }

        return $count;
    }
}
