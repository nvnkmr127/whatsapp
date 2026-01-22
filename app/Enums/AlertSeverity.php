<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case EMERGENCY = 'emergency';

    public function getColor(): string
    {
        return match ($this) {
            self::INFO => 'blue',
            self::WARNING => 'yellow',
            self::CRITICAL => 'orange',
            self::EMERGENCY => 'red',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::INFO => 'ℹ️',
            self::WARNING => '⚠️',
            self::CRITICAL => '🔴',
            self::EMERGENCY => '🚨',
        };
    }
}
