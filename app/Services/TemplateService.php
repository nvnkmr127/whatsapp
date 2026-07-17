<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;

class TemplateService
{
    protected $baseUrl;

    protected $team;

    public function __construct(?Team $team = null)
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com').'/'.config('whatsapp.api_version', 'v21.0');
        $this->team = $team;
    }

    /**
     * Sync templates from Meta for a team: fetch all pages, upsert locally,
     * persist readiness scores, and prune templates deleted on Meta's side.
     * Returns the synced template names.
     */
    public function syncTemplates(Team $team): array
    {
        $wabaId = $team->whatsapp_business_account_id;
        $accessToken = (string) $team->whatsapp_access_token;

        if (! $wabaId || ! $accessToken) {
            throw new \Exception("WABA ID or Access Token missing for Team {$team->id}");
        }

        $allRemoteTemplates = [];
        $appId = config('whatsapp.app_id');
        $nextUrl = "{$this->baseUrl}/{$wabaId}/message_templates?limit=100";

        if ($appId) {
            $nextUrl .= "&app_id={$appId}";
        }

        do {
            $response = Http::withToken($accessToken)->get($nextUrl);

            if ($response->failed()) {
                throw new \Exception('Failed to fetch templates: '.$response->body());
            }

            $data = $response->json();
            $allRemoteTemplates = array_merge($allRemoteTemplates, $data['data'] ?? []);

            $nextUrl = $data['paging']['next'] ?? null;

            // Rate limit guard between pages
            if ($nextUrl) {
                sleep(1);
            }
        } while ($nextUrl);

        $syncedNames = [];
        $validator = new \App\Validators\TemplateValidator;

        foreach ($allRemoteTemplates as $remote) {
            $template = WhatsappTemplate::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'name' => $remote['name'],
                    'language' => $remote['language'],
                ],
                [
                    'whatsapp_template_id' => $remote['id'] ?? null,
                    'category' => $remote['category'],
                    'status' => $remote['status'],
                    'components' => $remote['components'] ?? [],
                    'quality_rating' => $remote['quality_rating'] ?? 'UNKNOWN',
                    'is_paused_by_meta' => $remote['is_paused_by_meta'] ?? false,
                ]
            );

            // validate() persists the computed readiness_score itself
            $validator->validate($template);

            $syncedNames[] = $template->name;
        }

        // Prune templates deleted on Meta's side
        if (! empty($syncedNames)) {
            WhatsappTemplate::where('team_id', $team->id)
                ->whereNotIn('name', $syncedNames)
                ->delete();
        }

        return array_unique($syncedNames);
    }

    /**
     * Parse text to find all {{n}} variables.
     * Returns array of unique variable strings e.g. ['{{1}}', '{{2}}']
     */
    public function extractVariables(string $text): array
    {
        if (preg_match_all('/\{\{(\d+)\}\}/', $text, $matches)) {
            return array_unique($matches[0]);
        }

        return [];
    }

    /**
     * Get all placeholders from all components (text, dynamic button URLs).
     */
    public function extractAllPlaceholders(WhatsappTemplate $template): array
    {
        $placeholders = [];
        $components = $template->components;

        if (is_iterable($components)) {
            foreach ($components as $component) {
                if (isset($component['text'])) {
                    $placeholders = array_merge($placeholders, $this->extractVariables($component['text']));
                }

                if ($component['type'] === 'BUTTONS' && isset($component['buttons'])) {
                    foreach ($component['buttons'] as $button) {
                        if (($button['type'] ?? '') === 'URL' && isset($button['url'])) {
                            $placeholders = array_merge($placeholders, $this->extractVariables($button['url']));
                        }
                    }
                }
            }
        }

        return array_unique($placeholders);
    }

    /**
     * Sanitize variables to meet Meta's constraints:
     * nulls to empty strings, arrays flattened, truncated to 1024 chars.
     */
    public function sanitizeVariables(array $variables): array
    {
        return array_map(function ($var) {
            if (is_null($var)) {
                return '';
            }

            if (is_array($var)) {
                $var = (isset($var[0]) && count($var) === 1 && is_string($var[0])) ? $var[0] : json_encode($var);
            }

            return substr((string) $var, 0, 1024);
        }, $variables);
    }
}
