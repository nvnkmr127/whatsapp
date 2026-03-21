<?php

namespace App\Services\Email\Contracts;

use App\Enums\EmailUseCase;

interface EmailProviderContract
{
    public function send(EmailPayload $payload): EmailResult;

    public function supports(EmailUseCase $useCase): bool;

    public function getName(): string;

    public function isHealthy(): bool;
}
