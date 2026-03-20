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

    public function streamChat(array $messages, array $options = []): iterable
    {
        $result = $this->chat($messages, $options);
        yield $result['content'] ?? '';
    }

    public function embed(string|array $text, array $options = []): array
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->post("{$this->baseUrl}/embeddings", [
                    'model' => $options['model'] ?? 'text-embedding-3-small',
                    'input' => $text,
                ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json('data.0.embedding') ?? [];
        } catch (\Exception $e) {
            Log::error("OpenAI Embed Error: " . $e->getMessage());
            return [];
        }
    }

    public function summarize(string $content, array $options = []): string
    {
        $messages = [
            ['role' => 'system', 'content' => 'Summarize this text concisely.'],
            ['role' => 'user', 'content' => $content]
        ];

        $response = $this->chat($messages, array_merge($options, ['temperature' => 0.3]));
        return $response['content'] ?? '';
    }

    public function classify(string $content, array $categories, array $options = []): string
    {
        $categoriesStr = implode(', ', $categories);
        $messages = [
            ['role' => 'system', 'content' => "Classify into: {$categoriesStr}. Return only category name."],
            ['role' => 'user', 'content' => $content]
        ];

        $response = $this->chat($messages, array_merge($options, ['temperature' => 0]));
        return trim($response['content'] ?? '');
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
            'gpt-5.4-pro' => 'GPT-5.4 Pro (Maximum Intelligence)',
            'gpt-5.4' => 'GPT-5.4 (Balanced High-Performance)',
            'gpt-5.4-thinking' => 'GPT-5.4 Thinking (Deep Reasoning)',
            'gpt-5.4-mini' => 'GPT-5.4 Mini (Fast/Efficient)',
            'gpt-5.4-nano' => 'GPT-5.4 Nano (Ultra-Fast/Mobile)',
            'o1' => 'o1 (Classic Reasoning)',
            'gpt-4o' => 'GPT-4o (Legacy Pro)',
        ];
    }

    public function getName(): string
    {
        return 'OpenAI';
    }
}
