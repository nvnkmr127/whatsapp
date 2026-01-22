<?php

namespace App\Enums;

enum IntegrationState: string
{
    case DISCONNECTED = 'disconnected';
    case AUTHENTICATED = 'authenticated';
    case PROVISIONED = 'provisioned';
    case READY = 'ready';
    case READY_WARNING = 'ready_warning';
    case SUSPENDED = 'suspended';
    case RESTRICTED = 'restricted';
    case NOT_CONFIGURED = 'NOT_CONFIGURED';
    case ACTIVE = 'ACTIVE';
    case DISCONNECTED_UPPER = 'DISCONNECTED';
    case SUSPENDED_UPPER = 'SUSPENDED';
    case AUTHENTICATING = 'AUTHENTICATING';
    case TOKEN_EXCHANGE = 'TOKEN_EXCHANGE';
    case VALIDATING_CREDENTIALS = 'VALIDATING_CREDENTIALS';
    case FETCHING_ACCOUNT_INFO = 'FETCHING_ACCOUNT_INFO';
    case PHONE_SELECTION = 'PHONE_SELECTION';
    case PHONE_VALIDATION = 'PHONE_VALIDATION';
    case PHONE_REGISTRATION = 'PHONE_REGISTRATION';
    case SYNCING_TEMPLATES = 'SYNCING_TEMPLATES';
    case VERIFYING_SETUP = 'VERIFYING_SETUP';
    case DEGRADED = 'DEGRADED';
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
            self::DISCONNECTED => 'Disconnected',
            self::AUTHENTICATED => 'Authenticated',
            self::PROVISIONED => 'Provisioned',
            self::READY => 'Ready',
            self::READY_WARNING => 'Ready (Action Required)',
            self::SUSPENDED => 'Suspended (Action Required)',
            self::RESTRICTED => 'Restricted (Policy Violation)',
            self::NOT_CONFIGURED => 'Not Connected',
            self::ACTIVE => 'Ready',
            self::DISCONNECTED_UPPER => 'Disconnected',
            self::SUSPENDED_UPPER => 'Suspended (Action Required)',
            default => 'Provisioning...',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DISCONNECTED => 'slate',
            self::AUTHENTICATED => 'amber',
            self::PROVISIONED => 'blue',
            self::READY => 'green',
            self::READY_WARNING => 'amber',
            self::SUSPENDED => 'rose',
            self::RESTRICTED => 'rose',
            self::NOT_CONFIGURED => 'slate',
            self::ACTIVE => 'green',
            self::DISCONNECTED_UPPER => 'slate',
            self::SUSPENDED_UPPER => 'rose',
            default => 'blue',
        };
    }
}
