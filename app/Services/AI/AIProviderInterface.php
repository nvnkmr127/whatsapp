<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function chat(array $messages, array $options = []): array;
    
    public function testConnection(string $apiKey): bool;
    
    public function getAvailableModels(): array;
    
    public function getName(): string;
}
