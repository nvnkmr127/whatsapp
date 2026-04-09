<?php

namespace App\Enums;

enum EmailUseCase: string
{
    case OTP = 'otp';
    case ALERT = 'alert';
    case NOTIFICATION = 'notification';
    case MARKETING = 'marketing';
    case INVOICE = 'invoice';

    public function getMailer(): string
    {
        return match ($this) {
            self::OTP, self::ALERT, self::INVOICE => 'transactional',
            self::MARKETING => 'marketing',
            default => config('mail.default'),
        };
    }
}
