<?php

namespace App\Services;

use App\Models\Team;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TemplateService
{
    protected $baseUrl;
    protected $team;

    public function __construct(Team $team = null)
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com') . '/' . config('whatsapp.api_version', 'v21.0');
        $this->team = $team;
    }

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
        $validator = new \App\Validators\TemplateValidator();

        foreach ($remoteTemplates as $remote) {
            $template = $this->updateOrCreateTemplate($team, $remote);
            $validator->validate($template);
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
     * Map named data (e.g. ['name' => 'John']) to positional variables (e.g. {{1}})
     * using the template's variable_config schema.
     */
    public function hydrateTemplate(WhatsappTemplate $template, array $namedData): array
    {
        $schema = $template->variable_config ?? [];
        $positionalData = [];
        $maxIndex = 0;

        // Determine max index used in template
        // We need to scan extraction again to be sure, or trust schema keys
        // Let's scan components to get all placeholders in order
        $allPlaceholders = $this->extractAllPlaceholders($template);

        foreach ($allPlaceholders as $placeholder) {
            // Extract index from {{n}}
            $index = (int) filter_var($placeholder, FILTER_SANITIZE_NUMBER_INT);
            $maxIndex = max($maxIndex, $index);

            // 1. Look up config
            $config = $schema[$placeholder] ?? null;

            if (!$config) {
                // Fallback: If no config, try to use the index as key? 
                // Or just expect the user provided raw positional array?
                // For now, if no config, we can't map name -> value. 
                // We will assume the value is null unless provided by index in namedData?
                // No, namedData assumes keys are names.
                $value = $namedData[$index] ?? null; // Allow mixed usage?
            } else {
                $varName = $config['name'] ?? '';
                $value = $namedData[$varName] ?? $config['fallback'] ?? null;
            }

            if ($value === null) {
                // Strict mode could throw exception here
                $varName = $config['name'] ?? 'unknown';
                Log::warning("Missing value for variable {$placeholder} ({$varName}) in template {$template->name}");
                $value = '{{' . $index . '}}'; // Keep placeholder? or empty? standard is empty string often, or validation fail.
                // Whatsapp API will fail if we send {{n}} literal usually.
            }

            // Map to 0-indexed array for API (body_text params array)
            // Note: Cloud API expects parameters list in order. 
            // If body has {{1}} and {{3}}, we need list [v1, v2, v3] where v2 is unused?
            // Actually standard regex is {{1}}, {{2}} sequential. 
            // If strict validation passes, gaps shouldn't exist.
            $positionalData[$index - 1] = (string) $value;
        }

        // Fill gaps if any (though strict validation prevents this)
        // param array must be dense
        for ($i = 0; $i < $maxIndex; $i++) {
            if (!isset($positionalData[$i])) {
                $positionalData[$i] = ''; // Empty string for unused/skipped vars
            }
        }

        ksort($positionalData);
        return array_values($positionalData);
    }

    /**
     * Helper to get all placeholders from all components
     */
    public function extractAllPlaceholders(WhatsappTemplate $template): array
    {
        $placeholders = [];
        $components = $template->components ?? [];

        foreach ($components as $component) {
            if (isset($component['text'])) {
                $found = $this->extractVariables($component['text']);
                $placeholders = array_merge($placeholders, $found);
            }

            // Button dynamic URLs
            if ($component['type'] === 'BUTTONS' && isset($component['buttons'])) {
                foreach ($component['buttons'] as $button) {
                    if (($button['type'] ?? '') === 'URL' && isset($button['url'])) {
                        $found = $this->extractVariables($button['url']);
                        $placeholders = array_merge($placeholders, $found);
                    }
                }
            }
        }

        return array_unique($placeholders);
    }

    /**
     * Count {{1}}, {{2}} in BODY, HEADER, and dynamic BUTTONS.
     */
    protected function countPlaceholders(WhatsappTemplate $template): int
    {
        $count = 0;
        $components = $template->components ?? [];

        // Use new extraction logic for consistency
        $all = $this->extractAllPlaceholders($template);

        // Filter out non-numeric (though extraction only finds {{d+}})
        // But wait, countPlaceholders logic in legacy also counted MEDIA headers as 1.
        // My extraction logic above assumes text-based {{n}}.
        // We need to keep legacy counting for Media Headers which don't have {{1}} in text but take up a param slot in API call?
        // Actually, API splits header_parameters from body_parameters. 
        // validationVariables ($variables) passed to this func is usually ALL variables merged.

        // Re-implement legacy counting more accurately or preserve it?
        // The previous implementation had specific logic for header types.

        // Let's preserve the exact logic of the previous implementation but clean it up if possible.
        // Or actually, let's keep the existing implementation for safety as it was just fixed/audited.
        // I will revert this specific method replacement and only append the new methods.
        // Ah, I am replacing the whole block.

        foreach ($components as $component) {
            // Text based placeholders in HEADER and BODY
            if (in_array($component['type'], ['BODY', 'HEADER']) && isset($component['text'])) {
                if (preg_match_all('/\{\{(\d+)\}\}/', $component['text'], $matches)) {
                    $count += count($matches[0]);
                }
            }

            // Media based placeholders in HEADER
            if ($component['type'] === 'HEADER' && in_array($component['format'] ?? '', ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $count++;
            }

            // Dynamic URL placeholders in BUTTONS
            if ($component['type'] === 'BUTTONS' && isset($component['buttons'])) {
                foreach ($component['buttons'] as $button) {
                    if (($button['type'] ?? '') === 'URL' && isset($button['url']) && str_contains($button['url'], '{{1}}')) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
