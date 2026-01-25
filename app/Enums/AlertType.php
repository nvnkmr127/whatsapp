<?php

namespace App\Enums;

enum AlertType: string
{
    case SYSTEM = 'system';
    case SECURITY = 'security';
    case BILLING = 'billing';
    case COMPLIANCE = 'compliance';
    case OPERATIONAL = 'operational';
    case ACCOUNT = 'account';

    public function getLabel(): string
    {
        return match ($this) {
            self::SYSTEM => 'System Health',
            self::SECURITY => 'Security Alert',
            self::BILLING => 'Billing & Quota',
            self::COMPLIANCE => 'Compliance & Trust',
            self::OPERATIONAL => 'Operational Incident',
            self::ACCOUNT => 'Account Activity',
        };
    }
}
