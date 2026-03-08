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
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 8192;

        $contents = [];
        foreach ($messages as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $message['content']]]
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ]
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", $payload);

            if ($response->failed()) {
                Log::error("Gemini API Failed: " . $response->body());
                throw new \Exception("Gemini API request failed: " . ($response->json('error.message') ?? 'Unknown error'));
            }

            $content = $response->json('candidates.0.content.parts.0.text');

            return [
                'success' => true,
                'content' => $content,
                'model' => $model,
                'usage' => $response->json('usageMetadata'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("Gemini Provider Error: " . $e->getMessage());
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
                        ['role' => 'user', 'parts' => [['text' => 'Hello']]]
                    ]
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Gemini Connection Test Failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'gemini-1.5-pro' => 'Gemini 1.5 Pro (Smartest)',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fastest)',
            'gemini-1.0-pro' => 'Gemini 1.0 Pro (Previous Gen)',
        ];
    }

    public function getName(): string
    {
        return 'Google Gemini';
    }
}
