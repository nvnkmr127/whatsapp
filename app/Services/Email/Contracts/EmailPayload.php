<?php

namespace App\Services\Email\Contracts;

use App\Enums\EmailUseCase;

readonly class EmailPayload
{
    public function __construct(
        public string $to,
        public string $subject,
        public string $html,
        public ?string $text = null,
        public EmailUseCase $useCase = EmailUseCase::NOTIFICATION,
        public array $metadata = [],
        public ?string $replyTo = null,
        public array $headers = []
    ) {}
}
