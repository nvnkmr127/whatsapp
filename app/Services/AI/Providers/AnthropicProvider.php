<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.anthropic.com/v1';

    protected string $apiVersion = '2023-06-01';

    protected const DEFAULT_MODEL = 'claude-opus-4-8';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? self::DEFAULT_MODEL;
        $maxTokens = $options['max_tokens'] ?? 4096;

        $systemMessage = '';
        $formattedMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMessage = $message['content'];
            } else {
                $formattedMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content'],
                ];
            }
        }

        $payload = [
            'model' => $model,
            'messages' => $formattedMessages,
            'max_tokens' => $maxTokens,
        ];

        if ($systemMessage) {
            $payload['system'] = $systemMessage;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
            ])
                ->timeout(120)
                ->post("{$this->baseUrl}/messages", $payload);

            if ($response->failed()) {
                Log::error('Anthropic API Failed: '.$response->body());
                throw new \Exception('Anthropic API request failed: '.($response->json('error.message') ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'content' => $response->json('content.0.text'),
                'model' => $response->json('model'),
                'usage' => $response->json('usage'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Anthropic Provider Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function testConnection(string $apiKey): bool
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->post("{$this->baseUrl}/messages", [
                    'model' => 'claude-haiku-4-5',
                    'messages' => [['role' => 'user', 'content' => 'Hello']],
                    'max_tokens' => 10,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Anthropic Connection Test Failed: '.$e->getMessage());

            return false;
        }
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-opus-4-8' => 'Claude Opus 4.8 (Most Capable)',
            'claude-sonnet-5' => 'Claude Sonnet 5 (Fast & Intelligent)',
            'claude-haiku-4-5' => 'Claude Haiku 4.5 (Fastest/Cheapest)',
        ];
    }

    public function getName(): string
    {
        return 'Anthropic Claude';
    }
}
