<?php

namespace App\Enums;

enum WhatsAppMessageStatus: string
{
    case SENT      = 'sent';
    case DELIVERED = 'delivered';
    case READ      = 'read';
    case FAILED    = 'failed';
    case PENDING   = 'pending';
}

enum WhatsAppTemplateStatus: string
{
    case APPROVED = 'approved';
    case PENDING  = 'pending';
    case REJECTED = 'rejected';
}
