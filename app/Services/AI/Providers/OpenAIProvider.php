<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements AIProviderInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'gpt-4o';
        $temperature = $options['temperature'] ?? 0.7;
        $responseFormat = $options['response_format'] ?? null;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ];

        if ($responseFormat) {
            $payload['response_format'] = $responseFormat;
        }

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                Log::error("OpenAI API Failed: " . $response->body());
                throw new \Exception("OpenAI API request failed: " . ($response->json('error.message') ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'content' => $response->json('choices.0.message.content'),
                'model' => $response->json('model'),
                'usage' => $response->json('usage'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("OpenAI Provider Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function testConnection(string $apiKey): bool
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [['role' => 'user', 'content' => 'Hello']],
                    'max_tokens' => 5
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("OpenAI Connection Test Failed: " . $e->getMessage());
            return false;
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-4o' => 'GPT-4o (Smartest)',
            'gpt-4o-mini' => 'GPT-4o Mini (Faster)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Previous Gen)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fastest/Cheapest)',
        ];
    }

    public function getName(): string
    {
        return 'OpenAI';
    }
}
