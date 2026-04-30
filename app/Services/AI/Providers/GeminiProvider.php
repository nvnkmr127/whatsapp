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
                'parts' => [['text' => $message['content']]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}", $payload);

            if ($response->failed()) {
                Log::error('Gemini API Failed: '.$response->body());
                throw new \Exception('Gemini API request failed: '.($response->json('error.message') ?? 'Unknown error'));
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
            Log::error('Gemini Provider Error: '.$e->getMessage());

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
            $model = $options['model'] ?? 'text-embedding-004';
            $response = Http::timeout(20)
                ->post("{$this->baseUrl}/models/{$model}:embedContent?key={$this->apiKey}", [
                    'model' => "models/{$model}",
                    'content' => [
                        'parts' => [['text' => is_array($text) ? implode("\n", $text) : $text]],
                    ],
                ]);

            if ($response->failed()) {
                return [];
            }

            return $response->json('embedding.values') ?? [];
        } catch (\Exception $e) {
            Log::error('Gemini Embed Error: '.$e->getMessage());

            return [];
        }
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

    public function transcribe(string $filePath, array $options = []): string
    {
        return ''; // Not supported by Gemini in this implementation
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
