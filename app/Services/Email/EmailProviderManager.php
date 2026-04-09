<?php

namespace App\Services\Email;

use App\Enums\EmailUseCase;
use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\Contracts\EmailProviderContract;
use App\Services\Email\Contracts\EmailResult;
use InvalidArgumentException;

class EmailProviderManager
{
    /**
     * @param  array<string, EmailProviderContract>  $drivers
     */
    public function __construct(
        protected array $drivers
    ) {}

    /**
     * Get a driver by name.
     */
    public function driver(string $name): EmailProviderContract
    {
        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Email driver [{$name}] is not registered.");
        }

        return $this->drivers[$name];
    }

    /**
     * Resolve the right driver for a given use case.
     */
    public function forUseCase(EmailUseCase $useCase): EmailProviderContract
    {
        // 1. Check specific routing for this use case
        $name = config('mail.routing.'.$useCase->value);

        // 2. Fall back to global default provider
        if (! $name) {
            $name = config('mail.provider', 'smtp');
        }

        try {
            $driver = $this->driver($name);

            // 3. Verify driver supports the use case, otherwise fall back to SMTP
            if ($driver->supports($useCase)) {
                return $driver;
            }
        } catch (InvalidArgumentException $e) {
            // Fall through to SMTP if driver not found
        }

        return $this->driver('smtp');
    }

    /**
     * Send an email using the best driver for the payload's use case.
     */
    public function send(EmailPayload $payload): EmailResult
    {
        return $this->forUseCase($payload->useCase)->send($payload);
    }

    /**
     * Get list of all registered driver names.
     */
    public function getAvailableDrivers(): array
    {
        return array_keys($this->drivers);
    }
}
