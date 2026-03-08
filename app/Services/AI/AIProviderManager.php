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
    ];

    public static function getProvider(?Team $team = null, ?string $providerName = null): ?AIProviderInterface
    {
        if ($team) {
            $teamId = $team->id;
            $provider = $providerName ?? get_setting("ai_provider_{$teamId}", 'openai');
            $apiKey = get_setting("ai_{$provider}_api_key_{$teamId}") ?? env(strtoupper($provider) . '_API_KEY');
        } else {
            $provider = $providerName ?? 'openai';
            $apiKey = env(strtoupper($provider) . '_API_KEY');
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
            'openai' => 'OpenAI (GPT-4, GPT-3.5)',
            'anthropic' => 'Anthropic Claude',
            'gemini' => 'Google Gemini',
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
        $provider = self::getProvider($team);
        
        if (!$provider) {
            return [
                'success' => false,
                'error' => 'No AI provider configured for this team',
            ];
        }

        $teamId = $team->id;
        $model = $options['model'] ?? get_setting("ai_model_{$teamId}", null);
        $temperature = $options['temperature'] ?? (float) get_setting("ai_temperature_{$teamId}", 0.7);

        $options['model'] = $model;
        $options['temperature'] = $temperature;

        return $provider->chat($messages, $options);
    }
}
