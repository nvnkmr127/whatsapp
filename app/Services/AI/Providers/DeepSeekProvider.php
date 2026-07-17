<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.deepseek.com';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(array $messages, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? 'deepseek-chat',
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'stream' => false,
        ];

        // DeepSeek is OpenAI-compatible, including response_format
        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                Log::error('DeepSeek API Failed: '.$response->body());
                throw new \Exception('DeepSeek API request failed: '.($response->json('error.message') ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'content' => $response->json('choices.0.message.content'),
                'model' => $response->json('model'),
                'usage' => $response->json('usage'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('DeepSeek Provider Error: '.$e->getMessage());

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
                    'model' => 'deepseek-chat',
                    'messages' => [['role' => 'user', 'content' => 'Hi']],
                    'max_tokens' => 1,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('DeepSeek Connection Test Failed: '.$e->getMessage());

            return false;
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'deepseek-chat' => 'DeepSeek Chat (Standard)',
            'deepseek-reasoner' => 'DeepSeek R1 (Reasoning)',
        ];
    }

    public function getName(): string
    {
        return 'DeepSeek AI';
    }
}
