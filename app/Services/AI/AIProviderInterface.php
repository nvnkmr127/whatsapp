<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    public function chat(array $messages, array $options = []): array;

    public function streamChat(array $messages, array $options = []): iterable;

    public function embed(string|array $text, array $options = []): array;

    public function summarize(string $content, array $options = []): string;

    public function classify(string $content, array $categories, array $options = []): string;

    public function testConnection(string $apiKey): bool;

    public function getAvailableModels(): array;

    public function getName(): string;
}
