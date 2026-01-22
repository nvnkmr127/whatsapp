<?php

namespace App\Enums;

enum WhatsAppSetupState: string
{
    case NOT_CONFIGURED = 'NOT_CONFIGURED';
    case AUTHENTICATING = 'AUTHENTICATING';
    case TOKEN_EXCHANGE = 'TOKEN_EXCHANGE';
    case VALIDATING_CREDENTIALS = 'VALIDATING_CREDENTIALS';
    case FETCHING_ACCOUNT_INFO = 'FETCHING_ACCOUNT_INFO';
    case PHONE_SELECTION = 'PHONE_SELECTION';
    case PHONE_VALIDATION = 'PHONE_VALIDATION';
    case PHONE_REGISTRATION = 'PHONE_REGISTRATION';
    case SYNCING_TEMPLATES = 'SYNCING_TEMPLATES';
    case VERIFYING_SETUP = 'VERIFYING_SETUP';
    case ACTIVE = 'ACTIVE';
    case DEGRADED = 'DEGRADED';
    case SUSPENDED = 'SUSPENDED';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';

    // Error states
    case AUTH_FAILED = 'AUTH_FAILED';
    case TOKEN_EXCHANGE_FAILED = 'TOKEN_EXCHANGE_FAILED';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case ACCOUNT_FETCH_FAILED = 'ACCOUNT_FETCH_FAILED';
    case NO_PHONES_AVAILABLE = 'NO_PHONES_AVAILABLE';
    case PHONE_MISMATCH = 'PHONE_MISMATCH';
    case REGISTRATION_FAILED = 'REGISTRATION_FAILED';
    case SYNC_FAILED = 'SYNC_FAILED';
    case PARTIAL_SETUP = 'PARTIAL_SETUP';
    case PHONE_RESTRICTED = 'PHONE_RESTRICTED';
    case REFRESHING_TOKEN = 'REFRESHING_TOKEN';

    /**
     * Get allowed transitions from this state
     */
    public function getAllowedTransitions(): array
    {
        return match ($this) {
            self::NOT_CONFIGURED => [
                self::AUTHENTICATING,
            ],
            self::AUTHENTICATING => [
                self::TOKEN_EXCHANGE,
                self::AUTH_FAILED,
            ],
            self::AUTH_FAILED => [
                self::NOT_CONFIGURED,
                self::AUTHENTICATING,
            ],
            self::TOKEN_EXCHANGE => [
                self::VALIDATING_CREDENTIALS,
                self::TOKEN_EXCHANGE_FAILED,
            ],
            self::TOKEN_EXCHANGE_FAILED => [
                self::NOT_CONFIGURED,
                self::TOKEN_EXCHANGE,
            ],
            self::VALIDATING_CREDENTIALS => [
                self::FETCHING_ACCOUNT_INFO,
                self::INVALID_CREDENTIALS,
            ],
            self::INVALID_CREDENTIALS => [
                self::NOT_CONFIGURED,
            ],
            self::FETCHING_ACCOUNT_INFO => [
                self::PHONE_SELECTION,
                self::ACCOUNT_FETCH_FAILED,
            ],
            self::ACCOUNT_FETCH_FAILED => [
                self::VALIDATING_CREDENTIALS,
                self::NOT_CONFIGURED,
            ],
            self::PHONE_SELECTION => [
                self::PHONE_VALIDATION,
                self::NO_PHONES_AVAILABLE,
            ],
            self::NO_PHONES_AVAILABLE => [
                self::NOT_CONFIGURED,
            ],
            self::PHONE_VALIDATION => [
                self::PHONE_REGISTRATION,
                self::SYNCING_TEMPLATES,
                self::PHONE_MISMATCH,
            ],
            self::PHONE_MISMATCH => [
                self::PHONE_SELECTION,
                self::NOT_CONFIGURED,
            ],
            self::PHONE_REGISTRATION => [
                self::SYNCING_TEMPLATES,
                self::REGISTRATION_FAILED,
            ],
            self::REGISTRATION_FAILED => [
                self::PHONE_VALIDATION,
                self::NOT_CONFIGURED,
            ],
            self::SYNCING_TEMPLATES => [
                self::VERIFYING_SETUP,
                self::SYNC_FAILED,
            ],
            self::SYNC_FAILED => [
                self::SYNCING_TEMPLATES,
                self::VERIFYING_SETUP, // Allow partial
                self::NOT_CONFIGURED,
            ],
            self::VERIFYING_SETUP => [
                self::ACTIVE,
                self::PARTIAL_SETUP,
            ],
            self::PARTIAL_SETUP => [
                self::VERIFYING_SETUP,
                self::ACTIVE,
            ],
            self::ACTIVE => [
                self::DEGRADED,
                self::SUSPENDED,
                self::TOKEN_EXPIRED,
                self::PHONE_RESTRICTED,
                self::NOT_CONFIGURED, // Disconnect
            ],
            self::DEGRADED => [
                self::ACTIVE,
                self::SUSPENDED,
                self::TOKEN_EXPIRED,
            ],
            self::SUSPENDED => [
                self::DEGRADED,
                self::NOT_CONFIGURED,
            ],
            self::TOKEN_EXPIRED => [
                self::REFRESHING_TOKEN,
                self::NOT_CONFIGURED,
            ],
            self::REFRESHING_TOKEN => [
                self::ACTIVE,
                self::NOT_CONFIGURED,
            ],
            self::PHONE_RESTRICTED => [
                self::ACTIVE,
                self::SUSPENDED,
            ],
        };
    }

    /**
     * Get timeout duration in seconds for this state
     */
    public function getTimeout(): int
    {
        return match ($this) {
            self::AUTHENTICATING => 300, // 5 minutes
            self::TOKEN_EXCHANGE => 60,
            self::VALIDATING_CREDENTIALS => 30,
            self::FETCHING_ACCOUNT_INFO => 60,
            self::PHONE_SELECTION => 600, // 10 minutes (user action)
            self::PHONE_VALIDATION => 30,
            self::PHONE_REGISTRATION => 120,
            self::SYNCING_TEMPLATES => 120,
            self::VERIFYING_SETUP => 60,
            self::REFRESHING_TOKEN => 60,
            default => 0, // No timeout for terminal/error states
        };
    }

    /**
     * Check if this is a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::DEGRADED,
            self::SUSPENDED,
        ]);
    }

    /**
     * Check if this is an error state
     */
    public function isError(): bool
    {
        return in_array($this, [
            self::AUTH_FAILED,
            self::TOKEN_EXCHANGE_FAILED,
            self::INVALID_CREDENTIALS,
            self::ACCOUNT_FETCH_FAILED,
            self::NO_PHONES_AVAILABLE,
            self::PHONE_MISMATCH,
            self::REGISTRATION_FAILED,
            self::SYNC_FAILED,
        ]);
    }

    /**
     * Check if retry is allowed from this state
     */
    public function canRetry(): bool
    {
        return $this->isError() && !in_array($this, [
            self::NO_PHONES_AVAILABLE,
            self::INVALID_CREDENTIALS,
        ]);
    }

    /**
     * Get user-friendly label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_CONFIGURED => 'Not Connected',
            self::AUTHENTICATING => 'Authenticating...',
            self::TOKEN_EXCHANGE => 'Exchanging Token...',
            self::VALIDATING_CREDENTIALS => 'Validating Credentials...',
            self::FETCHING_ACCOUNT_INFO => 'Fetching Account Info...',
            self::PHONE_SELECTION => 'Select Phone Number',
            self::PHONE_VALIDATION => 'Validating Phone...',
            self::PHONE_REGISTRATION => 'Registering Phone...',
            self::SYNCING_TEMPLATES => 'Syncing Templates...',
            self::VERIFYING_SETUP => 'Verifying Setup...',
            self::ACTIVE => 'Active',
            self::DEGRADED => 'Degraded (Quality Warning)',
            self::SUSPENDED => 'Suspended (Critical Issue)',
            self::TOKEN_EXPIRED => 'Token Expired',
            self::AUTH_FAILED => 'Authentication Failed',
            self::TOKEN_EXCHANGE_FAILED => 'Token Exchange Failed',
            self::INVALID_CREDENTIALS => 'Invalid Credentials',
            self::ACCOUNT_FETCH_FAILED => 'Account Fetch Failed',
            self::NO_PHONES_AVAILABLE => 'No Phones Available',
            self::PHONE_MISMATCH => 'Phone Number Mismatch',
            self::REGISTRATION_FAILED => 'Registration Failed',
            self::SYNC_FAILED => 'Template Sync Failed',
            self::PARTIAL_SETUP => 'Partial Setup',
            self::PHONE_RESTRICTED => 'Phone Restricted',
            self::REFRESHING_TOKEN => 'Refreshing Token...',
        };
    }
}
