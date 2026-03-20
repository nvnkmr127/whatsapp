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
        $model = $options['model'] ?? 'deepseek-chat';
        $temperature = $options['temperature'] ?? 0.7;

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'stream' => false,
        ];

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                Log::error("DeepSeek API Failed: " . $response->body());
                throw new \Exception("DeepSeek API request failed: " . ($response->json('error.message') ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'content' => $response->json('choices.0.message.content'),
                'model' => $response->json('model'),
                'usage' => $response->json('usage'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("DeepSeek Provider Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function streamChat(array $messages, array $options = []): iterable
    {
        // Placeholder for streaming - in a real app this would use a streaming HTTP client
        // DeepSeek supports streaming at the same endpoint with 'stream' => true
        $result = $this->chat($messages, $options);
        yield $result['content'] ?? '';
    }

    public function embed(string|array $text, array $options = []): array
    {
        // DeepSeek does not currently provide an embeddings endpoint
        Log::warning("DeepSeek does not support embeddings. Returning empty vector.");
        return [];
    }

    public function summarize(string $content, array $options = []): string
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant that summarizes text concisely.'],
            ['role' => 'user', 'content' => "Please summarize the following text:\n\n{$content}"]
        ];

        $response = $this->chat($messages, array_merge($options, ['temperature' => 0.3]));

        return $response['content'] ?? '';
    }

    public function classify(string $content, array $categories, array $options = []): string
    {
        $categoriesStr = implode(', ', $categories);
        $messages = [
            ['role' => 'system', 'content' => "You are a classifier. Classify the input into one of these categories: {$categoriesStr}. Return only the category name."],
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
                    'model' => 'deepseek-chat',
                    'messages' => [['role' => 'user', 'content' => 'Hi']],
                    'max_tokens' => 1
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("DeepSeek Connection Test Failed: " . $e->getMessage());
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
