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

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'claude-3-5-sonnet-20241022';
        $temperature = $options['temperature'] ?? 0.7;
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
            'temperature' => $temperature,
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
                ->timeout(30)
                ->post("{$this->baseUrl}/messages", $payload);

            if ($response->failed()) {
                Log::error('Anthropic API Failed: '.$response->body());
                throw new \Exception('Anthropic API request failed: '.($response->json('error.message') ?? 'Unknown error'));
            }

            $content = $response->json('content.0.text');

            return [
                'success' => true,
                'content' => $content,
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

    public function streamChat(array $messages, array $options = []): iterable
    {
        $result = $this->chat($messages, $options);
        yield $result['content'] ?? '';
    }

    public function embed(string|array $text, array $options = []): array
    {
        Log::warning('Anthropic does not support embeddings.');

        return [];
    }

    public function summarize(string $content, array $options = []): string
    {
        $messages = [
            ['role' => 'user', 'content' => "Summarize this: {$content}"],
        ];

        $response = $this->chat($messages, array_merge($options, ['temperature' => 0.3]));

        return $response['content'] ?? '';
    }

    public function classify(string $content, array $categories, array $options = []): string
    {
        $categoriesStr = implode(', ', $categories);
        $messages = [
            ['role' => 'user', 'content' => "Classify into {$categoriesStr}. Return only the label: {$content}"],
        ];

        $response = $this->chat($messages, array_merge($options, ['temperature' => 0]));

        return trim($response['content'] ?? '');
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
                    'model' => 'claude-3-5-sonnet-20241022',
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
            'claude-4-6-opus' => 'Claude 4.6 Opus (Best Coding/Agentic)',
            'claude-4-6-sonnet' => 'Claude 4.6 Sonnet (Highly Intelligent)',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Legacy)',
        ];
    }

    public function getName(): string
    {
        return 'Anthropic Claude';
    }
}
