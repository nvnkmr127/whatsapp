<?php

namespace App\Enums;

enum IntegrationState: string
{
    // Primary States (Lowercase Values)
    case DISCONNECTED = 'disconnected';
    case AUTHENTICATED = 'authenticated';
    case PROVISIONED = 'provisioned';
    case READY = 'ready';
    case READY_WARNING = 'ready_warning';
    case SUSPENDED = 'suspended';
    case RESTRICTED = 'restricted';
    case DEGRADED = 'degraded';
    case CONNECTED = 'connected'; // Added to fix runtime error in resetCircuitBreaker

    // Backward Compatibility / Legacy Mappings (Redirect to primary cases if possible)
    case ACTIVE = 'ACTIVE'; // Unified with READY in labels/colors
    case NOT_CONFIGURED = 'NOT_CONFIGURED';
    case DISCONNECTED_UPPER = 'DISCONNECTED';
    case SUSPENDED_UPPER = 'SUSPENDED';

    // Transient / Operational States
    case AUTHENTICATING = 'AUTHENTICATING';
    case TOKEN_EXCHANGE = 'TOKEN_EXCHANGE';
    case VALIDATING_CREDENTIALS = 'VALIDATING_CREDENTIALS';
    case FETCHING_ACCOUNT_INFO = 'FETCHING_ACCOUNT_INFO';
    case PHONE_SELECTION = 'PHONE_SELECTION';
    case PHONE_VALIDATION = 'PHONE_VALIDATION';
    case PHONE_REGISTRATION = 'PHONE_REGISTRATION';
    case SYNCING_TEMPLATES = 'SYNCING_TEMPLATES';
    case VERIFYING_SETUP = 'VERIFYING_SETUP';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
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

    public function label(): string
    {
        return match ($this) {
            self::DISCONNECTED, self::DISCONNECTED_UPPER => 'Disconnected',
            self::AUTHENTICATED => 'Authenticated',
            self::PROVISIONED => 'Provisioned',
            self::READY, self::ACTIVE, self::CONNECTED => 'Ready',
            self::READY_WARNING => 'Ready (Action Required)',
            self::SUSPENDED, self::SUSPENDED_UPPER => 'Suspended (Action Required)',
            self::RESTRICTED => 'Restricted (Policy Violation)',
            self::NOT_CONFIGURED => 'Not Connected',
            self::DEGRADED => 'Degraded',
            default => 'Provisioning...',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DISCONNECTED, self::DISCONNECTED_UPPER, self::NOT_CONFIGURED => 'slate',
            self::AUTHENTICATED => 'amber',
            self::PROVISIONED => 'blue',
            self::READY, self::ACTIVE, self::CONNECTED => 'green',
            self::READY_WARNING => 'amber',
            self::SUSPENDED, self::SUSPENDED_UPPER, self::RESTRICTED => 'rose',
            self::DEGRADED => 'amber',
            default => 'blue',
        };
    }

    public function isReady(): bool
    {
        return in_array($this, [self::READY, self::ACTIVE, self::CONNECTED]);
    }

    public function isFailure(): bool
    {
        return in_array($this, [self::SUSPENDED, self::SUSPENDED_UPPER, self::RESTRICTED, self::AUTH_FAILED]);
    }
}
