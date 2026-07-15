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
        $payload = [
            'model' => $options['model'] ?? 'gpt-4o',
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                Log::error('OpenAI API Failed: '.$response->body());
                throw new \Exception('OpenAI API request failed: '.($response->json('error.message') ?? 'Unknown error'));
            }

            return [
                'success' => true,
                'content' => $response->json('choices.0.message.content'),
                'model' => $response->json('model'),
                'usage' => $response->json('usage'),
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('OpenAI Provider Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Whisper transcription — OpenAI-only capability, not part of AIProviderInterface.
     */
    public function transcribe(string $filePath, array $options = []): string
    {
        if (! file_exists($filePath)) {
            Log::error("Whisper transcription failed: File not found at {$filePath}");

            return '';
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->attach('file', fopen($filePath, 'r'), basename($filePath))
                ->post("{$this->baseUrl}/audio/transcriptions", [
                    'model' => $options['model'] ?? 'whisper-1',
                    'language' => $options['language'] ?? null,
                    'prompt' => $options['prompt'] ?? null,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI Whisper Failed: '.$response->body());

                return '';
            }

            return $response->json('text') ?? '';
        } catch (\Exception $e) {
            Log::error('OpenAI Whisper Error: '.$e->getMessage());

            return '';
        }
    }

    public function testConnection(string $apiKey): bool
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => 'Hello']],
                    'max_tokens' => 5,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('OpenAI Connection Test Failed: '.$e->getMessage());

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
