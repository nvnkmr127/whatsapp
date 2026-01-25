<?php

namespace App\Enums;

enum EmailUseCase: string
{
    case OTP = 'otp';
    case ALERT = 'alert';
    case NOTIFICATION = 'notification';
    case MARKETING = 'marketing';

    public function getMailer(): string
    {
        return match ($this) {
            self::OTP, self::ALERT => 'transactional',
            self::MARKETING => 'marketing',
            default => 'default',
        };
    }
}
