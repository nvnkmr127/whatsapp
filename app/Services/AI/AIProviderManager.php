<?php

namespace App\Services\AI;

use App\Models\Team;
use App\Services\AI\Providers\AnthropicProvider;
use App\Services\AI\Providers\DeepSeekProvider;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Support\Facades\Log;

class AIProviderManager
{
    protected const PROVIDERS = [
        'openai' => OpenAIProvider::class,
        'anthropic' => AnthropicProvider::class,
        'gemini' => GeminiProvider::class,
        'deepseek' => DeepSeekProvider::class,
    ];

    public static function getAvailableProviders(): array
    {
        return [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'gemini' => 'Gemini',
            'deepseek' => 'DeepSeek',
        ];
    }

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

        if (! $apiKey) {
            Log::warning("No API key found for AI provider: {$provider}");

            return null;
        }

        if (! isset(self::PROVIDERS[$provider])) {
            Log::error("Unknown AI provider: {$provider}");

            return null;
        }

        $providerClass = self::PROVIDERS[$provider];

        return new $providerClass($apiKey);
    }

    public static function testConnection(string $provider, string $apiKey): bool
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return false;
        }

        $providerClass = self::PROVIDERS[$provider];

        return (new $providerClass($apiKey))->testConnection($apiKey);
    }

    public static function getModelsForProvider(string $provider): array
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return [];
        }

        $providerClass = self::PROVIDERS[$provider];

        return (new $providerClass('temp-key'))->getAvailableModels();
    }

    /**
     * Chat with the team's configured provider, falling back to the
     * team's fallback provider on failure.
     */
    public static function chat(Team $team, array $messages, array $options = []): array
    {
        return self::executeWithFallback($team, $messages, $options);
    }

    /**
     * Single-prompt completion returning a plain string.
     */
    public static function summarize(Team $team, string $content, array $options = []): string
    {
        $result = self::chat($team, [['role' => 'user', 'content' => $content]], array_merge(['temperature' => 0.3], $options));

        return $result['content'] ?? '';
    }

    protected static function executeWithFallback(Team $team, array $messages, array $options): array
    {
        $teamId = $team->id;
        $primaryProviderName = get_setting("ai_provider_{$teamId}", 'openai');

        try {
            return self::execute($team, $primaryProviderName, $messages, $options);
        } catch (\Exception $e) {
            $fallbackProviderName = get_setting("ai_fallback_provider_{$teamId}");

            if ($fallbackProviderName && $fallbackProviderName !== $primaryProviderName) {
                Log::warning("AI Primary Provider ({$primaryProviderName}) failed. Falling back to {$fallbackProviderName}. Error: ".$e->getMessage());
                try {
                    return self::execute($team, $fallbackProviderName, $messages, $options);
                } catch (\Exception $fe) {
                    Log::error("AI Fallback Provider ({$fallbackProviderName}) also failed: ".$fe->getMessage());
                }
            }

            throw $e;
        }
    }

    protected static function execute(Team $team, string $providerName, array $messages, array $options): array
    {
        $provider = self::getProvider($team, $providerName);

        if (! $provider) {
            throw new \Exception("AI provider {$providerName} not configured correctly.");
        }

        if (! isset($options['model'])) {
            $options['model'] = get_setting("ai_model_{$team->id}");
        }

        $result = $provider->chat($messages, $options);

        // Providers swallow HTTP errors into a failure array; surface it so the fallback chain fires
        if (! ($result['success'] ?? false)) {
            throw new \Exception($result['error'] ?? "AI provider {$providerName} request failed.");
        }

        return $result;
    }
}
