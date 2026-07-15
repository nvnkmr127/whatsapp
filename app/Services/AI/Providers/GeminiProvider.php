<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'gemini-1.5-pro';

        $contents = [];
        foreach ($messages as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $message['content']]],
            ];
        }

        $generationConfig = [
            'temperature' => $options['temperature'] ?? 0.7,
            'maxOutputTokens' => $options['max_tokens'] ?? 8192,
        ];

        // Map the OpenAI-style json_object option to Gemini's native equivalent
        if (($options['response_format']['type'] ?? null) === 'json_object') {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => $generationConfig,
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", $payload);

            if ($response->failed()) {
                Log::error('Gemini API Failed: '.$response->body());
                throw new \Exception('Gemini API request failed: '.($response->json('error.message') ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'content' => $response->json('candidates.0.content.parts.0.text'),
                'model' => $model,
                'usage' => $response->json('usageMetadata'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Gemini Provider Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function testConnection(string $apiKey): bool
    {
        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => 'Hello']]],
                    ],
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Gemini Connection Test Failed: '.$e->getMessage());

            return false;
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'gemini-3.1-pro' => 'Gemini 3.1 Pro (Ultimate Reasoning)',
            'gemini-3.1-flash' => 'Gemini 3.1 Flash (Fast & Capable)',
            'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash-Lite (Scalable)',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Legacy)',
        ];
    }

    public function getName(): string
    {
        return 'Google Gemini';
    }
}
