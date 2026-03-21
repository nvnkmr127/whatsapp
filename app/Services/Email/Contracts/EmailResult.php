<?php

namespace App\Services\Email\Contracts;

readonly class EmailResult
{
    public function __construct(
        public bool $success,
        public string $providerName,
        public ?string $messageId = null,
        public ?string $error = null
    ) {}

    public static function ok(string $provider, string $messageId): self
    {
        return new self(true, $provider, $messageId);
    }

    public static function fail(string $provider, string $error): self
    {
        return new self(false, $provider, null, $error);
    }
}
