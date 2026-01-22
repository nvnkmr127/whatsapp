<?php

namespace App\DTOs;

class ValidationError
{
    public function __construct(
        public string $code,
        public string $message,
        public string $severity,
        public ?string $field = null,
        public ?string $suggestion = null,
        public array $metadata = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity,
            'field' => $this->field,
            'suggestion' => $this->suggestion,
            'metadata' => $this->metadata,
        ];
    }

    public function isBlocking(): bool
    {
        return $this->severity === 'error';
    }
}
