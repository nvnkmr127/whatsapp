<?php

namespace App\DTOs;

class ValidationResult
{
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
        public array $recipientResults = []
    ) {
    }

    public function isValid(): bool
    {
        return empty($this->getBlockingErrors());
    }

    public function canSend(): bool
    {
        return $this->isValid();
    }

    public function getBlockingErrors(): array
    {
        return array_filter($this->errors, fn($error) => $error->isBlocking());
    }

    public function getErrors(): array
    {
        return array_map(fn($error) => $error->toArray(), $this->errors);
    }

    public function getWarnings(): array
    {
        return array_map(fn($warning) => $warning->toArray(), $this->warnings);
    }

    public function getBlockingReason(): string
    {
        $blockingErrors = $this->getBlockingErrors();

        if (empty($blockingErrors)) {
            return '';
        }

        return $blockingErrors[0]->message;
    }

    public function getValidRecipients(): array
    {
        $valid = [];

        foreach ($this->recipientResults as $contactId => $result) {
            if ($result['valid']) {
                $valid[] = $contactId;
            }
        }

        return $valid;
    }

    public function getInvalidRecipients(): array
    {
        $invalid = [];

        foreach ($this->recipientResults as $contactId => $result) {
            if (!$result['valid']) {
                $invalid[$contactId] = $result;
            }
        }

        return $invalid;
    }

    public function getSummary(): array
    {
        $totalRecipients = count($this->recipientResults);
        $validRecipients = count($this->getValidRecipients());

        return [
            'total_recipients' => $totalRecipients,
            'valid_recipients' => $validRecipients,
            'invalid_recipients' => $totalRecipients - $validRecipients,
            'blocking_errors' => count($this->getBlockingErrors()),
            'warnings' => count($this->warnings),
        ];
    }

    public function addError(ValidationError $error): void
    {
        if ($error->severity === 'error') {
            $this->errors[] = $error;
        } else {
            $this->warnings[] = $error;
        }
    }
}
