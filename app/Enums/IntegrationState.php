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
    case DEGRADED = 'degraded';
    case TOKEN_EXPIRED = 'token_expired';

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
            self::DEGRADED => 'Degraded',
            self::TOKEN_EXPIRED => 'Token Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DISCONNECTED => 'slate',
            self::AUTHENTICATED, self::READY_WARNING, self::DEGRADED, self::TOKEN_EXPIRED => 'amber',
            self::PROVISIONED => 'blue',
            self::READY => 'green',
            self::SUSPENDED, self::RESTRICTED => 'rose',
        };
    }

    public function isReady(): bool
    {
        return $this === self::READY;
    }

    public function isFailure(): bool
    {
        return in_array($this, [self::SUSPENDED, self::RESTRICTED]);
    }

    public function isDegraded(): bool
    {
        return in_array($this, [self::DEGRADED, self::READY_WARNING]);
    }
}
