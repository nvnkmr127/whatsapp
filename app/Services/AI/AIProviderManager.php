<?php

namespace App\Services\AI;

use App\Services\AI\Providers\OpenAIProvider;
use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class AIProviderManager
{
    protected const PROVIDERS = [
        'openai' => OpenAIProvider::class,
        'anthropic' => AnthropicProvider::class,
        'gemini' => GeminiProvider::class,
        'deepseek' => \App\Services\AI\Providers\DeepSeekProvider::class,
    ];

    public static function getProvider(?Team $team = null, ?string $providerName = null): ?AIProviderInterface
    {
        if ($team) {
            $teamId = $team->id;
            $provider = $providerName ?? get_setting("ai_provider_{$teamId}", 'openai');
            $apiKey = get_setting("ai_{$provider}_api_key_{$teamId}") ?? config("services.ai.{$provider}.api_key");
        } else {
            $provider = $providerName ?? 'openai';
            $apiKey = config("services.ai.{$provider}.api_key");
        }

        if (!$apiKey) {
            Log::warning("No API key found for AI provider: {$provider}");
            return null;
        }

        if (!isset(self::PROVIDERS[$provider])) {
            Log::error("Unknown AI provider: {$provider}");
            return null;
        }

        $providerClass = self::PROVIDERS[$provider];
        return new $providerClass($apiKey);
    }

    public static function getAvailableProviders(): array
    {
        return [
            'openai' => 'OpenAI (GPT-4o, o1)',
            'anthropic' => 'Anthropic Claude 3.5',
            'gemini' => 'Google Gemini 1.5/2.0',
            'deepseek' => 'DeepSeek AI',
        ];
    }

    public static function testConnection(string $provider, string $apiKey): bool
    {
        if (!isset(self::PROVIDERS[$provider])) {
            return false;
        }

        $providerClass = self::PROVIDERS[$provider];
        $providerInstance = new $providerClass($apiKey);
        
        return $providerInstance->testConnection($apiKey);
    }

    public static function getModelsForProvider(string $provider): array
    {
        if (!isset(self::PROVIDERS[$provider])) {
            return [];
        }

        $providerClass = self::PROVIDERS[$provider];
        $tempInstance = new $providerClass('temp-key');
        
        return $tempInstance->getAvailableModels();
    }

    public static function chat(Team $team, array $messages, array $options = []): array
    {
        return self::executeWithFallback($team, 'chat', [$messages, $options]);
    }

    public static function embed(Team $team, string|array $text, array $options = []): array
    {
        return self::executeWithFallback($team, 'embed', [$text, $options]);
    }

    public static function summarize(Team $team, string $content, array $options = []): string
    {
        $result = self::executeWithFallback($team, 'summarize', [$content, $options]);
        return is_string($result) ? $result : ($result['content'] ?? '');
    }

    public static function classify(Team $team, string $content, array $categories, array $options = []): string
    {
        $result = self::executeWithFallback($team, 'classify', [$content, $categories, $options]);
        return is_string($result) ? $result : ($result['content'] ?? '');
    }

    protected static function executeWithFallback(Team $team, string $method, array $args): mixed
    {
        $teamId = $team->id;
        $primaryProviderName = get_setting("ai_provider_{$teamId}", 'openai');
        
        try {
            return self::execute($team, $primaryProviderName, $method, $args);
        } catch (\Exception $e) {
            $fallbackProviderName = get_setting("ai_fallback_provider_{$teamId}");
            
            if ($fallbackProviderName && $fallbackProviderName !== $primaryProviderName) {
                Log::warning("AI Primary Provider ({$primaryProviderName}) failed. Falling back to {$fallbackProviderName}. Error: " . $e->getMessage());
                try {
                    return self::execute($team, $fallbackProviderName, $method, $args);
                } catch (\Exception $fe) {
                    Log::error("AI Fallback Provider ({$fallbackProviderName}) also failed: " . $fe->getMessage());
                }
            }
            
            throw $e;
        }
    }

    protected static function execute(Team $team, string $providerName, string $method, array $args): mixed
    {
        $provider = self::getProvider($team, $providerName);
        
        if (!$provider) {
            throw new \Exception("AI provider {$providerName} not configured correctly.");
        }

        // Apply team-specific model if not provided in args
        if ($method === 'chat' || $method === 'streamChat') {
            $options = $args[1] ?? [];
            if (!isset($options['model'])) {
                $options['model'] = get_setting("ai_model_{$team->id}");
            }
            $args[1] = $options;
        }

        $result = $provider->$method(...$args);
        
        // Log usage if successful and returns usage data
        if (is_array($result) && isset($result['usage'])) {
            self::logUsage($team, $providerName, $result, $method);
        }

        return $result;
    }

    protected static function logUsage(Team $team, string $provider, array $result, string $type): void
    {
        try {
            $usage = $result['usage'] ?? [];
            $model = $result['model'] ?? 'unknown';
            
            // Normalize tokens
            $promptTokens = $usage['prompt_tokens'] ?? $usage['promptTokenCount'] ?? $usage['input_tokens'] ?? 0;
            $completionTokens = $usage['completion_tokens'] ?? $usage['candidatesTokenCount'] ?? $usage['output_tokens'] ?? 0;
            $totalTokens = $usage['total_tokens'] ?? $usage['totalTokenCount'] ?? ($promptTokens + $completionTokens);

            \App\Models\AiUsageLog::create([
                'team_id' => $team->id,
                'provider' => $provider,
                'model' => $model,
                'type' => $type,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'metadata' => [
                    'options' => $result['options'] ?? [],
                    'request_id' => $result['raw_response']['id'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log AI usage: " . $e->getMessage());
        }
    }
}
