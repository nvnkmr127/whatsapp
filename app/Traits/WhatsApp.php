<?php

namespace App\Traits;

use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WhatsApp
{
    protected static string $facebookAPI = 'https://graph.facebook.com/';

    protected static function getApiVersion(): string
    {
        return 'v18.0'; // Default valid version
    }

    protected static function getBaseUrl(): string
    {
        return self::$facebookAPI . self::getApiVersion() . '/';
    }

    private function getToken(): string
    {
        // Ideally fetch from DB settings
        return config('services.whatsapp.access_token') ?? '';
        // For now, returning empty string or mocking. 
        // Real implementation needs to fetch from Settings table if standard.
    }

    private function getAccountID(): string
    {
        return config('services.whatsapp.account_id') ?? '';
    }

    public function loadTemplatesFromWhatsApp(): array
    {
        try {
            // Mocking or fetching settings
            $accountId = $this->getAccountID();
            $token = $this->getToken();

            if (empty($accountId) || empty($token)) {
                // Return empty or throw generic if not configured
                // But for MVP, we might want to fail gracefully.
            }

            $response = Http::get(self::getBaseUrl() . "{$accountId}/", [
                'fields' => 'id,name,message_templates,phone_numbers',
                'access_token' => $token,
            ]);

            if ($response->failed()) {
                throw new \Exception($response->json('error.message'));
            }

            $messageTemplates = $response->json('message_templates.data');
            if (!$messageTemplates) {
                return ['status' => false, 'message' => 'No templates found.'];
            }

            $existingTemplateIds = WhatsappTemplate::pluck('template_id')->toArray();
            $apiTemplateIds = [];

            foreach ($messageTemplates as $templateData) {
                $apiTemplateIds[] = $templateData['id'];

                // Simplify component logic for immediate port
                $components = [];
                $headerParamsCount = $bodyParamsCount = $footerParamsCount = 0;

                foreach ($templateData['components'] as $component) {
                    if ($component['type'] === 'HEADER') {
                        $components['HEADER'] = $component['text'] ?? ($component['format'] ?? '');
                        if (isset($component['text'])) {
                            $headerParamsCount = preg_match_all('/{{(.*?)}}/i', $component['text'], $matches);
                        }
                    }
                    if ($component['type'] === 'BODY') {
                        $components['BODY'] = $component['text'];
                        $bodyParamsCount = preg_match_all('/{{(.*?)}}/i', $component['text'], $matches);
                    }
                    if ($component['type'] === 'FOOTER') {
                        $components['FOOTER'] = $component['text'];
                        $footerParamsCount = preg_match_all('/{{(.*?)}}/i', $component['text'], $matches);
                    }
                }

                WhatsappTemplate::updateOrCreate(
                    ['template_id' => $templateData['id']],
                    [
                        'template_name' => $templateData['name'],
                        'language' => $templateData['language'],
                        'status' => $templateData['status'],
                        'category' => $templateData['category'],
                        'header_data_text' => $components['HEADER'] ?? null,
                        'body_data' => $components['BODY'] ?? null,
                        'footer_data' => $components['FOOTER'] ?? null,
                        'header_params_count' => $headerParamsCount,
                        'body_params_count' => $bodyParamsCount,
                        'footer_params_count' => $footerParamsCount,
                    ]
                );
            }

            return [
                'status' => true,
                'message' => 'Templates synced successfully',
                'count' => count($apiTemplateIds)
            ];

        } catch (\Throwable $e) {
            Log::error("WhatsApp Template Sync Error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function getPhoneNumberDetails(string $phoneNumberId): array
    {
        try {
            $token = $this->getToken();

            if (empty($token)) {
                return ['status' => false, 'message' => 'Access token not configured.'];
            }

            $response = Http::get(self::getBaseUrl() . "{$phoneNumberId}", [
                'fields' => 'display_phone_number,verified_name,quality_rating,messaging_limit_tier',
                'access_token' => $token,
            ]);

            if ($response->failed()) {
                return ['status' => false, 'message' => $response->json('error.message') ?? 'API Error'];
            }

            $data = $response->json();

            return [
                'status' => true,
                'data' => [
                    'display_phone_number' => $data['display_phone_number'] ?? null,
                    'verified_name' => $data['verified_name'] ?? null,
                    'quality_rating' => $data['quality_rating'] ?? null,
                    'messaging_limit_tier' => $data['messaging_limit_tier'] ?? null,
                ]
            ];

        } catch (\Throwable $e) {
            Log::error("WhatsApp Phone Details Error: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
