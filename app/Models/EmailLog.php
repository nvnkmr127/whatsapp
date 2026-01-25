<?php

namespace App\Models;

use App\Enums\EmailUseCase;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'recipient',
        'use_case',
        'template_id',
        'subject',
        'status',
        'smtp_config_id',
        'provider_name',
        'failure_reason',
        'failure_type',
        'metadata',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'use_case' => EmailUseCase::class,
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function smtpConfig()
    {
        return $this->belongsTo(SmtpConfig::class);
    }
}
