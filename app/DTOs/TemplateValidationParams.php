<?php

namespace App\DTOs;

class TemplateValidationParams
{
    public function __construct(
        public ?string $headerMediaUrl = null,
        public array $bodyVariables = [],
        public array $footerVariables = [],
        public array $buttonVariables = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            headerMediaUrl: $data['header_media_url'] ?? $data['header_url'] ?? null,
            bodyVariables: $data['body_variables'] ?? $data['variables'] ?? [],
            footerVariables: $data['footer_variables'] ?? [],
            buttonVariables: $data['button_variables'] ?? []
        );
    }
}
